<?php

namespace Tests\Feature\Extra;

use App\Exports\SellerProductsExport;
use App\Livewire\Admin\Sellers\SellerLeaderboard;
use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\PriceAlert;
use App\Models\Review;
use App\Models\Seller;
use App\Models\SellerDeposit;
use App\Models\SellerInfo;
use App\Models\SellerVariant;
use App\Models\StockMovement;
use App\Models\StockNotify;
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

    /* ================= فاز ۱۰۶-۳) سیگنال‌های تقاضا ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ سیگنال‌های تقاضا را ذخیره می‌کند')]
    public function developer_tab_saves_demand_signals_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorDemandSignals', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('seller_demand_signals', '1'));
        $this->assertFalse(MultiVendorSettings::demandSignalsEnabled());

        Livewire::test(Developer::class)
            ->set('vendorDemandSignals', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('seller_demand_signals', '1'));
    }

    /** درخواست «منتظر موجودی» (StockNotify) روی محصول فروشنده */
    private function newStockNotify(int $productId, array $attributes = []): StockNotify
    {
        return StockNotify::create(array_merge([
            'name' => 'مشتری '.Str::random(4),
            'mobile' => '0913'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'seen' => false,
            'product_id' => $productId,
        ], $attributes));
    }

    /** اشتراک فعالِ «منتظر کاهش قیمت» (PriceAlert) روی محصول فروشنده */
    private function newPriceAlert(int $productId, array $attributes = []): PriceAlert
    {
        return PriceAlert::create(array_merge([
            'mobile' => '0912'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'name' => 'مشتری '.Str::random(4),
            'target_price' => 80000,
            'active' => true,
            'product_id' => $productId,
        ], $attributes));
    }

    #[Test]
    #[TestDox('صفحهٔ سیگنال‌های تقاضا شمارش هر محصول و آخرین سیگنال‌ها را با موبایل ماسک‌شده نشان می‌دهد')]
    public function demand_signals_page_shows_counts_and_products(): void
    {
        $seller = $this->newSeller();

        // محصول A: دو «منتظر موجودی» (یکی جدید) + یک «منتظر کاهش قیمت» با هدف ۸۰٬۰۰۰
        $productA = $this->newProduct(['seller_id' => $seller->id]);
        $this->newStockNotify($productA->id, ['mobile' => '09135550001', 'seen' => false]);
        $this->newStockNotify($productA->id, ['seen' => true]);
        $this->newPriceAlert($productA->id);

        // محصول B: فقط یک اشتراک کاهش قیمت (برای تفکیک شمارش هر محصول)
        $productB = $this->newProduct(['seller_id' => $seller->id]);
        $this->newPriceAlert($productB->id);

        // سیگنال‌های فروشندهٔ دیگر — نباید هیچ ردی دیده شود
        $otherProduct = $this->newProduct(['seller_id' => $this->newSeller()->id]);
        $this->newStockNotify($otherProduct->id, ['mobile' => '09359990007']);
        $this->newPriceAlert($otherProduct->id);

        $this->actingAs($seller, 'seller');
        $response = $this->get(route('seller.demand.signals'));

        $response->assertOk();
        $response->assertSee('سیگنال‌های تقاضا', false);

        // کارت‌ها: ۲ منتظر موجودی + ۲ منتظر کاهش قیمت + ۲ محصول دارای تقاضا
        // (سیگنال‌های فروشندهٔ دیگر در هیچ عددی نمی‌آیند)
        $response->assertSee('مشتریان منتظر موجودی', false);
        $response->assertSee('منتظر کاهش قیمت', false);
        $response->assertSee('تقاضا به‌ازای هر محصول', false);

        // جدول محصولات — هر دو محصول فروشنده با شمارش ردیف‌به‌ردیف
        $response->assertSee($productA->title, false);
        $response->assertSee($productB->title, false);

        // خوراک آخرین سیگنال‌ها: موبایل ماسک می‌شود و قیمت هدف دیده می‌شود
        $response->assertSee('0913***001', false);
        $response->assertDontSee('09135550001', false);
        $response->assertSee('هدف: 80,000 تومان', false);
        $response->assertSee('جدید', false);

        // انزوا: سیگنال‌های فروشندهٔ دیگر (محصول و موبایل) نشت نمی‌کند
        $response->assertDontSee($otherProduct->title, false);
        $response->assertDontSee('09359990007', false);

        // آیتم منوی سایدبار (سوییچ روشن)
        $this->get(route('seller.dashboard'))->assertSee(route('seller.demand.signals'), false);
    }

    #[Test]
    #[TestDox('سوییچ خاموش سیگنال‌های تقاضا: صفحه به داشبورد برمی‌گردد و منو مخفی می‌شود')]
    public function demand_signals_respects_toggle(): void
    {
        $seller = $this->newSeller();

        $product = $this->newProduct(['seller_id' => $seller->id]);
        $this->newStockNotify($product->id);

        $this->actingAs($seller, 'seller');

        option_update('seller_demand_signals', '0');
        Cache::flush();

        $response = $this->get(route('seller.demand.signals'));
        $response->assertRedirect(route('seller.dashboard'));

        // آیتم منو با سوییچ خاموش حذف شود — سنجش URL (پیام toast حاوی نام قابلیت است)
        $this->get(route('seller.dashboard'))->assertDontSee(route('seller.demand.signals'), false);
    }

    /* ================= فاز ۱۰۶-۴) نظرات محصولات ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ دسترسی به نظرات را ذخیره می‌کند')]
    public function developer_tab_saves_reviews_access_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorReviewsAccess', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('seller_reviews_access', '1'));
        $this->assertFalse(MultiVendorSettings::reviewsAccessEnabled());

        Livewire::test(Developer::class)
            ->set('vendorReviewsAccess', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('seller_reviews_access', '1'));
    }

    /** نظری روی محصول فروشنده (rating ۱-۵، suggest yes/no/not_sure، وضعیت‌های سه‌گانه) */
    private function newReview(int $productId, array $attributes = []): Review
    {
        return Review::create(array_merge([
            'user_id' => $this->newUser()->id,
            'product_id' => $productId,
            'title' => 'عنوان نظر '.Str::random(5),
            'body' => 'متن نظر تستی — کیفیت خوب بود و سریع رسید.',
            'rating' => 5,
            'suggest' => 'yes',
            'status' => 'accepted',
            'likes_count' => 2,
            'dislikes_count' => 1,
        ], $attributes));
    }

    #[Test]
    #[TestDox('صفحهٔ نظرات محصولات امتیاز، درصد توصیه و ردیف‌به‌ردیف نظرات همان فروشنده را نشان می‌دهد')]
    public function seller_reviews_page_shows_reviews_and_stats(): void
    {
        $seller = $this->newSeller();

        // محصول A: دو نظر تأییدشده (امتیاز ۵ + ۳، توصیه بله/نه → میانگین ۴ و توصیه ۵۰٪) + یک در انتظار
        $productA = $this->newProduct(['seller_id' => $seller->id]);
        $this->newReview($productA->id, ['rating' => 5, 'suggest' => 'yes', 'likes_count' => 7]);
        $this->newReview($productA->id, ['rating' => 3, 'suggest' => 'no']);
        $this->newReview($productA->id, ['rating' => 4, 'suggest' => 'yes', 'status' => 'pending']);

        // محصول B: یک نظر تأییدشدهٔ دیگر (میانگین کل تأییدشده‌ها: (۵+۳+۴)/۳ = ۴)
        $productB = $this->newProduct(['seller_id' => $seller->id]);
        $this->newReview($productB->id, ['rating' => 4, 'suggest' => 'yes']);

        // نظر فروشندهٔ دیگر — نباید دیده شود
        $otherProduct = $this->newProduct(['seller_id' => $this->newSeller()->id]);
        $this->newReview($otherProduct->id, ['title' => 'نظر محرمانهٔ فروشندهٔ دیگر']);

        $this->actingAs($seller, 'seller');
        $response = $this->get(route('seller.reviews.index'));

        $response->assertOk();
        $response->assertSee('نظرات محصولات', false);

        // کارت‌های آمار: کل ۴ (۳ تأییدشده + ۱ در انتظار) — میانگین ۴ — توصیه ۶۶.۷٪ (۲ از ۳)
        $response->assertSee('کل نظرات ثبت‌شده', false);
        $response->assertSee('میانگین امتیاز', false);
        $response->assertSee('4.0', false);
        $response->assertSee('66.7٪', false);
        $response->assertSee('در انتظار تأیید مدیر', false);

        // ردیف‌به‌ردیف: محصولات فروشنده + ستاره‌ها و وضعیت‌ها
        $response->assertSee($productA->title, false);
        $response->assertSee($productB->title, false);
        $response->assertSee('تأییدشده', false);
        $response->assertSee('در انتظار تأیید', false);
        $response->assertSee('پیشنهاد نمی‌کنم', false);

        // انزوا: نظر فروشندهٔ دیگر نشت نمی‌کند
        $response->assertDontSee('نظر محرمانهٔ فروشندهٔ دیگر', false);
        $response->assertDontSee($otherProduct->title, false);

        // آیتم منوی سایدبار (سوییچ روشن)
        $this->get(route('seller.dashboard'))->assertSee(route('seller.reviews.index'), false);
    }

    #[Test]
    #[TestDox('سوییچ خاموش نظرات: صفحه به داشبورد برمی‌گردد و منو مخفی می‌شود')]
    public function seller_reviews_respects_toggle(): void
    {
        $seller = $this->newSeller();

        $product = $this->newProduct(['seller_id' => $seller->id]);
        $this->newReview($product->id);

        $this->actingAs($seller, 'seller');

        option_update('seller_reviews_access', '0');
        Cache::flush();

        $response = $this->get(route('seller.reviews.index'));
        $response->assertRedirect(route('seller.dashboard'));

        // آیتم منو با سوییچ خاموش حذف شود
        $this->get(route('seller.dashboard'))->assertDontSee(route('seller.reviews.index'), false);
    }

    /* ================= فاز ۱۰۶-۵) مرکز موجودی ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ مرکز موجودی را ذخیره می‌کند')]
    public function developer_tab_saves_inventory_center_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorInventoryCenter', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('seller_inventory_center', '1'));
        $this->assertFalse(MultiVendorSettings::inventoryCenterEnabled());

        Livewire::test(Developer::class)
            ->set('vendorInventoryCenter', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('seller_inventory_center', '1'));
    }

    #[Test]
    #[TestDox('مرکز موجودی: وضعیت ناموجود/کم‌موجود ردیف‌به‌ردیف + تاریخچهٔ حرکت انبار')]
    public function inventory_center_shows_stock_status_and_movements(): void
    {
        $seller = $this->newSeller();

        // سه تنوع با عنوان یکتا: ناموجود (۰)، کم‌موجود (۲)، سالم (۷) — مرتب بحرانی‌اول
        $productOut = $this->newProduct(['seller_id' => $seller->id, 'title' => 'محصول بحرانی فاز ۱۰۶']);
        $this->newPrice($productOut, ['stock' => 0]);

        $productLow = $this->newProduct(['seller_id' => $seller->id, 'title' => 'محصول در حال اتمام فاز ۱۰۶']);
        $this->newPrice($productLow, ['stock' => 2]);

        $productOk = $this->newProduct(['seller_id' => $seller->id, 'title' => 'محصول سالم فاز ۱۰۶']);
        $priceOk = $this->newPrice($productOk, ['stock' => 7]);

        // حرکت انبار واقعی (خروج ۵ عدد — سرویس فاز ۹۳ همین شکل می‌نویسد)
        StockMovement::create([
            'product_id' => $productOk->id,
            'price_id' => $priceOk->id,
            'type' => 'out',
            'quantity' => 5,
            'before_stock' => 12,
            'after_stock' => 7,
            'description' => 'خروج برای سفارش تست',
        ]);

        // تنوع و حرکت فروشندهٔ دیگر — نباید دیده شود
        $otherProduct = $this->newProduct(['seller_id' => $this->newSeller()->id, 'title' => 'محصول محرمانهٔ فروشندهٔ دیگر']);
        $this->newPrice($otherProduct, ['stock' => 0]);
        StockMovement::create([
            'product_id' => $otherProduct->id,
            'type' => 'out',
            'quantity' => 3,
            'before_stock' => 3,
            'after_stock' => 0,
            'description' => 'حرکت محرمانهٔ فروشندهٔ دیگر',
        ]);

        $this->actingAs($seller, 'seller');
        $response = $this->get(route('seller.inventory.index'));

        $response->assertOk();
        $response->assertSee('مرکز موجودی', false);

        // کارت‌ها: ۳ تنوع، ۱ ناموجود، ۱ کم‌موجود، ۱ حرکت ۳۰ روز
        $response->assertSee('کل تنوع‌های قیمت', false);
        $response->assertSee('ناموجود', false);
        $response->assertSee('کم‌موجود (۱ تا ۲ عدد)', false);
        $response->assertSee('حرکت انبار ۳۰ روز اخیر', false);

        // جدول وضعیت: هر سه محصول فروشنده دیده می‌شوند
        $response->assertSee('وضعیت موجودی تنوع‌ها', false);
        $response->assertSee('محصول بحرانی فاز ۱۰۶', false);
        $response->assertSee('محصول در حال اتمام فاز ۱۰۶', false);
        $response->assertSee('محصول سالم فاز ۱۰۶', false);

        // مرتب بحرانی‌تر-اول: ردیف ناموجود قبل از ردیف سالم (عنوان‌های یکتا — مقاوم)
        $this->assertLessThan(
            mb_strpos($response->getContent(), 'محصول سالم فاز ۱۰۶'),
            mb_strpos($response->getContent(), 'محصول بحرانی فاز ۱۰۶')
        );

        // تاریخچهٔ حرکت انبار: نوع + موجودی پس از حرکت
        $response->assertSee('آخرین حرکت‌های انبار', false);
        $response->assertSee('خروج از انبار', false);
        $response->assertSee('خروج برای سفارش تست', false);

        // انزوا: تنوع ناموجود و حرکت فروشندهٔ دیگر نشت نمی‌کند
        $response->assertDontSee('محصول محرمانهٔ فروشندهٔ دیگر', false);
        $response->assertDontSee('حرکت محرمانهٔ فروشندهٔ دیگر', false);

        // آیتم منوی سایدبار (سوییچ روشن)
        $this->get(route('seller.dashboard'))->assertSee(route('seller.inventory.index'), false);
    }

    #[Test]
    #[TestDox('سوییچ خاموش مرکز موجودی: صفحه به داشبورد برمی‌گردد و منو مخفی می‌شود')]
    public function inventory_center_respects_toggle(): void
    {
        $seller = $this->newSeller();

        $product = $this->newProduct(['seller_id' => $seller->id]);
        $this->newPrice($product, ['stock' => 0]);

        $this->actingAs($seller, 'seller');

        option_update('seller_inventory_center', '0');
        Cache::flush();

        $response = $this->get(route('seller.inventory.index'));
        $response->assertRedirect(route('seller.dashboard'));

        // آیتم منو با سوییچ خاموش حذف شود
        $this->get(route('seller.dashboard'))->assertDontSee(route('seller.inventory.index'), false);
    }

    /* ================= فاز ۱۰۶-۶) خروجی اکسل محصولات ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ خروجی اکسل محصولات را ذخیره می‌کند')]
    public function developer_tab_saves_products_export_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorProductsExport', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('seller_products_export', '1'));
        $this->assertFalse(MultiVendorSettings::productsExportEnabled());

        Livewire::test(Developer::class)
            ->set('vendorProductsExport', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('seller_products_export', '1'));
    }

    #[Test]
    #[TestDox('خروجی اکسل محصولات: مبنای SellerVariant، ردیف/قیمت درست، دکمهٔ گیت‌شده و دانلود xlsx')]
    public function products_export_downloads_and_respects_toggle(): void
    {
        $seller = $this->newSeller();

        // دو محصول از مبنای SellerVariant (همان apiIndex «مدیریت محصولات»)
        // توجه: newProduct خودش یک قیمت پیش‌فرض ۱۰۰٬۰۰۰ می‌سازد؛ برای تشخیص مین،
        // قیمت‌های صریحِ زیر ۱۰۰ هزار اضافه می‌کنیم
        $productA = $this->newProduct(['seller_id' => $seller->id, 'title' => 'محصول اکسل الف']);
        $priceA = $this->newPrice($productA, ['price' => 55000, 'regular_price' => 55000, 'discount_price' => 55000]);
        $this->newPrice($productA, ['price' => 22000, 'regular_price' => 22000, 'discount_price' => 22000, 'stock' => 3]);

        $productB = $this->newProduct(['seller_id' => $seller->id, 'title' => 'محصول اکسل ب']);
        $this->newPrice($productB, ['stock' => 4]);

        // محصول بیرون از مبنای SellerVariant — نباید در خروجی بیاید
        $this->newProduct(['title' => 'محصول غریبهٔ اکسل']);

        SellerVariant::create([
            'seller_id' => $seller->id,
            'product_id' => $productA->id,
            'prices_id' => (string) $priceA->id,
        ]);
        SellerVariant::create([
            'seller_id' => $seller->id,
            'product_id' => $productB->id,
            'prices_id' => '',
        ]);

        $this->actingAs($seller, 'seller');

        // دکمهٔ خروجی در صفحهٔ محصولات (سوییچ روشن)
        $this->get(route('seller.products.index'))->assertSee(route('seller.products.export'), false);

        // محتوای خروجی: فقط محصولات مبنای SellerVariant + قیمت مؤثر و موجودی
        $html = (new SellerProductsExport($seller->id))->view()->render();
        $this->assertStringContainsString('محصول اکسل الف', $html);
        $this->assertStringContainsString('محصول اکسل ب', $html);
        $this->assertStringContainsString('22,000', $html);   // مین قیمت مؤثر محصول الف (۱۰۰ پیش‌فرض/۵۵/۲۲)
        $this->assertStringNotContainsString('محصول غریبهٔ اکسل', $html);

        // دانلود واقعی xlsx (امضای PK)
        $response = $this->get(route('seller.products.export'));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', (string) $response->headers->get('Content-Type'));
        $file = $response->getFile();
        $bytes = (string) file_get_contents($file->getPathname());
        $this->assertSame('PK', substr($bytes, 0, 2));

        // سوییچ خاموش: دکمه حذف و دانلود ریدایرکت می‌شود
        option_update('seller_products_export', '0');
        Cache::flush();

        $this->get(route('seller.products.index'))->assertDontSee(route('seller.products.export'), false);

        $response = $this->get(route('seller.products.export'));
        $response->assertRedirect(route('seller.products.index'));
    }

    /* ================= فاز ۱۰۶-۷) لیدربورد مقایسهٔ فروشندگان (ادمین) ================= */

    #[Test]
    #[TestDox('تب چندفروشندگی Developer سوییچ لیدربورد مقایسهٔ فروشندگان را ذخیره می‌کند')]
    public function developer_tab_saves_leaderboard_toggle(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->set('vendorEnabled', true)
            ->set('vendorLeaderboard', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('multi_vendor_leaderboard', '1'));
        $this->assertFalse(MultiVendorSettings::leaderboardEnabled());

        Livewire::test(Developer::class)
            ->set('vendorLeaderboard', true)
            ->call('saveMultiVendor');

        $this->assertSame('1', option('multi_vendor_leaderboard', '1'));
    }

    #[Test]
    #[TestDox('لیدربورد: رتبه‌بندی درآمد بر مبنای order_items.seller_id با فیلتر دوره ۳۰/۹۰/همه')]
    public function leaderboard_ranks_sellers_by_revenue_with_period(): void
    {
        $this->newCreator();

        $sellerA = $this->newSeller();
        $sellerB = $this->newSeller();

        SellerInfo::where('seller_id', $sellerA->id)->update(['business_name' => 'فروشندهٔ الف لیدربورد']);
        SellerInfo::where('seller_id', $sellerB->id)->update(['business_name' => 'فروشندهٔ ب لیدربورد']);

        // فروشندهٔ الف: سفارش ۵۰۰٬۰۰۰ (امروز) + سفارش ۳۰۰٬۰۰۰ (۴۵ روز پیش)
        [$orderOld] = $this->newSellerPaidOrder($sellerA, 300000);
        $orderOld->forceFill(['created_at' => now()->subDays(45)])->save();

        $this->newSellerPaidOrder($sellerA, 500000);

        // فروشندهٔ ب: یک سفارش ۲۵۰٬۰۰۰
        $this->newSellerPaidOrder($sellerB, 250000);

        // آیتم بدون فروشنده (فروش مستقیم فروشگاه) — نباید در رتبه‌بندی بیاید
        $this->newPaidOrder($this->newUser(), $this->newProduct(), [], ['price' => 900000, 'real_price' => 900000]);

        // دورهٔ پیش‌فرض ۳۰ روز: الف = ۵۰۰٬۰۰۰ (سفارش ۴۵ روزه خارج) و صدرنشین
        $component = Livewire::test(SellerLeaderboard::class);
        $html = $component->html();
        $this->assertStringContainsString('فروشندهٔ الف لیدربورد', $html);
        $this->assertStringContainsString('فروشندهٔ ب لیدربورد', $html);
        $this->assertStringContainsString('500,000', $html);
        $this->assertStringContainsString('250,000', $html);
        $this->assertStringNotContainsString('300,000', $html);
        $this->assertStringNotContainsString('900,000', $html);
        // ترتیب: الف قبل از ب
        $this->assertLessThan(
            mb_strpos($html, 'فروشندهٔ ب لیدربورد'),
            mb_strpos($html, 'فروشندهٔ الف لیدربورد')
        );

        // دورهٔ همهٔ زمان‌ها: الف = ۸۰۰٬۰۰۰، میانگین هر سفارش ۴۰۰٬۰۰۰
        $allHtml = Livewire::test(SellerLeaderboard::class)
            ->set('period', 'all')
            ->html();
        $this->assertStringContainsString('800,000', $allHtml);
        $this->assertStringContainsString('400,000', $allHtml);
    }

    #[Test]
    #[TestDox('سوییچ خاموش لیدربورد: ریدایرکت به لیست فروشندگان و آیتم منو گیت‌شده')]
    public function leaderboard_respects_toggle(): void
    {
        $this->newCreator();

        // آیتم منو با کلید enabled فاز ۸۳ گیت شده است
        $item = null;
        foreach (config('admin-menu.groups', []) as $group) {
            foreach ($group['items'] ?? [] as $child) {
                foreach ($child['children'] ?? [] as $leaf) {
                    if (($leaf['route'] ?? null) === 'np.sellers.leaderboard') {
                        $item = $leaf;
                    }
                }
            }
        }
        $this->assertNotNull($item, 'آیتم منوی لیدربورد باید در admin-menu موجود باشد.');
        $this->assertSame('multi_vendor_leaderboard', $item['enabled'] ?? null);

        // سوییچ خاموش → ریدایرکت به لیست فروشندگان
        option_update('multi_vendor_leaderboard', '0');
        Cache::flush();

        Livewire::test(SellerLeaderboard::class)
            ->assertRedirect(route('np.sellers.index'));
    }
}
