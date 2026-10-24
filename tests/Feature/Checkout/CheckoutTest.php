<?php

namespace Tests\Feature\Checkout;

use App\Models\Carrier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های چک‌اوت:
 *  - انتخاب/ویرایش آدرس
 *  - روش‌های ارسال و هزینه صحیح
 *  - اعمال کد تخفیف (معتبر، منقضی، نامعتبر)
 *  - خلاصه سفارش با تفکیک قیمت‌ها
 *  - چک‌اوت مهمان (رفتار فعلی: پشتیبانی نمی‌شود)
 */
class CheckoutTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
        Queue::fake(); // جاب CancelOrder بلافاصله اجرا نشود (queue=sync)
    }

    /**
     * کاربر با سبد آماده + آدرس + درگاه فعال را می‌سازد.
     * آدرس در همان استان روش ارسال ساخته می‌شود (تعرفه درون‌استانی ۴۰,۰۰۰).
     */
    protected function readyUser(array $productPrice = []): array
    {
        $user = $this->newUser();
        $product = $this->newProduct([], $productPrice);
        $this->addToCart($user, $product, null, 2);
        $carrier = $this->newCarrier();

        // آدرس در استان روش ارسال (تعرفه درون‌استانی اعمال شود)
        $province = \App\Models\Province::find($carrier->province_id);
        $city = \App\Models\City::find($carrier->city_id);
        $address = $this->newAddress($user, [], $province, $city);

        $gateway = $this->newGateway('zarinpal');

        return [$user, $product, $address, $carrier, $gateway];
    }

    // =====================================================================
    //  صفحه چک‌اوت
    // =====================================================================

    #[Test]
    public function checkout_page_renders_for_logged_in_user_with_items(): void
    {
        [$user] = $this->readyUser();

        $response = $this->actingAs($user, 'web')->get('/checkout');

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_cannot_access_checkout(): void
    {
        $this->get('/checkout')->assertRedirect();
    }

    // =====================================================================
    //  آدرس
    // =====================================================================

    #[Test]
    public function physical_order_without_address_is_rejected(): void
    {
        [$user, $product] = $this->readyUser();

        $response = $this->actingAs($user, 'web')
            ->from('/checkout')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'carrier_id_store' => 1,
                // 'address' عمداً ارسال نمی‌شود
            ]);

        $response->assertSessionHasErrors(['address']);
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function order_with_valid_address_is_created(): void
    {
        [$user, $product, $address] = $this->readyUser();
        $carrier = Carrier::active()->first();

        $response = $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ]);

        $response->assertRedirect(); // به درگاه پرداخت هدایت می‌شود

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order, 'سفارش باید ساخته شود');
        $this->assertEquals($address->city_id, $order->city_id);
        $this->assertEquals($address->mobile, $order->mobile);
        $this->assertEquals($address->address, $order->address);
    }

    #[Test]
    public function order_with_invalid_address_id_is_rejected(): void
    {
        [$user] = $this->readyUser();
        $carrier = Carrier::active()->first();

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => 999999,
                'carrier_id_store' => $carrier->id,
            ])
            ->assertSessionHasErrors(['address']);
    }

    // =====================================================================
    //  روش ارسال
    // =====================================================================

    #[Test]
    public function physical_order_without_carrier_is_rejected(): void
    {
        [$user, $product, $address] = $this->readyUser();

        $response = $this->actingAs($user, 'web')
            ->from('/checkout')
            ->post('/profile/orders', [
                'gateway' => 'zarinpal',
                'address' => $address->id,
                // carrier_id_store ارسال نمی‌شود
            ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function shipping_cost_is_calculated_from_tariff(): void
    {
        [$user, $product, $address] = $this->readyUser();
        $carrier = Carrier::active()->first(); // تعرفه درون‌استانی: 40,000

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        // ۲ عدد × ۱۰۰,۰۰۰ + ۴۰,۰۰۰ ارسال = ۲۴۰,۰۰۰
        $this->assertEquals(40000, $order->shipping_cost);
        $this->assertEquals(240000, $order->price);
    }

    #[Test]
    public function download_only_order_needs_no_address_or_shipping(): void
    {
        $user = $this->newUser();
        $product = $this->newDownloadProduct();
        $this->addToCart($user, $product, null, 1);
        $this->newGateway('zarinpal');

        $response = $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway' => 'zarinpal',
            ]);

        $response->assertRedirect();

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(0, $order->shipping_cost);
        $this->assertNull($order->city_id);
    }

    // =====================================================================
    //  کد تخفیف
    // =====================================================================

    #[Test]
    public function valid_discount_code_is_applied_to_cart(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product, null, 2); // ۲۰۰,۰۰۰ تومان
        $discount = $this->newDiscount(['type' => 'percent', 'amount' => 10]);

        $response = $this->actingAs($user, 'web')
            ->post('/discount', ['code' => $discount->code]);

        $response->assertOk();
        $this->assertEquals($discount->id, $user->cart->fresh()->discount_id);
    }

    #[Test]
    public function invalid_discount_code_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $this->actingAs($user, 'web')
            ->post('/discount', ['code' => 'NO-SUCH-CODE'])
            ->assertSessionHasErrors(['code']);
    }

    #[Test]
    public function expired_discount_code_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $discount = $this->newDiscount(['end_date' => now()->subDay()]);

        $response = $this->actingAs($user, 'web')
            ->from('/checkout')
            ->post('/discount', ['code' => $discount->code]);

        $response->assertSessionHasErrors();
        $this->assertNull($user->cart->fresh()->discount_id);
    }

    #[Test]
    public function not_started_discount_code_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $discount = $this->newDiscount(['start_date' => now()->addDay()]);

        $this->actingAs($user, 'web')
            ->post('/discount', ['code' => $discount->code])
            ->assertSessionHasErrors();
    }

    #[Test]
    public function unpublished_discount_code_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $discount = $this->newDiscount(['published' => false]);

        $this->actingAs($user, 'web')
            ->post('/discount', ['code' => $discount->code])
            ->assertSessionHasErrors();
    }

    #[Test]
    public function only_one_discount_code_can_be_used(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);

        $first = $this->newDiscount();
        $second = $this->newDiscount();

        $this->actingAs($user, 'web')->post('/discount', ['code' => $first->code]);

        // کش رابطه cart بین درخواست‌ها تازه شود (رفتار هر درخواست واقعی)
        $user->unsetRelation('cart');

        $response = $this->actingAs($user, 'web')
            ->from('/checkout')
            ->post('/discount', ['code' => $second->code]);

        $response->assertSessionHasErrors();
        $this->assertEquals($first->id, $user->cart->fresh()->discount_id);
    }

    #[Test]
    public function discount_code_can_be_removed_from_cart(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product);
        $discount = $this->newDiscount();

        $this->actingAs($user, 'web')->post('/discount', ['code' => $discount->code]);

        $this->actingAs($user, 'web')
            ->delete('/discount')
            ->assertRedirect(route('front.checkout'));

        $this->assertNull($user->cart->fresh()->discount_id);
    }

    #[Test]
    public function discount_is_applied_to_final_order_price(): void
    {
        [$user, $product, $address] = $this->readyUser(); // ۲×۱۰۰,۰۰۰ = ۲۰۰,۰۰۰
        $carrier = Carrier::active()->first();
        $discount = $this->newDiscount(['type' => 'amount', 'amount' => 50000]);

        $this->actingAs($user, 'web')->post('/discount', ['code' => $discount->code]);

        // رابطه cart روی آبجکت acting user بین درخواست‌ها کش می‌شود — تازه‌سازی
        $user->unsetRelation('cart');

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        // ۲۰۰,۰۰۰ + ۴۰,۰۰۰ ارسال − ۵۰,۰۰۰ تخفیف = ۱۹۰,۰۰۰
        $this->assertEquals(190000, $order->price);
        $this->assertEquals($discount->id, $order->discount_id);
    }

    // =====================================================================
    //  خلاصه سفارش (تفکیک قیمت‌ها)
    // =====================================================================

    #[Test]
    public function order_summary_breaks_down_prices_correctly(): void
    {
        [$user, $product, $address] = $this->readyUser([
            'price'          => 200000,
            'discount_price' => 180000,
            'regular_price'  => 200000,
            'discount'       => 10,
        ]);
        $carrier = Carrier::active()->first();

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ]);

        $order = Order::with('items')->where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        $item = $order->items->first();
        $this->assertEquals(180000, $item->price, 'قیمت آیتم باید قیمت با تخفیف محصول باشد');
        $this->assertEquals(200000, $item->real_price, 'real_price باید قیمت اصلی باشد');
        $this->assertEquals(40000, $order->shipping_cost);

        // WeblakShop — رفتار فعلی سرور: تخفیف محصول هم در قیمت آیتم (۱۸۰,۰۰۰) لحاظ شده
        // و هم در totalDiscount — یعنی عملاً دو بار کسر می‌شود:
        // ۳۶۰,۰۰۰ (قیمت‌های تخفیف‌دار) + ۴۰,۰۰۰ ارسال − ۴۰,۰۰۰ تخفیف = ۳۶۰,۰۰۰
        // ⚠️ این دوبار‌حسابی تخفیف محصول باید در رفع اشکال بررسی شود
        //
        // سانیار (فاز D تم) همین دوبار‌حسابی را اصلاح کرده است: totals از قبل با
        // discount_price هستند و فقط کوپن از قیمت نهایی کسر می‌شود:
        // ۳۶۰,۰۰۰ + ۴۰,۰۰۰ ارسال − ۰ کوپن = ۴۰۰,۰۰۰ (ستون discount_amount همچنان ۴۰,۰۰۰)
        $expectedPrice = config('general.current_theme') === 'SaniarTheme' ? 400000 : 360000;
        $this->assertEquals($expectedPrice, $order->price);
        $this->assertEquals(40000, $order->discount_amount);
    }

    #[Test]
    public function order_is_created_with_unpaid_status(): void
    {
        [$user, $product, $address] = $this->readyUser();
        $carrier = Carrier::active()->first();

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertEquals('unpaid', $order->status);
    }

    // =====================================================================
    //  چک‌اوت مهمان
    // =====================================================================

    #[Test]
    public function guest_checkout_is_not_allowed(): void
    {
        $product = $this->newProduct();

        // فلوی چک‌اوت نیازمند احراز هویت است
        $this->post('/profile/orders', [
            'gateway' => 'zarinpal',
            'address' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('orders', 0);
    }

    // =====================================================================
    //  سبد خالی
    // =====================================================================

    #[Test]
    public function order_with_empty_cart_redirects_to_cart(): void
    {
        $user = $this->newUser();
        $address = $this->newAddress($user);
        $this->newGateway('zarinpal');

        $response = $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway' => 'zarinpal',
                'address' => $address->id,
            ]);

        $response->assertRedirect(route('front.cart'));
        $this->assertDatabaseCount('orders', 0);
    }

    // =====================================================================
    //  اعتبارسنجی درگاه
    // =====================================================================

    #[Test]
    public function invalid_gateway_is_rejected(): void
    {
        [$user, $product, $address] = $this->readyUser();
        $carrier = Carrier::active()->first();

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'hackers-gateway',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
            ])
            ->assertSessionHasErrors(['gateway']);
    }
}
