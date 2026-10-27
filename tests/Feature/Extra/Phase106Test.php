<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\Seller;
use App\Models\SellerDeposit;
use App\Models\SellerInfo;
use App\Models\Viewer;
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
 * فاز ۱۰۶ الف — پنل فروشنده: پنج شکستهٔ کشف‌شده در بررسی «پنل حرفه‌ای‌تر».
 * --------------------------------------------------------------------------
 *  1) روت seller.commission.guide به متدی اشاره می‌کرد که هرگز نوشته نشده بود → 500
 *  2) ویو seller.statistics.index وجود نداشت → 500
 *  3) فرم فیلتر «مدیریت محصولات» فروشنده به admin.products.index پست می‌شد (کپی از پنل ادمین)
 *  4) کارت «ارزش سفارشات موفق» داشبورد orders.seller_id (ستون قدیمی) را جمع می‌زد و
 *     با «فروش موفق» بلوک چندفروشندگی (order_items.seller_id) دو عدد متفاوت نشان می‌داد
 *  5) partial اعلان‌های هدر فروشنده به route ادمین لینک می‌داد (کد مرده)
 *
 * فاز ۱۰۶-۱ — صورت‌حساب تسویهٔ فروشنده (سوییچ multi_vendor_settlement_statement)
 * فاز ۱۰۶-۲ — گزارش فروش فروشنده (سوییچ seller_analytics_enabled)
 */
class Phase106Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // پنل فروشنده پشت میدل‌ویر Seller گیتِ multi_vendor_system_status دارد
        MultiVendorSettings::setEnabled(true);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        MultiVendorSettings::setEnabled(false);
        Cache::flush();

        parent::tearDown();
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

        // ردیف پروفایل — لایوت پنل فروشنده seller_info()->business_name و لوگو را می‌خواند
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

    /** ادمین creator با همهٔ گیت‌ها (الگوی فاز ۱۰۳) — برای تب تنظیمات Developer */
    private function newCreator(): void
    {
        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۶',
            'username' => 'feat106-'.Str::random(6),
            'email' => 'feat106-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        Gate::before(function ($user = null) {
            return $user instanceof Admin && $user->level === 'creator' ? true : null;
        });

        Auth::guard('adminPanel')->setUser($creator);
    }

    /* ================= 1) راهنمای کمیسیون ================= */

    #[Test]
    #[TestDox('روت seller.commission.guide دیگر 500 نمی‌دهد و نرخ‌های کمیسیون را نشان می‌دهد')]
    public function seller_commission_guide_renders(): void
    {
        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        $response = $this->get(route('seller.commission.guide'));

        $response->assertOk();
        // همان محتوای صفحهٔ کمیسیون (نرخ‌های مؤثر دسته‌بندی‌ها)
        $response->assertSee('جدول کمیسیون دسته‌بندی‌ها', false);

        // و روت اصلی کمیسیون هم سرِ جای خودش سبز بماند
        $this->get(route('seller.commission'))->assertOk();
    }

    /* ================= 2) صفحهٔ آمار ================= */

    #[Test]
    #[TestDox('صفحهٔ آمار فروشنده با ویو تم رندر می‌شود (قبلاً ویو وجود نداشت)')]
    public function seller_statistics_page_renders(): void
    {
        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        $response = $this->get(route('seller.statistics.index'));

        $response->assertOk();
        $response->assertSee('آمار بازدید فروشگاه', false);
        $response->assertSee('بازدیدکنندگان یکتا', false);
        $response->assertSee('stx-daily-chart', false);
    }

    /* ================= 3) فرم فیلتر محصولات ================= */

    #[Test]
    #[TestDox('فرم فیلتر محصولات فروشنده به روت خودِ فروشنده اشاره می‌کند نه ادمین')]
    public function seller_products_filter_form_points_to_seller_route(): void
    {
        $seller = $this->newSeller();
        $this->actingAs($seller, 'seller');

        // صفحهٔ «مدیریت محصولات»
        $response = $this->get(route('seller.products.index'));
        $response->assertOk();
        $response->assertSee(route('seller.products.index'), false);
        $response->assertDontSee(route('admin.products.index'), false);

        // صفحهٔ «جستجو یا درج محصول» (همان باگ کپی‌شده از ادمین)
        $response = $this->get(route('seller.products.find'));
        $response->assertOk();
        $response->assertSee(route('seller.products.index'), false);
        $response->assertDontSee(route('admin.products.index'), false);
    }

    /* ================= 4) یکسان‌سازی口径 آمار داشبورد ================= */

    /** سفارش پرداخت‌شده با یک آیتم فروشنده‌دار (همان الگوی Phase103Test) */
    private function newSellerPaidOrder(Seller $seller, int $price = 100000): array
    {
        $product = $this->newProduct([], [
            'price' => $price,
        ]);

        return $this->newPaidOrder($this->newUser(), $product, [], [
            'seller_id' => $seller->id,
            'price' => $price,
            'real_price' => $price,
        ]);
    }

    #[Test]
    #[TestDox('کارت «ارزش سفارشات موفق» بر مبنای order_items.seller_id است نه ستون قدیمی orders.seller_id')]
    public function dashboard_paid_orders_value_uses_order_items(): void
    {
        $sellerA = $this->newSeller();
        $sellerB = $this->newSeller();

        // سفارش پرداخت‌شده با آیتمِ فروشندهٔ A — قیمت خاص برای تشخیص عدد
        [$order, $item] = $this->newSellerPaidOrder($sellerA, 777000);

        // شبیه‌سازی دادهٔ قدیمی: ستون orders.seller_id به فروشندهٔ دیگری اشاره می‌کند
        // → کد قدیمی برای فروشندهٔ A صفر نشان می‌داد،口径 درست 777,000 است
        $order->forceFill(['seller_id' => $sellerB->id])->save();

        // بلوک آمار چندفروشندگی خاموش → کارت «ارزش سفارشات موفق» تنها منبع عدد است
        // (وگرنه کارت «فروش موفق» همان بلوک با口径 درست، تست را کور می‌کند)
        option_update('multi_vendor_dashboard_stats', '0');
        Cache::flush();

        $this->actingAs($sellerA, 'seller');
        $response = $this->get(route('seller.dashboard'));

        $response->assertOk();
        $response->assertSee('ارزش سفارشات موفق', false);
        $response->assertSee('777,000', false);
    }

    /* ================= 5) partial اعلان‌ها ================= */

    #[Test]
    #[TestDox('partial اعلان‌های فروشنده به پنل فروشنده لینک می‌دهد نه ادمین')]
    public function seller_notifications_partial_links_to_seller_panel(): void
    {
        $html = view('front::sellers.panel.partials.notifications', [
            'notifications' => collect([]),
        ])->render();

        $this->assertStringContainsString(route('seller.notifications.index'), $html);
        $this->assertStringNotContainsString(route('admin.notifications'), $html);
    }

    /* ================= فاز ۱۰۶-۱) صورت‌حساب تسویهٔ فروشنده ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ صورت‌حساب تسویه را ذخیره می‌کند')]
    public function developer_tab_saves_settlement_statement_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorSettlementStatement', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('multi_vendor_settlement_statement', '1'));
        $this->assertFalse(MultiVendorSettings::settlementStatementEnabled());

        Livewire::test(Developer::class)
            ->set('vendorSettlementStatement', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('multi_vendor_settlement_statement', '1'));
    }

    /** ردیف کمیسیون + سهم واریزشدهٔ یک سفارش تسویه‌شدهٔ واقعی (شکل دقیق VendorSettlementService) */
    private function newSettledOrder(Seller $seller, int $price = 100000, int $commission = 23000): array
    {
        [$order, $item] = $this->newSellerPaidOrder($seller, $price);

        SellerDeposit::create([
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'amount' => $commission,
            'percent' => 23,
            'description' => 'کمیسیون فروش سفارش #'.$order->id,
            'status' => 'success',
        ]);

        $seller->getWallet()->histories()->create([
            'type' => 'deposit',
            'amount' => $price - $commission,
            'description' => 'تسویهٔ خودکار فروش سفارش #'.$order->id,
            'source' => 'seller',
            'status' => 'success',
            'order_id' => $order->id,
        ]);

        return [$order, $item];
    }

    #[Test]
    #[TestDox('صفحهٔ صورت‌حساب تسویه ردیف‌به‌ردیف کمیسیون و جمع‌ها را نشان می‌دهد')]
    public function settlement_statement_page_shows_rows_and_totals(): void
    {
        $seller = $this->newSeller();
        $this->newSettledOrder($seller, 100000, 23000);
        $this->actingAs($seller, 'seller');

        $response = $this->get(route('seller.wallet.settlements'));

        $response->assertOk();
        $response->assertSee('صورت‌حساب تسویه', false);
        $response->assertSee('23,000', false);          // کمیسیون کسرشده
        $response->assertSee('77,000', false);          // سهم واریزشده (۱۰۰٬۰۰۰ − ۲۳٬۰۰۰)
        $response->assertSee('تسویه‌شده', false);
        $response->assertSee(route('seller.wallet.settlements.export'), false);

        // آیتم منوی سایدبار هم دیده شود (سوییچ روشن) — سنجش URL (نام قابلیت در toastها هم هست)
        $this->get(route('seller.dashboard'))->assertSee(route('seller.wallet.settlements'), false);
    }

    #[Test]
    #[TestDox('سوییچ خاموش صورت‌حساب: صفحه به کیف پول برمی‌گردد و منو هم مخفی می‌شود')]
    public function settlement_statement_respects_toggle(): void
    {
        $seller = $this->newSeller();
        $this->newSettledOrder($seller);
        $this->actingAs($seller, 'seller');

        option_update('multi_vendor_settlement_statement', '0');
        Cache::flush();

        $response = $this->get(route('seller.wallet.settlements'));
        $response->assertRedirect(route('seller.wallet.index'));

        $response = $this->get(route('seller.wallet.settlements.export'));
        $response->assertRedirect(route('seller.wallet.index'));

        // آیتم منو با سوییچ خاموش حذف شود — سنجش URL (پیام toast حاوی نام قابلیت است)
        $this->get(route('seller.dashboard'))->assertDontSee(route('seller.wallet.settlements'), false);
    }

    #[Test]
    #[TestDox('خروجی اکسل صورت‌حساب دانلود می‌شود و شامل ردیف‌هاست')]
    public function settlement_statement_export_downloads_xlsx(): void
    {
        $seller = $this->newSeller();
        $this->newSettledOrder($seller, 100000, 23000);
        $this->actingAs($seller, 'seller');

        $response = $this->get(route('seller.wallet.settlements.export'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', (string) $response->headers->get('Content-Type'));

        // BinaryFileResponse — محتوا از فایل واقعی خوانده می‌شود (getContent برای stream برمی‌گرداند false)
        $file = $response->getFile();
        $this->assertTrue($file->isFile());
        $bytes = (string) file_get_contents($file->getPathname());
        $this->assertNotEmpty($bytes);
        // xlsx = بستهٔ ZIP → امضای PK
        $this->assertSame('PK', substr($bytes, 0, 2));
    }

    /* ================= فاز ۱۰۶-۲) گزارش فروش فروشنده ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ گزارش فروش فروشنده را ذخیره می‌کند')]
    public function developer_tab_saves_seller_analytics_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorSellerAnalytics', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('seller_analytics_enabled', '1'));
        $this->assertFalse(MultiVendorSettings::analyticsEnabled());

        Livewire::test(Developer::class)
            ->set('vendorSellerAnalytics', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('seller_analytics_enabled', '1'));
    }

    /** بازدید صفحات محصول فروشنده (مکملِ newSellerPaidOrder برای نرخ تبدیل) */
    private function newProductViews(Seller $seller, string $productSlug, int $count, int $daysAgo = 0): void
    {
        for ($i = 0; $i < $count; $i++) {
            Viewer::create([
                'ip' => '10.0.'.rand(1, 250).'.'.rand(1, 250),
                'product_path' => $productSlug,
                'path' => '/product/'.$productSlug,
                'created_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    #[Test]
    #[TestDox('گزارش فروش: درآمد ۳۰ روزه، پرفروش‌ترین‌ها و نرخ تبدیل بر مبنای order_items.seller_id')]
    public function seller_analytics_shows_revenue_bestsellers_and_conversion(): void
    {
        $seller = $this->newSeller();

        // دو سفارش پرداخت‌شدهٔ فروشندهٔ A (۵۰۰٬۰۰۰ × ۲ قلم = ۱٬۰۰۰٬۰۰۰ برای تشخیص عدد)
        // محصولات با seller_id فروشنده ساخته می‌شوند (الگوی «جستجو یا درج محصول») تا
        // بازدید صفحاتشان در نرخ تبدیل شمرده شود
        $productA = $this->newProduct(['seller_id' => $seller->id], ['price' => 500000]);
        [$orderA, $itemA] = $this->newPaidOrder($this->newUser(), $productA, [], [
            'seller_id' => $seller->id,
            'price' => 500000,
            'real_price' => 500000,
        ]);
        $itemA->forceFill(['quantity' => 2])->save();

        $productB = $this->newProduct(['seller_id' => $seller->id], ['price' => 200000]);
        $this->newPaidOrder($this->newUser(), $productB, [], [
            'seller_id' => $seller->id,
            'price' => 200000,
            'real_price' => 200000,
        ]);

        // سفارشِ فروشندهٔ دیگر نباید در گزارش فروشندهٔ A دیده شود
        $this->newSellerPaidOrder($this->newSeller(), 999000);

        // بازدید صفحات محصول (۵ بازدید محصول اول + ۵ بازدید محصول دوم)
        $this->newProductViews($seller, $itemA->product->slug, 5);
        $this->newProductViews($seller, $productB->slug, 5);

        $this->actingAs($seller, 'seller');
        $response = $this->get(route('seller.statistics.index'));

        $response->assertOk();
        $response->assertSee('گزارش فروش (۳۰ روز اخیر)', false);
        // درآمد: ۱٬۰۰۰٬۰۰۰ + ۲۰۰٬۰۰۰ = ۱٬۲۰۰٬۰۰۰ (سفارش ۹۹۹٬۰۰۰ فروشندهٔ دیگر حذف)
        $response->assertSee('1,200,000', false);
        $response->assertSee('روند درآمد ۳۰ روز اخیر', false);
        $response->assertSee('stx-revenue-chart', false);
        // پرفروش‌ترین‌ها (محصول اول ۲ قلم در صدر)
        $response->assertSee('پرفروش‌ترین محصولات', false);
        $response->assertSee($itemA->product->title, false);
        // بازدید → سفارش: ۱۰ بازدید / ۲ سفارش = ۲۰٪
        $response->assertSee('بازدید → سفارش', false);
        $response->assertSee('20.0٪', false);

        // آیتم منوی سایدبار
        $this->get(route('seller.dashboard'))->assertSee(route('seller.statistics.index'), false);
    }

    #[Test]
    #[TestDox('سوییچ خاموش گزارش فروش: بخش فروش حذف ولی آمار بازدید همان صفحه سرو می‌شود')]
    public function seller_analytics_respects_toggle(): void
    {
        $seller = $this->newSeller();
        $this->newSellerPaidOrder($seller, 500000);
        $this->newProductViews($seller, 'any-product', 4);
        $this->actingAs($seller, 'seller');

        option_update('seller_analytics_enabled', '0');
        Cache::flush();

        $response = $this->get(route('seller.statistics.index'));

        // صفحهٔ آمار (فاز ۱۰۶ الف) همچنان ۲۰۰ و آمار بازدید دارد
        $response->assertOk();
        $response->assertSee('آمار بازدید فروشگاه', false);
        $response->assertSee('بازدیدکنندگان یکتا', false);
        $response->assertSee('stx-daily-chart', false);

        // بخش فروش کامل حذف — نه کارت‌ها، نه نمودار، نه پرفروش‌ها
        $response->assertDontSee('گزارش فروش (۳۰ روز اخیر)', false);
        $response->assertDontSee('stx-revenue-chart', false);
        $response->assertDontSee('پرفروش‌ترین محصولات', false);
        $response->assertDontSee('500,000', false);
    }

    #[Test]
    #[TestDox('گزارش فروش محصول حذف‌شده را بی‌صدا از پرفروش‌ها حذف می‌کند (nullSafe)')]
    public function seller_analytics_skips_deleted_products(): void
    {
        $seller = $this->newSeller();
        [$order, $item] = $this->newSellerPaidOrder($seller, 300000);

        // محصول حذف می‌شود — ردیف پرفروش‌ها نباید صفحه را بشکند
        $item->product()->delete();

        $this->actingAs($seller, 'seller');
        $response = $this->get(route('seller.statistics.index'));

        $response->assertOk();
        $response->assertSee('گزارش فروش (۳۰ روز اخیر)', false);
        // کارت‌های جمع همچنان درآمد را نشان می‌دهند (آیتم حذف‌شده فروش است)
        $response->assertSee('300,000', false);
        // ولی جدول پرفروش‌ها خالی است (ردیف محصول نال فیلتر شده)
        $response->assertSee('هنوز فروشی در ۳۰ روز اخیر ثبت نشده است', false);
    }
}
