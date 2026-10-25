<?php

namespace Tests\Feature\Extra;

use App\Jobs\ProcessPriceAlerts;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Support\ShopTestSetup;

/**
 * فاز ۸۱ — شخصی‌سازی/غیرفعال‌سازی هشدار کاهش قیمت + پیش‌فروشِ تنوع‌محور.
 *
 * پوشش:
 *  - هشدار کاهش قیمت: غیرفعال‌سازی از تنظیمات (بسته‌شدن اشتراک + توقف پردازش)،
 *    اعلان database فروشگاه (PriceAlertsSent) به مدیران بعد از پردازش
 *  - پیش‌فروش: محصول چندتنوعی با همهٔ موجودی‌های صفر (سناریوی کاربر: ۳ تنوع ناموجود)،
 *    نمایش قیمت به‌جای «ناموجود»، افزودن تنوع ناموجود به سبد، بلاک قیمت منتشرنشده،
 *    سقف cart_min/cart_max = ۱، و بسته‌شدن کامل قابلیت با dt_pre_order_status=0
 */
class Phase81Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    protected function tearDown(): void
    {
        // برگرداندن کلیدهای سراسری به پیش‌فرض تا روی تست‌های دیگر اثر نگذارد
        option_update('dt_price_alert_status', '1');
        option_update('dt_pre_order_status', '1');
        Cache::flush();

        parent::tearDown();
    }

    /** محصول پیش‌فروش با ۳ تنوع، همه با موجودی صفر (سناریوی دقیق کاربر) */
    private function newZeroStockVariationPreOrderProduct(): Product
    {
        $product = $this->newProduct([
            'pre_order'  => true,
            'price_type' => 'multiple-price',
        ], ['stock' => 0]);

        $product->prices()->first()->delete();

        $this->newPrice($product, ['price' => 120000, 'discount_price' => 120000, 'regular_price' => 120000, 'stock' => 0]);
        $this->newPrice($product, ['price' => 150000, 'discount_price' => 150000, 'regular_price' => 150000, 'stock' => 0]);
        $this->newPrice($product, ['price' => 180000, 'discount_price' => 180000, 'regular_price' => 180000, 'stock' => 0]);

        return $product->fresh();
    }

    /* ================= هشدار کاهش قیمت ================= */

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabling_price_alert_blocks_subscription_endpoint()
    {
        $product = $this->newProduct();

        option_update('dt_price_alert_status', '0');
        Cache::flush();

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09121234567',
        ])->assertNotFound();

        $this->assertEquals(0, PriceAlert::count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabling_price_alert_stops_alert_processing()
    {
        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        $alert = PriceAlert::create([
            'product_id'   => $product->id,
            'mobile'       => '09121234567',
            'target_price' => 100000,
            'active'       => true,
        ]);

        option_update('dt_price_alert_status', '0');
        Cache::flush();

        // حتی با فراخوانی مستقیم Job، قابلیتِ غیرفعال چیزی پردازش نمی‌کند
        (new ProcessPriceAlerts($product->id))->handle();

        $this->assertTrue($alert->fresh()->active, 'با قابلیتِ غیرفعال، هشدار باید فعال بماند');
        $this->assertNull($alert->fresh()->notified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_admins_receive_database_notification_after_alerts_processed()
    {
        \App\Models\Admin::create([
            'first_name' => 'مدیر',
            'last_name'  => 'قیمتی',
            'username'   => 'price-admin-' . \Illuminate\Support\Str::random(6),
            'level'      => 'admin',
            'password'   => \Illuminate\Support\Facades\Hash::make('admin-secret'),
        ]);

        $product = $this->newProduct([], [
            'price'          => 100000,
            'discount_price' => 100000,
            'regular_price'  => 100000,
            'stock'          => 10,
        ]);

        PriceAlert::create([
            'product_id'   => $product->id,
            'mobile'       => '09121234567',
            'target_price' => 100000,
            'active'       => true,
        ]);

        // افت قیمت مؤثر → Observer در صف sync فوراً Job را رانده است
        $product->prices()->first()->update([
            'price'          => 80000,
            'discount_price' => 80000,
            'regular_price'  => 80000,
        ]);

        $admin = \App\Models\Admin::where('level', 'admin')->latest('id')->first();

        $this->assertDatabaseHas('notifications', [
            'type' => 'PriceAlertsSent',
        ]);

        $notification = $admin->notifications()->where('type', 'PriceAlertsSent')->latest()->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString((string) $product->title, $notification->data['message']);
        $this->assertEquals($product->id, $notification->data['product_id']);
    }

    // فاز ۸۲ — فیلدهای شخصی‌سازی هشدار کاهش قیمت/پیش‌فروش از تنظیمات قالب به
    // صفحهٔ «تنظیمات دیگر» سایت منتقل شدند؛ پوشش تستی به Phase82Test منتقل شد.

    /* ================= پیش‌فروش چندتنوعی ================= */

    #[\PHPUnit\Framework\Attributes\Test]
    public function zero_stock_variation_pre_order_product_shows_price_instead_of_out_of_stock()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();

        // کارت‌ها و API: به‌جای «ناموجود»، قیمت ارزان‌ترین تنوع منتشرشده
        $this->assertNotEquals('ناموجود', $product->getLowestPrice());
        $this->assertEquals(120000, (float) $product->getLowestPrice(true));

        // فروش‌پذیری: بدون هیچ موجودی، قابل پیش‌خرید
        $this->assertTrue($product->addableToCart());
        $this->assertTrue($product->isPreOrder());
        $this->assertEquals(3, $product->saleablePrices(true)->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function zero_stock_variation_of_pre_order_product_is_addable_to_web_cart()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();
        $price   = $product->prices()->where('price', 150000)->first();

        $this->post(route('front.cart.store', ['product' => $product->slug]), [
            'price_id' => $price->id,
        ])->assertJson(['status' => 'success']);

        $cart = \App\Models\Cart::query()->latest('id')->first();

        $this->assertTrue(
            $cart->products()->wherePivot('price_id', $price->id)->exists(),
            'تنوع ناموجودِ پیش‌فروش باید قابل پیش‌خرید باشد'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unpublished_price_of_pre_order_product_is_not_buyable()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();

        $this->newPrice($product, ['price' => 200000, 'discount_price' => 200000, 'regular_price' => 200000, 'stock' => 0, 'published' => false]);

        $unpublished = $product->prices()->where('published', false)->first();

        $this->post(route('front.cart.store', ['product' => $product->slug]), [
            'price_id' => $unpublished->id,
        ])->assertJson([
            'status'  => 'error',
            'message' => 'قیمتی برای این محصول یافت نشد',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cart_quantity_helpers_cap_pre_order_to_one()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();
        $price   = $product->prices()->first();

        // با موجودی صفر، حداقل و حداکثر تعداد مجاز در سبد = ۱
        $this->assertEquals(1, cart_min($price));
        $this->assertEquals(1, cart_max($price));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function disabling_pre_order_from_settings_restores_out_of_stock_behavior()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();

        option_update('dt_pre_order_status', '0');
        Cache::flush();

        $this->assertFalse($product->isPreOrder(), 'با غیرفعال‌بودن قابلیت، تیک پیش‌فروش باید بی‌اثر شود');
        $this->assertFalse($product->addableToCart());
        $this->assertEquals('ناموجود', $product->getLowestPrice());
        $this->assertEquals(0, $product->saleablePrices()->count(), 'بدون پیش‌فروش، فیلتر موجودی برمی‌گردد');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pre_order_product_page_resolves_selected_price_from_zero_stock_variations()
    {
        $product = $this->newZeroStockVariationPreOrderProduct();

        // شبیه‌سازی همان resolve ای که ProductController@show انجام می‌دهد
        $selected = $product->getPrices()->first()
            ?? ($product->isPreOrder() ? $product->saleablePrices(true)->first() : null);

        $this->assertNotNull($selected, 'صفحهٔ محصول پیش‌فروش باید قیمت منتخب داشته باشد (نه شاخهٔ ناموجود)');
        $this->assertEquals(120000, (float) $selected->discountPrice());
    }
}
