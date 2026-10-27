<?php

namespace Tests\Feature\Extra;

use App\Models\Seller;
use App\Models\SellerInfo;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
}
