<?php

namespace Tests\Feature\Extra;

use App\Events\OrderPaid as OrderPaidEvent;
use App\Livewire\Admin\Finance\RequestDepositIndex;
use App\Livewire\Admin\Finance\VendorCommissionIndex;
use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Seller;
use App\Models\SellerDeposit;
use App\Models\SellerInfo;
use App\Models\User;
use App\Services\Vendor\VendorSettlementService;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۳ — چندفروشندگی پیشرفته.
 * --------------------------------------------------------------------------
 *  • شکست اصلی که این فاز پر می‌کند: تا قبل از این فاز فقط سفارش‌های «کیف پولی»
 *    سهم فروشنده را واریز می‌کردند (Order::payUsingWallet inline)؛ سفارش‌های
 *    درگاهی هرگز کیف پول فروشنده را شارژ نمی‌کردند و ردیف کمیسیون ثبت نمی‌شد.
 *  • موتور تسویهٔ VendorSettlementService روی رویداد OrderPaid (هر دو مسیر وب و API)
 *    + ایدمپوتنس با ردیف seller_deposits (سفارش کیف‌پولی دوباره‌کاری نمی‌شود).
 *  • کمیسیون مؤثر: override فروشنده > دسته‌بندی > پیش‌فرض سراسری — همه با گیت تنظیماتی
 *    (الزام شخصی‌سازی: هر قابلیت حداقل یک سوییچ فعال/غیرفعال دارد).
 *  • پرداخت دسته‌جمعی تسویه با شمارهٔ پیگیری per-row (ستون trackingId در DB unique است).
 */
class Phase103Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // بازگشت همهٔ کلیدهای فاز ۱۰۳ به پیش‌فرض + کش آپشن‌ها و کش رزولوشن
        $this->resetVendorOptions();
        MultiVendorSettings::flushCache();
    }

    protected function tearDown(): void
    {
        $this->resetVendorOptions();
        MultiVendorSettings::flushCache();
        Cache::flush();

        parent::tearDown();
    }

    private function resetVendorOptions(): void
    {
        // پیش‌فرض‌ها = دقیقاً رفتار قبل از فاز ۱۰۳ (به‌جز خود کلید اصلی که قدیمی است)
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        option_update('multi_vendor_auto_settlement', '1');
        option_update('multi_vendor_seller_commission_override', '1');
        option_update('multi_vendor_bulk_settlement', '1');
        option_update('multi_vendor_dashboard_stats', '1');
        option_update('multi_vendor_default_commission', '');
        option_update('multi_vendor_withdraw_min_amount', '0');
        Cache::flush();
    }

    private function enableVendor(): void
    {
        MultiVendorSettings::setEnabled(true);
        Cache::flush();
    }

    private function newSeller(array $attributes = []): Seller
    {
        $suffix = Str::random(8);

        $seller = Seller::create(array_merge([
            'slug' => 'seller-'.$suffix,
            'email' => 'seller-'.$suffix.'@test.ir',
            'mobile' => '0913'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => Hash::make('secret1234'),
            'status' => 'ACTIVE',
            'status_register' => 'complete',
            'status_documents' => 'Accept',
            'status_work' => 'ACTIVE',
        ], $attributes));

        // ردیف پروفایل — پنل‌های ادمین نام کسب‌وکار را می‌خوانند (سناریوی واقعی فروشنده)
        SellerInfo::create([
            'seller_id' => $seller->id,
            'business_name' => 'کسب‌وکار '.$suffix,
            'first_name' => 'نام',
            'last_name' => $suffix,
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'آدرس تست',
            'post_code' => '1234567890',
            'phone' => '02112345678',
            'mobile' => $seller->mobile,
            'shaba_number' => 'IR'.str_pad((string) random_int(1000000, 9999999), 24, '0', STR_PAD_LEFT),
            'main_supply_category_id' => 1,
            'number_of_products' => 0,
        ]);

        return $seller;
    }

    private function newCreator(): Admin
    {
        foreach (Permission::where('active', true)->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        Gate::define('request_deposit', fn ($user = null) => true);
        Gate::define('payments.transactions.seller_deposit', fn ($user = null) => true);

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۳',
            'username' => 'feat103-'.Str::random(6),
            'email' => 'feat103-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /** سفارش پرداخت‌شده با یک آیتم فروشنده‌دار (مسیر درگاهی — بدون واریز inline) */
    private function newSellerPaidOrder(Seller $seller, int $price = 100000, int $realPrice = 100000): array
    {
        return $this->newSellerOrderFor($seller, $this->newUser(), $price, $realPrice);
    }

    private function newSellerOrderFor(Seller $seller, User $user, int $price, int $realPrice): array
    {
        $product = $this->newProduct([], [
            'price' => $realPrice,
            'discount' => $realPrice - $price,
        ]);

        [$order, $item] = $this->newPaidOrder($user, $product, [
            'price' => $price,
        ], [
            'seller_id' => $seller->id,
            'price' => $price,
            'real_price' => $realPrice,
        ]);

        return [$order, $item];
    }

    /* ================= ۱) کلاس تنظیمات ================= */

    #[Test]
    #[TestDox('کلید اصلی هم قرارداد قدیمی true/false و هم 1/0 را می‌پذیرد و با قرارداد قدیمی می‌نویسد')]
    public function settings_class_is_legacy_compatible(): void
    {
        option_update(MultiVendorSettings::KEY_ENABLED, 'true');
        Cache::flush();
        $this->assertTrue(MultiVendorSettings::enabled());

        option_update(MultiVendorSettings::KEY_ENABLED, '1');
        Cache::flush();
        $this->assertTrue(MultiVendorSettings::enabled());

        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        Cache::flush();
        $this->assertFalse(MultiVendorSettings::enabled());

        // نوشتن همیشه با قرارداد قدیمی 'true'/'false' تا خواننده‌های فاز ۳۰ نشکنند
        MultiVendorSettings::setEnabled(true);
        $this->assertSame('true', option(MultiVendorSettings::KEY_ENABLED, 'false'));

        MultiVendorSettings::setEnabled(false);
        $this->assertSame('false', option(MultiVendorSettings::KEY_ENABLED, 'false'));
    }

    #[Test]
    #[TestDox('تب «چندفروشندگی» تنظیمات برنامه‌نویس همهٔ کلیدها را ذخیره می‌کند')]
    public function developer_tab_saves_multi_vendor_settings(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorAutoSettlement', false)
            ->set('vendorSellerOverride', false)
            ->set('vendorBulkSettlement', false)
            ->set('vendorDashboardStats', false)
            ->set('vendorDefaultCommission', '15')
            ->set('vendorMinWithdraw', '50000')
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('true', option(MultiVendorSettings::KEY_ENABLED, 'false'));
        $this->assertTrue(MultiVendorSettings::enabled());
        $this->assertFalse(MultiVendorSettings::autoSettlement());
        $this->assertFalse(MultiVendorSettings::sellerOverrideEnabled());
        $this->assertFalse(MultiVendorSettings::bulkSettlementEnabled());
        $this->assertFalse(MultiVendorSettings::dashboardStatsEnabled());
        $this->assertSame(15, MultiVendorSettings::defaultCommission());
        $this->assertSame(50000, MultiVendorSettings::minWithdrawAmount());

        // اعتبارسنجی: کمیسیون بیش از ۱۰۰ رد می‌شود
        Livewire::test(Developer::class)
            ->set('vendorDefaultCommission', '150')
            ->call('saveMultiVendor')
            ->assertHasErrors(['vendorDefaultCommission']);
    }

    /* ================= ۲) کمیسیون ================= */

    #[Test]
    #[TestDox('اولویت کمیسیون مؤثر: فروشنده > دسته‌بندی > پیش‌فرض سراسری > صفر')]
    public function commission_resolution_priority_is_correct(): void
    {
        $seller = $this->newSeller();

        // ① بدون دسته‌بندی صریح و بدون پیش‌فرض → صفر (رفتار قبل از فاز)
        $resolved = MultiVendorSettings::resolveCommission($seller->id, ['commission' => 0, 'category_id' => null]);
        $this->assertSame(0, $resolved['commission']);
        $this->assertSame('category', $resolved['source']);

        // ② بدون دسته‌بندی صریح + پیش‌فرض ۱۵ → ۱۵
        option_update('multi_vendor_default_commission', '15');
        Cache::flush();
        $resolved = MultiVendorSettings::resolveCommission($seller->id, ['commission' => 0, 'category_id' => null]);
        $this->assertSame(15, $resolved['commission']);
        $this->assertSame('default', $resolved['source']);

        // ③ دسته‌بندی صریحِ ۱۰ بر پیش‌فرض مقدم است
        $resolved = MultiVendorSettings::resolveCommission($seller->id, ['commission' => 10, 'category_id' => 55]);
        $this->assertSame(10, $resolved['commission']);
        $this->assertSame('category', $resolved['source']);
        $this->assertSame(55, $resolved['category_id']);

        // ④ override فروشنده ۲۵ بر همه مقدم است
        $seller->update(['commission' => 25]);
        MultiVendorSettings::flushCache();
        $resolved = MultiVendorSettings::resolveCommission($seller->id, ['commission' => 10, 'category_id' => 55]);
        $this->assertSame(25, $resolved['commission']);
        $this->assertSame('seller', $resolved['source']);

        // ⑤ گیت خاموش override → برگشت به دسته‌بندی
        option_update('multi_vendor_seller_commission_override', '0');
        Cache::flush();
        MultiVendorSettings::flushCache();
        $resolved = MultiVendorSettings::resolveCommission($seller->id, ['commission' => 10, 'category_id' => 55]);
        $this->assertSame(10, $resolved['commission']);
        $this->assertSame('category', $resolved['source']);
    }

    #[Test]
    #[TestDox('priceSellerDepositWallet کمیسیون پیش‌فرض سراسری را روی آیتم و سهم سایت اعمال می‌کند')]
    public function price_seller_deposit_applies_global_default_commission(): void
    {
        $this->enableVendor();
        option_update('multi_vendor_default_commission', '15');
        Cache::flush();

        $seller = $this->newSeller();
        // priceSeller = 90000 (واحد، بدون ارسال، بدون تخفیف سفارش)
        [$order] = $this->newSellerPaidOrder($seller, 90000, 100000);

        $amount = $order->priceSellerDepositWallet($seller->id);

        $this->assertSame(15, $amount['percent']);
        $this->assertSame(90000 - 13500, $amount['priceForSeller']); // 76500
        $this->assertSame(13500, $amount['priceForSite']);

        // کمیسیون روی آیتم هم مُهر شده است
        $this->assertSame(15, $order->items()->first()->commission);
    }

    #[Test]
    #[TestDox('پنل کمیسیون: ذخیرهٔ override فروشنده + گیت خاموشی + آیتم منو')]
    public function commission_panel_saves_override_and_respects_gate(): void
    {
        $this->newCreator();
        $this->enableVendor();

        $seller = $this->newSeller();

        // آیتم منو با گیت vendor ثبت شده باشد (جست‌وجوی بازگشتی — آیتم داخل children هم)
        $findItem = function (array $items, string $route) use (&$findItem) {
            foreach ($items as $item) {
                if (($item['route'] ?? null) === $route) {
                    return $item;
                }

                if (! empty($item['children'])) {
                    $hit = $findItem($item['children'], $route);

                    if ($hit) {
                        return $hit;
                    }
                }
            }

            return null;
        };

        $menuItem = null;

        foreach (config('admin-menu.groups', []) as $group) {
            $hit = $findItem($group['items'] ?? [], 'np.vendors.commission');

            if ($hit) {
                $menuItem = $hit;
                break;
            }
        }

        $this->assertNotNull($menuItem, 'آیتم منوی «کمیسیون فروشندگان» باید ثبت شود');
        $this->assertTrue(($menuItem['vendor'] ?? false) === true);
        $this->assertSame('payments.transactions.seller_deposit', $menuItem['permission'] ?? '');

        // ذخیرهٔ override
        Livewire::test(VendorCommissionIndex::class)
            ->call('editCommission', $seller->id)
            ->assertSet('editing', true)
            ->set('editingValue', '20')
            ->call('saveCommission')
            ->assertDispatched('toast');

        $this->assertSame(20, $seller->fresh()->commission);

        // خالی = حذف override
        Livewire::test(VendorCommissionIndex::class)
            ->call('editCommission', $seller->id)
            ->set('editingValue', '')
            ->call('saveCommission');

        $this->assertNull($seller->fresh()->commission);

        // گیت خاموشی override → ذخیره ممنوع
        option_update('multi_vendor_seller_commission_override', '0');
        Cache::flush();
        MultiVendorSettings::flushCache();

        Livewire::test(VendorCommissionIndex::class)
            ->call('editCommission', $seller->id)
            ->assertHasErrors(['editingValue']);
    }

    /* ================= ۳) موتور تسویه ================= */

    #[Test]
    #[TestDox('تسویهٔ خودکار: سفارش درگاهی سهم فروشنده را واریز و ردیف کمیسیون ثبت می‌کند — و تکرارش دوباره‌کاری نمی‌کند')]
    public function settlement_service_credits_seller_wallet_and_is_idempotent(): void
    {
        $this->enableVendor();
        option_update('multi_vendor_default_commission', '15');
        Cache::flush();

        $seller = $this->newSeller();
        [$order] = $this->newSellerPaidOrder($seller, 90000, 100000);

        $wallet = $seller->getWallet();
        $this->assertSame(0, $wallet->balance());

        $service = app(VendorSettlementService::class);

        $this->assertSame(1, $service->settleOrder($order));

        $wallet->refresh();
        $this->assertSame(76500, (int) $wallet->balance());

        // تاریخچهٔ واریز + ردیف کمیسیون
        $this->assertSame(1, $wallet->histories()->where(['type' => 'deposit', 'order_id' => $order->id])->count());
        $deposit = SellerDeposit::where(['order_id' => $order->id, 'seller_id' => $seller->id])->first();
        $this->assertNotNull($deposit);
        $this->assertSame(13500, (int) $deposit->amount);
        $this->assertSame(15, (int) $deposit->percent);
        $this->assertSame('success', $deposit->status);

        // ایدمپوتنس — اجرای دوم هیچ رکورد جدیدی نمی‌سازد
        $this->assertSame(0, $service->settleOrder($order));
        $this->assertSame(1, SellerDeposit::where('order_id', $order->id)->count());
        $wallet->refresh();
        $this->assertSame(76500, (int) $wallet->balance());
    }

    #[Test]
    #[TestDox('سفارشِ قبلاً تسویه‌شدهٔ کیف‌پولی (ردیف seller_deposits دارد) توسط شنونده دوباره واریز نمی‌شود')]
    public function settlement_skips_wallet_lane_orders(): void
    {
        $this->enableVendor();

        $seller = $this->newSeller();
        [$order] = $this->newSellerPaidOrder($seller, 90000, 100000);

        // شبیه‌سازی مسیر کیف پول: ردیف کمیسیون از قبل ثبت شده (inline payUsingWallet)
        SellerDeposit::create([
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'amount' => 13500,
            'percent' => 15,
            'description' => 'ثبت سفارش',
            'status' => 'success',
        ]);

        $settled = app(VendorSettlementService::class)->settleOrder($order);

        $this->assertSame(0, $settled);
        $this->assertSame(1, SellerDeposit::where('order_id', $order->id)->count());
        $this->assertSame(0, $seller->getWallet()->balance());
    }

    #[Test]
    #[TestDox('شنوندهٔ OrderPaid با هر دو گیت (کلید اصلی + تسویهٔ خودکار) کنترل می‌شود')]
    public function order_paid_listener_respects_settings_gates(): void
    {
        option_update('multi_vendor_default_commission', '15');
        Cache::flush();

        $seller = $this->newSeller();
        [$order] = $this->newSellerPaidOrder($seller, 90000, 100000);

        // ① کلید اصلی خاموش (پیش‌فرض setUp) → تسویه نه
        event(new OrderPaidEvent($order));
        $this->assertSame(0, SellerDeposit::where('order_id', $order->id)->count());

        // ② کلید اصلی روشن + تسویهٔ خودکار روشن → تسویه می‌شود
        $this->enableVendor();
        event(new OrderPaidEvent($order));
        $this->assertSame(1, SellerDeposit::where('order_id', $order->id)->count());
        $this->assertSame(76500, (int) $seller->getWallet()->balance());

        // ③ سفارش دوم با تسویهٔ خودکار خاموش → تسویه نه
        option_update('multi_vendor_auto_settlement', '0');
        Cache::flush();
        [$order2] = $this->newSellerPaidOrder($seller, 50000, 50000);
        event(new OrderPaidEvent($order2));
        $this->assertSame(0, SellerDeposit::where('order_id', $order2->id)->count());
        $this->assertSame(76500, $seller->getWallet()->balance());
    }

    /* ================= ۴) متدهای کیف پول ================= */

    #[Test]
    #[TestDox('Wallet::deposit/withdraw تاریخچه می‌سازند، موجودی را تازه‌سازی و خروجی truthy برمی‌گردانند')]
    public function wallet_deposit_withdraw_helpers_work(): void
    {
        $user = $this->newUser();
        $wallet = $user->getWallet();

        $deposit = $wallet->deposit(100000, 'شارژ تست');
        $this->assertNotNull($deposit);
        $this->assertSame('deposit', $deposit->type);
        $this->assertSame('user', $deposit->source);
        $this->assertSame(100000, $wallet->balance());

        $withdraw = $wallet->withdraw(30000, 'برداشت تست');
        $this->assertSame('withdraw', $withdraw->type);
        $this->assertSame(70000, $wallet->balance());

        // کیف پول فروشنده → source=seller
        $seller = $this->newSeller();
        $sellerWallet = $seller->getWallet();
        $sellerWallet->deposit(5000, 'تست فروشنده');
        $this->assertSame('seller', $sellerWallet->histories()->latest('id')->first()->source);
        $this->assertSame(5000, $sellerWallet->balance());
    }

    /* ================= ۵) پرداخت دسته‌جمعی ================= */

    #[Test]
    #[TestDox('پرداخت دسته‌جمعی درخواست‌های در انتظار را با trackingId یکتای per-row پرداخت می‌کند')]
    public function bulk_pay_marks_waiting_rows_paid_with_unique_tracking_ids(): void
    {
        $this->newCreator();
        $this->enableVendor();

        $seller = $this->newSeller();
        $wallet = $seller->getWallet();

        $row1 = $wallet->histories()->create([
            'type' => 'withdraw', 'amount' => 40000, 'description' => 'درخواست برداشت ۱',
            'source' => 'seller', 'status' => 'success', 'withdraw' => 1, 'status_pay' => 'waiting',
        ]);
        $row2 = $wallet->histories()->create([
            'type' => 'withdraw', 'amount' => 30000, 'description' => 'درخواست برداشت ۲',
            'source' => 'seller', 'status' => 'success', 'withdraw' => 1, 'status_pay' => 'waiting',
        ]);
        // یک ردیف غیر در انتظار — نباید دست بخورد
        $row3 = $wallet->histories()->create([
            'type' => 'withdraw', 'amount' => 10000, 'description' => 'ردیف unpay',
            'source' => 'seller', 'status' => 'success', 'withdraw' => 1, 'status_pay' => 'unpay',
        ]);

        Livewire::test(RequestDepositIndex::class)
            ->set('selected', [$row1->id, $row2->id, $row3->id])
            ->call('openBulkPay')
            ->assertSet('confirmingBulkPay', true)
            ->set('bulkPayTrackingId', 'TR-942')
            ->call('bulkPay')
            ->assertDispatched('toast');

        $this->assertSame('pay', $row1->fresh()->status_pay);
        $this->assertSame('TR-942-1', $row1->fresh()->trackingId);
        $this->assertSame('pay', $row2->fresh()->status_pay);
        $this->assertSame('TR-942-2', $row2->fresh()->trackingId);

        // ردیف unpay دست نخورده
        $this->assertSame('unpay', $row3->fresh()->status_pay);
        $this->assertNull($row3->fresh()->trackingId);
    }

    #[Test]
    #[TestDox('پرداخت دسته‌جمعی با خاموشی گیت تنظیماتی ممنوع می‌شود')]
    public function bulk_pay_is_blocked_when_setting_disabled(): void
    {
        $this->newCreator();
        $this->enableVendor();
        option_update('multi_vendor_bulk_settlement', '0');
        Cache::flush();

        $seller = $this->newSeller();
        $wallet = $seller->getWallet();
        $row = $wallet->histories()->create([
            'type' => 'withdraw', 'amount' => 40000, 'description' => 'درخواست برداشت',
            'source' => 'seller', 'status' => 'success', 'withdraw' => 1, 'status_pay' => 'waiting',
        ]);

        // گیت خاموشی → گارد سروری openBulkPay نباید اجازهٔ پرداخت بدهد
        $seller = $this->newSeller();
        $wallet = $seller->getWallet();
        $row = $wallet->histories()->create([
            'type' => 'withdraw', 'amount' => 40000, 'description' => 'درخواست برداشت',
            'source' => 'seller', 'status' => 'success', 'withdraw' => 1, 'status_pay' => 'waiting',
        ]);

        // sanity: کلید واقعاً خاموش خوانده می‌شود
        $this->assertFalse(MultiVendorSettings::bulkSettlementEnabled());

        $component = Livewire::test(RequestDepositIndex::class)
            ->set('selected', [$row->id]);

        // Livewire در تست ممکن است HttpException را به پاسخ 403 تبدیل کند —
        // قرارداد رفتاری: «هیچ پرداخت دسته‌جمعی رخ ندهد»؛ هر دو حالت را پوشش می‌دهیم
        try {
            $component->call('openBulkPay');
            $component->assertSet('confirmingBulkPay', false);
        } catch (\Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 0;
            $this->assertSame(403, $status);
        }

        // هیچ ردیفی پرداخت نشده و شمارهٔ پیگیری ندارد
        $this->assertSame('waiting', $row->fresh()->status_pay);
        $this->assertNull($row->fresh()->trackingId);
    }

    /* ================= ۶) حداقل مبلغ برداشت ================= */

    #[Test]
    #[TestDox('حداقل مبلغ برداشت فروشنده از تنظیمات اعمال می‌شود')]
    public function seller_withdraw_respects_min_amount_setting(): void
    {
        $this->enableVendor();
        option_update('multi_vendor_withdraw_min_amount', '50000');
        Cache::flush();

        $seller = $this->newSeller();
        $wallet = $seller->getWallet();
        $wallet->deposit(100000, 'شارژ');
        $this->actingAs($seller, 'seller');

        // زیر حد تنظیمی → خطای اعتبارسنجی و بدون تاریخچه
        $response = $this->post(route('seller.wallet.withdraw'), ['amount' => '20000']);
        $response->assertSessionHasErrors('amount');
        $this->assertSame(0, $wallet->histories()->where('type', 'withdraw')->count());

        // بالای حد → درخواست ثبت می‌شود
        $this->post(route('seller.wallet.withdraw'), ['amount' => '60000']);
        $this->assertSame(1, $wallet->histories()->where(['type' => 'withdraw', 'withdraw' => 1])->count());
    }
}
