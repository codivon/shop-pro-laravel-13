<?php

namespace Tests\Feature\Extra;

use App\Models\Apikey;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Shetabit\Multipay\Receipt;
use Shetabit\Multipay\RedirectionForm;
use Shetabit\Payment\Facade\Payment;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۵ — تکمیل API v1: تسویه‌حساب + verify بدون نشست + جست‌وجو + فیلترها
 * --------------------------------------------------------------------------
 *  • GET  /api/v1/checkout  → خلاصهٔ چک‌اوت (گروه‌ها/ارسال/درگاه‌ها/آدرس‌ها)
 *  • POST /api/v1/checkout  → ثبت سفارش (کیف پول/درگاه شتابی) با قواعد وب
 *  • GET|POST /api/v1/payments/verify/{token} → تأیید درگاه بدون نشست
 *  • GET  /api/v1/search → جست‌وجوی یکپارچه
 *  • GET  /api/v1/products/filters → فرادادهٔ فیلترها
 *  • scopeApiFilter: min_price + رفع تکرار in_stock + فیلتر واقعی تخفیف
 */
class Phase95Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
        Queue::fake(); // جاب CancelOrder بلافاصله اجرا نشود (queue=sync)
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    /* ================= کمکی‌ها ================= */

    private function apiKey(): string
    {
        Apikey::firstOrCreate(['key' => 'test-api-key-phase95'], ['is_active' => true]);

        return 'test-api-key-phase95';
    }

    private function apiHeaders(): array
    {
        return ['x-api-key' => $this->apiKey()];
    }

    /**
     * کاربر آمادهٔ چک‌اوت: محصول فیزیکی ۲عددی ×۱۰۰,۰۰۰ + روش ارسال درون‌استانی
     * ۴۰,۰۰۰ + آدرس در همان استان + درگاه زرین‌پال.
     */
    private function readyUser(array $priceAttributes = [], array $productAttributes = []): array
    {
        $user = $this->newUser();
        $product = $this->newProduct($productAttributes, $priceAttributes);
        $this->addToCart($user, $product, null, 2);
        $carrier = $this->newCarrier();

        $province = \App\Models\Province::find($carrier->province_id);
        $city = \App\Models\City::find($carrier->city_id);
        $address = $this->newAddress($user, [], $province, $city);

        $gateway = $this->newGateway('zarinpal');

        return [$user, $product, $address, $carrier, $gateway];
    }

    private function chargeWallet($user, int $amount): void
    {
        $wallet = $user->getWallet();

        $wallet->histories()->create([
            'type'        => 'deposit',
            'amount'      => $amount,
            'description' => 'شارژ تست',
            'source'      => 'user',
            'status'      => 'success',
        ]);
    }

    /* =====================================================================
     |  ۱) احراز هویت
     * ===================================================================== */

    #[Test]
    public function checkout_post_requires_authentication(): void
    {
        $this->postJson('/api/v1/checkout', [
            'gateway' => 'wallet',
        ], $this->apiHeaders())->assertStatus(401);
    }

    /* =====================================================================
     |  ۲) خلاصهٔ چک‌اوت
     * ===================================================================== */

    #[Test]
    public function get_checkout_returns_full_summary(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();
        $this->chargeWallet($user, 150000);
        $this->newGateway('zarinpal');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/checkout', $this->apiHeaders());

        $response->assertOk();

        $result = $response->json('result');

        // گروه فروشگاه با روش ارسال درون‌استانی ۴۰,۰۰۰
        $this->assertCount(1, $result['groups']);
        $this->assertEquals('store', $result['groups'][0]['group_id']);
        $this->assertTrue($result['groups'][0]['is_store']);

        $carrierIds = collect($result['groups'][0]['carriers'])->pluck('id');
        $this->assertContains($carrier->id, $carrierIds);

        $groupCarrier = collect($result['groups'][0]['carriers'])->firstWhere('id', $carrier->id);
        $this->assertEquals(40000, $groupCarrier['shipping_cost']);

        // جمع‌ها: ۲×۱۰۰,۰۰۰ فیزیکی + ۴۰,۰۰۰ ارسال
        $this->assertEquals(200000, $result['totals']['physical_total']);
        $this->assertEquals(40000, $result['totals']['shipping_total']);
        $this->assertEquals(240000, $result['totals']['final_price']);

        // کیف پول + درگاه فعال + آدرس‌ها
        $this->assertEquals(150000, $result['payment']['wallet']['balance']);
        $this->assertContains('zarinpal', collect($result['payment']['gateways'])->pluck('key')->all());
        $this->assertContains($address->id, collect($result['addresses'])->pluck('id')->all());

        // آیتم‌ها با نوع و قیمت
        $this->assertEquals('physical', $result['items'][0]['type']);
        $this->assertEquals(100000, $result['items'][0]['price']);
        $this->assertEquals(1000, $result['weight']); // ۲×۵۰۰ گرم
    }

    #[Test]
    public function get_checkout_with_empty_cart_is_rejected(): void
    {
        $user = $this->newUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/checkout', $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('سبد خرید', (string) $response->json('message'));
    }

    /* =====================================================================
     |  ۳) پرداخت با کیف پول
     * ===================================================================== */

    #[Test]
    public function wallet_checkout_pays_order_and_clears_cart(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();
        $this->chargeWallet($user, 500000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(201);
        $this->assertTrue($response->json('result.paid'));
        $this->assertEquals('paid', $response->json('result.order.status'));

        // سفارش پرداخت‌شده (paid_at در این اسکیمای دیتابیس ستون ندارد — وب هم همین است)
        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(240000, $order->price); // ۲۰۰,۰۰۰ + ۴۰,۰۰۰ ارسال

        // برداشت کیف پول
        $wallet = $user->getWallet();
        $this->assertDatabaseHas('wallet_histories', [
            'wallet_id' => $wallet->id,
            'type'      => 'withdraw',
            'amount'    => 240000,
            'status'    => 'success',
        ]);
        $this->assertEquals(260000, $wallet->fresh()->balance());

        // آیتم‌ها با روش ارسال و هزینه و ویژگی‌ها
        $item = $order->items->first();
        $this->assertEquals($carrier->id, $item->carrier_id);
        $this->assertEquals(40000, $item->shipping_cost);
        $this->assertNotNull($item->attributes);
        $this->assertEquals(100000, $item->price);
        $this->assertEquals(100000, $item->real_price);

        // پاک‌سازی سبد
        $cart = $user->cart->fresh();
        $this->assertEquals(0, $cart->products()->count());
        $this->assertNull($cart->discount_id);

        // اثر ایونت OrderPaid: شمارندهٔ فروش محصول
        $this->assertEquals(2, $product->fresh()->sell);
    }

    #[Test]
    public function wallet_insufficient_balance_is_rejected_before_order(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();
        $this->chargeWallet($user, 10000); // ناکافی

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('موجودی کیف پول', (string) $response->json('message'));

        // سفارش ساخته نشده باشد
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
    }

    #[Test]
    public function download_only_wallet_order_needs_no_address_or_carrier(): void
    {
        $user = $this->newUser();
        $product = $this->newDownloadProduct();
        $this->addToCart($user, $product, null, 1);
        $this->chargeWallet($user, 100000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway' => 'wallet',
            ], $this->apiHeaders());

        $response->assertStatus(201);
        $this->assertTrue($response->json('result.paid'));

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(0, $order->shipping_cost);
        $this->assertNull($order->city_id);

        $item = $order->items->first();
        $this->assertNull($item->carrier_id);
        $this->assertEquals(0, $item->shipping_cost);
    }

    /* =====================================================================
     |  ۴) آدرس (IDOR) و روش ارسال
     * ===================================================================== */

    #[Test]
    public function foreign_address_is_rejected(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();
        $other = $this->newUser();
        $foreignAddress = $this->newAddress($other);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $foreignAddress->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertArrayHasKey('address', $response->json('errors'));
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
    }

    #[Test]
    public function invalid_or_unusable_carrier_is_rejected(): void
    {
        // الف) شناسهٔ نامعتبر
        [$user, $product, $address] = $this->readyUser();
        $this->chargeWallet($user, 500000);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address->id,
                'carriers' => ['store' => 999999],
            ], $this->apiHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['carriers.store']);

        // ب) روش ارسال که شهر آدرس را پوشش نمی‌دهد
        $carrier = $this->newCarrier(['covered_cities' => 'select_city']);
        // فقط شهر خود روش ارسال پوشش داده می‌شود؛ آدرس کاربر در شهر دیگری است
        $carrier->cities()->attach(\App\Models\City::find($carrier->city_id)->id);

        [$user2, $product2, $address2] = $this->readyUser();
        $this->chargeWallet($user2, 500000);

        $response = $this->actingAs($user2, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address2->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('قابل استفاده نیست', (string) $response->json('message'));
        $this->assertDatabaseMissing('orders', ['user_id' => $user2->id]);
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
    }

    /* =====================================================================
     |  ۵) تاریخ تحویل برای روش user_select
     * ===================================================================== */

    #[Test]
    public function delivery_date_is_required_and_validated_for_user_select_carrier(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        $this->addToCart($user, $product, null, 1);

        $carrier = $this->newCarrier([
            'delivery_time_type' => 'user_select',
            'user_select_ranges' => 7,
            'start_days_after_order' => 1,
            'disable_fridays'    => false,
            'disable_holidays'   => false,
        ]);

        $province = \App\Models\Province::find($carrier->province_id);
        $city = \App\Models\City::find($carrier->city_id);
        $address = $this->newAddress($user, [], $province, $city);

        $this->chargeWallet($user, 500000);

        // الف) بدون تاریخ تحویل → ۴۲۲
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('تاریخ تحویل', (string) $response->json('message'));

        // ب) تاریخ خارج از بازهٔ قابل انتخاب → ۴۲۲
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'        => 'wallet',
                'address'        => $address->id,
                'carriers'       => ['store' => $carrier->id],
                'delivery_dates' => ['store' => now()->addDays(40)->format('Y-m-d')],
            ], $this->apiHeaders())
            ->assertStatus(422);

        // ج) تاریخ معتبرِ قابل انتخاب → موفق و پرداخت‌شده
        $validDate = now()->addDays(3)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'        => 'wallet',
                'address'        => $address->id,
                'carriers'       => ['store' => $carrier->id],
                'delivery_dates' => ['store' => $validDate],
            ], $this->apiHeaders());

        $response->assertStatus(201);
        $this->assertTrue($response->json('result.paid'));

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($validDate, $order->items->first()->delivery_date);
    }

    /* =====================================================================
     |  ۶) انتقال تخفیف سبد به سفارش
     * ===================================================================== */

    #[Test]
    public function discount_code_is_transferred_to_order(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();
        $this->chargeWallet($user, 500000);

        $discount = $this->newDiscount(['type' => 'percent', 'amount' => 10]);
        $user->getCart()->update(['discount_id' => $discount->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'wallet',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(201);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(10, $order->discount_percent);
        $this->assertEquals(20000, $order->discount_amount); // ۱۰٪ از ۲۰۰,۰۰۰
        // ۲۰۰,۰۰۰ + ۴۰,۰۰۰ ارسال − ۲۰,۰۰۰ تخفیف = ۲۲۰,۰۰۰
        $this->assertEquals(220000, $order->price);
    }

    /* =====================================================================
     |  ۷) درگاه شتابی + verify بدون نشست
     * ===================================================================== */

    #[Test]
    public function gateway_checkout_returns_payment_block_and_transaction(): void
    {
        [$user, $product, $address, $carrier, $gateway] = $this->readyUser();

        $capturedCallback = null;

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->with(\Mockery::capture($capturedCallback))->andReturnSelf();
        Payment::shouldReceive('purchase')->with(
            \Mockery::on(fn ($invoice) => true),
            \Mockery::on(function ($closure) {
                if (is_callable($closure)) {
                    $closure(null, 'TX-API-GW-95');
                }

                return true;
            })
        )->andReturnSelf();
        Payment::shouldReceive('pay')->andReturn(
            new RedirectionForm('https://gw.example/pay', ['Token' => 'abc123'], 'POST')
        );

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'zarinpal',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $response->assertStatus(201);
        $this->assertFalse($response->json('result.paid'));

        $payment = $response->json('result.payment');
        $this->assertEquals('form', $payment['type']);
        $this->assertEquals('https://gw.example/pay', $payment['url']);
        $this->assertEquals('POST', $payment['method']);
        $this->assertEquals(['Token' => 'abc123'], $payment['inputs']);
        $this->assertNotEmpty($payment['token']);

        // callbackUrl روت verify API با همان توکن است
        $this->assertStringContainsString('/api/v1/payments/verify/' . $payment['token'], (string) $capturedCallback);

        // ردیف تراکنش با همان ستون‌های فلوی وب
        $transaction = Transaction::where('user_id', $user->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(0, $transaction->status);
        $this->assertEquals(240000, $transaction->amount);
        $this->assertEquals('TX-API-GW-95', $transaction->transId);
        $this->assertEquals($payment['token'], $transaction->token);

        // سفارش پرداخت‌نشده + رزرو موجودی فیزیکی
        $order = Order::where('user_id', $user->id)->first();
        $this->assertEquals('unpaid', $order->status);
        $this->assertEquals(2, $order->items->first()->get_price->reserved_stock);
    }

    #[Test]
    public function payments_verify_marks_order_paid_without_session(): void
    {
        [$user, $product, $address, $carrier, $gateway] = $this->readyUser();

        // مرحلهٔ خرید با توکن API
        $token = null;
        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->with(
            \Mockery::on(fn ($invoice) => true),
            \Mockery::on(function ($closure) use (&$token) {
                if (is_callable($closure)) {
                    $closure(null, 'TX-API-VERIFY-95');
                }

                return true;
            })
        )->andReturnSelf();
        Payment::shouldReceive('pay')->andReturnUsing(function () use (&$token) {
            return new RedirectionForm('https://gw.example/pay?token=' . $token, [], 'POST');
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'  => 'zarinpal',
                'address'  => $address->id,
                'carriers' => ['store' => $carrier->id],
            ], $this->apiHeaders());

        $token = $response->json('result.payment.token');
        $this->assertNotEmpty($token);

        $order = Order::where('user_id', $user->id)->first();

        // مرحلهٔ تأیید — بدون هدر x-api-key (شبیه‌سازی بازگشت واقعی درگاه)
        Payment::shouldReceive('amount')->andReturnSelf();
        Payment::shouldReceive('transactionId')->andReturnSelf();
        Payment::shouldReceive('verify')->andReturn(new Receipt('zarinpal', 'REF-API-95'));

        $verify = $this->getJson('/api/v1/payments/verify/' . $token);

        $verify->assertOk();
        $this->assertEquals('paid', $verify->json('result.status'));

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // تراکنش به‌روزرسانی و شماره پیگیری ذخیره شد
        $this->assertDatabaseHas('transactions', [
            'token'       => $token,
            'status'      => 1,
            'traceNumber' => 'REF-API-95',
        ]);

        // موجودی: از ۱۰ (بدون decrement ایونت چون سفارش همین‌جا ساخته شد؟ نه —
        // OrderCreated هنگام POST زده شد: ۱۰→۸) رزرو ۲ و سپس خروج ۲ → ۶
        $price = $product->fresh()->prices()->first();
        $this->assertEquals(6, $price->stock);
        $this->assertEquals(0, $price->reserved_stock);
    }

    #[Test]
    public function failed_or_unknown_verify_returns_clean_error(): void
    {
        // توکن ناشناس → ۴۰۴
        $this->getJson('/api/v1/payments/verify/no-such-token')
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        // شکست verify درگاه → ۴۲۲ با پیام فارسی و سفارش پرداخت‌نشده
        [$user, $product, $address, $carrier, $gateway] = $this->readyUser();

        $order = $user->orders()->create([
            'name'          => 'خریدار تست',
            'mobile'        => $user->username,
            'province_id'   => 1,
            'city_id'       => 1,
            'postal_code'   => '1234567890',
            'address'       => 'آدرس تست',
            'shipping_cost' => 0,
            'price'         => 100000,
            'status'        => 'unpaid',
        ]);

        $order->items()->create([
            'product_id'   => $product->id,
            'title'        => $product->title,
            'price'        => 100000,
            'real_price'   => 100000,
            'quantity'     => 1,
            'discount'     => 0,
            'price_id'     => $product->prices()->first()->id,
            'product_type' => 'physical',
        ]);

        Transaction::create([
            'status'               => false,
            'amount'               => 100000,
            'factorNumber'         => $order->id,
            'mobile'               => $user->username,
            'message'              => 'تراکنش ایجاد شد برای درگاه zarinpal',
            'transID'              => 'TX-FAIL-95',
            'token'                => 'TOKEN-FAIL-95',
            'user_id'              => $user->id,
            'transactionable_type' => Order::class,
            'transactionable_id'   => $order->id,
            'gateway_id'           => $gateway->id,
        ]);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('amount')->andReturnSelf();
        Payment::shouldReceive('transactionId')->andReturnSelf();
        Payment::shouldReceive('verify')->andThrow(new \Exception('پرداخت توسط کاربر لغو شد'));

        $response = $this->getJson('/api/v1/payments/verify/TOKEN-FAIL-95');

        $response->assertStatus(422);
        $this->assertStringContainsString('پرداخت تأیید نشد', (string) $response->json('message'));

        $order->refresh();
        $this->assertEquals('unpaid', $order->status);

        $this->assertDatabaseHas('transactions', [
            'token'  => 'TOKEN-FAIL-95',
            'status' => 0,
        ]);
    }

    /* =====================================================================
     |  ۸) ممنوعیت اقساطی/اعتباری در v1
     * ===================================================================== */

    #[Test]
    public function installment_or_credit_is_rejected_with_persian_error(): void
    {
        [$user, $product, $address, $carrier] = $this->readyUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'             => 'wallet',
                'address'             => $address->id,
                'carriers'            => ['store' => $carrier->id],
                'installment_enabled' => 1,
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('اقساط', (string) $response->json('message'));
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'gateway'        => 'wallet',
                'address'        => $address->id,
                'carriers'       => ['store' => $carrier->id],
                'credit_enabled' => 1,
            ], $this->apiHeaders());

        $response->assertStatus(422);
        $this->assertStringContainsString('اعتباری', (string) $response->json('message'));
    }

    /* =====================================================================
     |  ۹) جست‌وجو (GET /api/v1/search)
     * ===================================================================== */

    #[Test]
    public function search_returns_products_categories_and_posts(): void
    {
        $marker = 'واژهیکتا' . random_int(10000, 99999);

        $user = $this->newUser();

        $product = $this->newProduct(['title' => 'محصول ' . $marker . ' ویژه']);
        $category = $this->newCategory(['title' => 'دستهٔ ' . $marker]);
        Post::create([
            'title'     => 'مقالهٔ ' . $marker,
            'slug'      => 'post-' . $marker . '-' . random_int(100, 999),
            'content'   => 'متن مقالهٔ تستی',
            'published' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=' . urlencode($marker), $this->apiHeaders());

        $response->assertOk();

        $result = $response->json('result');

        $this->assertGreaterThan(0, $result['products_count']);
        $this->assertContains($product->id, collect(data_get($result, 'products.data'))->pluck('id')->all());

        $this->assertArrayHasKey('categories', $result);
        $this->assertGreaterThan(0, $result['categories_count']);
        $this->assertArrayHasKey('posts', $result);
        $this->assertGreaterThan(0, $result['posts_count']);
    }

    #[Test]
    public function search_validates_minimum_query_length(): void
    {
        $user = $this->newUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search', $this->apiHeaders())
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=' . urlencode('ا'), $this->apiHeaders())
            ->assertStatus(422);
    }

    /* =====================================================================
     |  ۱۰) فیلتر قیمت و تخفیف در apiFilter
     * ===================================================================== */

    #[Test]
    public function api_filter_supports_min_price_max_price_and_real_discount(): void
    {
        $user = $this->newUser();

        $cheap = $this->newProduct([], ['price' => 50000, 'discount_price' => 50000]);
        $mid = $this->newProduct([], ['price' => 100000, 'discount_price' => 100000]);
        $expensive = $this->newProduct([], ['price' => 200000, 'discount_price' => 200000]);

        // محصول تخفیف‌دار (ستون discount فعال — فاز ۹۵: فیلتر واقعی تخفیف)
        $discounted = $this->newProduct([], [
            'price'          => 70000,
            'regular_price'  => 100000,
            'discount_price' => 70000,
            'discount'       => 30,
        ]);

        $ids = fn ($response) => collect(data_get($response->json(), 'result.data'))->pluck('id')->all();

        // min_price
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products?min_price=150000', $this->apiHeaders())->assertOk();

        $this->assertContains($expensive->id, $ids($response));
        $this->assertNotContains($cheap->id, $ids($response));
        $this->assertNotContains($mid->id, $ids($response));

        // max_price
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products?max_price=60000', $this->apiHeaders())->assertOk();

        $this->assertContains($cheap->id, $ids($response));
        $this->assertNotContains($expensive->id, $ids($response));

        // discount=1 → فقط محصولات با تخفیف فعال
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products?discount=1', $this->apiHeaders())->assertOk();

        $this->assertContains($discounted->id, $ids($response));
        $this->assertNotContains($cheap->id, $ids($response));
        $this->assertNotContains($mid->id, $ids($response));
    }

    /* =====================================================================
     |  ۱۱) فرادادهٔ فیلترها (GET /api/v1/products/filters)
     * ===================================================================== */

    #[Test]
    public function products_filters_returns_price_bounds_and_counts(): void
    {
        $user = $this->newUser();

        $cheap = $this->newProduct([], ['price' => 50000, 'discount_price' => 50000]);
        $expensive = $this->newProduct([], ['price' => 200000, 'discount_price' => 200000]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/products/filters', $this->apiHeaders());

        $response->assertOk();

        $result = $response->json('result');

        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('min', $result['price']);
        $this->assertArrayHasKey('max', $result['price']);

        // کف/سقف کل فروشگاه (شامل داده‌های seed) — باید محدودهٔ محصولات تست را بپوشاند
        $this->assertLessThanOrEqual(50000, $result['price']['min']);
        $this->assertGreaterThanOrEqual(200000, $result['price']['max']);

        $this->assertArrayHasKey('categories', $result);
        $this->assertIsArray($result['categories']);
        $this->assertArrayHasKey('special_count', $result);
        $this->assertArrayHasKey('discount_count', $result);
        $this->assertGreaterThanOrEqual(1, $result['discount_count']);
    }

    /* =====================================================================
     |  ۱۲) بدون هدر x-api-key در روت‌های معمولی API
     * ===================================================================== */

    #[Test]
    public function api_routes_still_require_api_key_header(): void
    {
        $user = $this->newUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/checkout')
            ->assertStatus(403);
    }
}
