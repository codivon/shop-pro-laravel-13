<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های سبد خرید:
 *  - افزودن تک و چند محصول
 *  - افزایش/کاهش/حذف تعداد
 *  - ماندگاری سبد (کاربر لاگین و مهمان)
 *  - به‌روزرسانی قیمت در صورت تغییر
 *  - سبد خالی (پیام راهنما)
 */
class CartTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    // =====================================================================
    //  افزودن به سبد
    // =====================================================================

    #[Test]
    public function logged_in_user_can_add_product_to_cart(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();

        $response = $this->actingAs($user, 'web')
            ->post('/cart/' . $product->slug);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $cart = $user->cart()->first();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->products);
        $this->assertEquals(1, $cart->products->first()->pivot->quantity);
    }

    #[Test]
    public function adding_same_product_twice_increases_quantity(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();

        $this->actingAs($user, 'web')->post('/cart/' . $product->slug);
        $this->actingAs($user, 'web')->post('/cart/' . $product->slug);

        $cart = $user->cart()->first();
        $this->assertCount(1, $cart->products, 'محصول نباید ردیف تکراری بگیرد');
        $this->assertEquals(2, $cart->products->first()->pivot->quantity);
    }

    #[Test]
    public function user_can_add_multiple_products_to_cart(): void
    {
        $user = $this->newUser();
        $productA = $this->newProduct();
        $productB = $this->newProduct();

        $this->actingAs($user, 'web')->post('/cart/' . $productA->slug);
        $this->actingAs($user, 'web')->post('/cart/' . $productB->slug);

        $cart = $user->cart()->first();
        $this->assertCount(2, $cart->products);
    }

    #[Test]
    public function adding_out_of_stock_product_returns_error(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 0]);
        $price = $product->prices()->first();

        $response = $this->actingAs($user, 'web')
            ->post('/cart/' . $product->slug, ['price_id' => $price->id]);

        $response->assertOk()
            ->assertJsonPath('status', 'error');
        $this->assertStringContainsString('موجودی', $response->json('message'));
    }

    #[Test]
    public function adding_quantity_beyond_stock_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 3]);

        // ۳ بار افزودن با موجودی ۳ مجاز است، تلاش چهارم باید خطا بدهد
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user, 'web')->post('/cart/' . $product->slug)
                ->assertJsonPath('status', 'success');
        }

        $response = $this->actingAs($user, 'web')
            ->post('/cart/' . $product->slug);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('موجودی', $response->json('message'));
    }

    #[Test]
    public function download_product_cannot_be_added_twice(): void
    {
        $user = $this->newUser();
        $product = $this->newDownloadProduct();

        $this->actingAs($user, 'web')->post('/cart/' . $product->slug)
            ->assertJsonPath('status', 'success');

        $response = $this->actingAs($user, 'web')
            ->post('/cart/' . $product->slug);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('قبلا', $response->json('message'));
    }

    // =====================================================================
    //  سبد مهمان
    // =====================================================================

    #[Test]
    public function guest_can_add_product_and_gets_cart_cookie(): void
    {
        $product = $this->newProduct();

        $response = $this->post('/cart/' . $product->slug);

        $response->assertOk()->assertJsonPath('status', 'success');

        // سبد مهمان در دیتابیس با user_id=null ساخته شده
        $guestCarts = Cart::whereNull('user_id')->get();
        $this->assertCount(1, $guestCarts);
        $this->assertCount(1, $guestCarts->first()->products);

        // کوکی cart_id تنظیم شده است
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_contains($c->getName(), 'cart_id'));
        $this->assertNotNull($cookie, 'کوکی cart_id باید ست شود');
    }

    #[Test]
    public function guest_cart_persists_across_requests(): void
    {
        $product = $this->newProduct();

        // درخواست اول: سبد ساخته می‌شود و کوکی صادر می‌شود
        $response = $this->post('/cart/' . $product->slug);
        $cart = Cart::whereNull('user_id')->first();

        // درخواست دوم با همان کوکی (مقدار رمزنگاری‌شده از پاسخ اول)
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => str_contains($c->getName(), 'cart_id'));

        $product2 = $this->newProduct();
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
            ->post('/cart/' . $product2->slug)
            ->assertJsonPath('status', 'success');

        $this->assertCount(2, $cart->fresh()->products, 'سبد مهمان باید بین درخواست‌ها حفظ شود');
    }

    #[Test]
    public function guest_cart_is_separate_from_user_cart(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();

        // مهمان محصول را اضافه می‌کند
        $this->post('/cart/' . $product->slug);

        // همان محصول در سبد کاربر لاگین اضافه می‌شود
        $this->actingAs($user, 'web')->post('/cart/' . $product->slug);

        $this->assertCount(1, Cart::whereNull('user_id')->get()->first()->products);
        $this->assertCount(1, $user->cart->products);
    }

    // =====================================================================
    //  به‌روزرسانی تعداد
    // =====================================================================

    #[Test]
    public function user_can_update_item_quantity(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 20]);
        $cart = $this->addToCart($user, $product, null, 1);

        $pivotId = $cart->products->first()->pivot->id;

        $response = $this->actingAs($user, 'web')
            ->put('/cart', ['product-' . $pivotId => '5']);

        $response->assertOk();
        $this->assertEquals(5, $cart->fresh()->products->first()->pivot->quantity);
    }

    #[Test]
    public function setting_quantity_to_zero_removes_item(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $cart = $this->addToCart($user, $product);

        $pivotId = $cart->products->first()->pivot->id;

        $this->actingAs($user, 'web')
            ->put('/cart', ['product-' . $pivotId => '0']);

        $this->assertCount(0, $cart->fresh()->products);
    }

    #[Test]
    public function updating_quantity_beyond_stock_returns_406_with_error(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 2]);
        $cart = $this->addToCart($user, $product);

        $pivotId = $cart->products->first()->pivot->id;

        $response = $this->actingAs($user, 'web')
            ->put('/cart', ['product-' . $pivotId => '50']);

        $response->assertStatus(406);
        $this->assertNotEmpty($response->json('errors'));
        // تعداد تغییر نکرده باشد
        $this->assertEquals(1, $cart->fresh()->products->first()->pivot->quantity);
    }

    #[Test]
    public function user_can_remove_item_from_cart(): void
    {
        $user = $this->newUser();
        $productA = $this->newProduct();
        $productB = $this->newProduct();

        $cart = $this->addToCart($user, $productA);
        $this->addToCart($user, $productB);

        $pivotA = $cart->products->where('id', $productA->id)->first()->pivot->id;

        $response = $this->actingAs($user, 'web')
            ->delete('/cart/' . $pivotA);

        $response->assertRedirect(route('front.cart'));
        $this->assertCount(1, $cart->fresh()->products);
        $this->assertEquals($productB->id, $cart->fresh()->products->first()->id);
    }

    // =====================================================================
    //  ماندگاری سبد کاربر لاگین
    // =====================================================================

    #[Test]
    public function logged_in_user_cart_persists_across_sessions(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();

        $this->addToCart($user, $product, null, 2);

        // «نشست جدید» — خروج و ورود مجدد
        $this->post('/logout');
        $this->actingAs($user, 'web');

        $cart = get_cart();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->products);
        $this->assertEquals(2, $cart->products->first()->pivot->quantity);
    }

    // =====================================================================
    //  به‌روزرسانی قیمت
    // =====================================================================

    #[Test]
    public function cart_total_reflects_price_change(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['price' => 100000]);
        $price = $product->prices()->first();

        $this->addToCart($user, $product, $price, 2);

        $cart = $user->cart;
        $this->assertEquals(200000, $cart->price);

        // قیمت محصول تغییر می‌کند
        $price->update(['price' => 150000, 'discount_price' => 150000, 'regular_price' => 150000]);
        $price->refresh();

        // جمع سبد باید با قیمت جدید محاسبه شود
        $this->assertEquals(300000, $user->cart->fresh()->price);
    }

    #[Test]
    public function cart_total_uses_discounted_price(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], [
            'price'          => 200000,
            'discount_price' => 180000,
            'regular_price'  => 200000,
            'discount'       => 10,
        ]);
        $price = $product->prices()->first();

        $this->addToCart($user, $product, $price, 1);

        $this->assertEquals(180000, $user->cart->price);
        // تخفیف محصول = ۲۰,۰۰۰ تومان
        $this->assertEquals(20000, $user->cart->totalDiscount() - $user->cart->discountCodeTotal());
    }

    // =====================================================================
    //  سبد خالی
    // =====================================================================

    #[Test]
    public function cart_page_renders_with_guide_message_when_empty(): void
    {
        $user = $this->newUser();

        $response = $this->actingAs($user, 'web')->get('/cart');

        $response->assertStatus(200);
    }

    #[Test]
    public function cart_page_requires_no_authentication(): void
    {
        $this->get('/cart')->assertStatus(200);
    }

    #[Test]
    public function cart_quantity_attribute_sums_all_items(): void
    {
        $user = $this->newUser();
        $productA = $this->newProduct();
        $productB = $this->newProduct();

        $this->addToCart($user, $productA, null, 2);
        $this->addToCart($user, $productB, null, 3);

        $this->assertEquals(5, $user->cart->quantity);
    }
}
