<?php

namespace Tests\Feature\Payment;

use App\Models\Gateway;
use App\Models\Order;
use App\Models\Price;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use Shetabit\Multipay\Receipt;
use Shetabit\Payment\Facade\Payment;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های پرداخت:
 *  - درگاه بانکی: موفق، ناموفق
 *  - پرداخت با کیف پول (موجودی کافی و ناکافی)
 *  - بازگشت از درگاه و ثبت وضعیت (verify)
 *  - جلوگیری از دستکاری قیمت
 *  - عدم ذخیره اطلاعات کارت (PCI)
 *  - مبلغ صفر (پرداخت رایگان)
 */
class PaymentTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
        Queue::fake();
    }

    /**
     * سفارش پرداخت‌نشده با آیتم برای کاربر.
     */
    protected function newUnpaidOrder(User $user, int $price = 150000): Order
    {
        $product = $this->newProduct([], ['price' => $price]);

        $order = $user->orders()->create([
            'name'         => 'خریدار تست',
            'mobile'       => $user->username,
            'province_id'  => 1,
            'city_id'      => 1,
            'postal_code'  => '1234567890',
            'address'      => 'آدرس تست',
            'shipping_cost' => 0,
            'price'        => $price,
            'status'       => 'unpaid',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'title'      => $product->title,
            'price'      => $price,
            'real_price' => $price,
            'quantity'   => 1,
            'discount'   => 0,
            'price_id'   => $product->prices()->first()->id,
            'product_type' => 'physical',
        ]);

        return $order->fresh();
    }

    // =====================================================================
    //  اعتبارسنجی ورودی پرداخت
    // =====================================================================

    #[Test]
    public function pay_requires_a_valid_gateway(): void
    {
        $user = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user);

        $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=not-a-gateway')
            ->assertSessionHasErrors(['gateway']);
    }

    #[Test]
    public function paying_another_users_order_is_forbidden(): void
    {
        $owner = $this->newUser();
        $attacker = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($owner);

        $this->actingAs($attacker, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal')
            ->assertNotFound();
    }

    #[Test]
    public function already_paid_order_cannot_be_paid_again(): void
    {
        $user = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user);
        $order->update(['status' => 'paid']);

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'قبلا پرداخت',
            (string) session('error')
        );
    }

    // =====================================================================
    //  پرداخت با کیف پول
    // =====================================================================

    #[Test]
    public function wallet_payment_with_sufficient_balance_marks_order_paid(): void
    {
        $user = $this->newUser();
        $order = $this->newUnpaidOrder($user, 100000);

        // شارژ کیف پول: ۱۵۰,۰۰۰ تومان
        $wallet = $user->getWallet();
        $wallet->histories()->create([
            'type' => 'deposit', 'amount' => 150000,
            'description' => 'شارژ تست', 'source' => 'user', 'status' => 'success',
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=wallet');

        $order->refresh();
        $this->assertEquals('paid', $order->status, 'سفارش باید پرداخت‌شده شود');

        // برداشت از کیف پول ثبت شده باشد
        $this->assertDatabaseHas('wallet_histories', [
            'wallet_id' => $wallet->id,
            'type'      => 'withdraw',
            'amount'    => 100000,
            'status'    => 'success',
        ]);

        // موجودی نهایی: ۱۵۰,۰۰۰ − ۱۰۰,۰۰۰ = ۵۰,۰۰۰
        $this->assertEquals(50000, $wallet->fresh()->balance());
    }

    #[Test]
    public function wallet_payment_with_insufficient_balance_falls_back_to_gateway(): void
    {
        $user = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 100000);

        // موجودی ناکافی: ۱۰,۰۰۰
        $wallet = $user->getWallet();
        $wallet->histories()->create([
            'type' => 'deposit', 'amount' => 10000,
            'description' => 'شارژ تست', 'source' => 'user', 'status' => 'success',
        ]);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->andReturnSelf();
        Payment::shouldReceive('pay')->andReturnSelf();
        Payment::shouldReceive('render')->andReturn('<form id="gateway-form">redirect</form>');

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=wallet');

        $this->assertStringContainsString('gateway-form', $response->getContent());

        $order->refresh();
        $this->assertEquals('unpaid', $order->status, 'سفارش نباید پرداخت شود');
    }

    // =====================================================================
    //  درگاه بانکی
    // =====================================================================

    #[Test]
    public function bank_gateway_purchase_creates_transaction_and_renders_form(): void
    {
        $user = $this->newUser();
        $gateway = $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 250000);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        // شبیه‌سازی فراخوانی closure توسط کتابخانه هنگام match شدن آرگومان‌ها
        Payment::shouldReceive('purchase')->with(
            \Mockery::on(fn ($invoice) => true),
            \Mockery::on(function ($closure) {
                if (is_callable($closure)) { $closure(null, 'TX-123456'); }
                return true;
            })
        )->andReturnSelf();
        Payment::shouldReceive('pay')->andReturnSelf();
        Payment::shouldReceive('render')->andReturn('<form id="pay">bank form</form>');

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal');

        file_put_contents('/tmp/pay-debug.html', $response->getContent());
        $this->assertStringContainsString('bank form', $response->getContent());

        // تراکنش با وضعیت پرداخت‌نشده ثبت شده باشد
        $transaction = Transaction::where('user_id', $user->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(0, $transaction->status);
        $this->assertEquals(250000, $transaction->amount);
        $this->assertEquals('TX-123456', $transaction->transId);
    }

    #[Test]
    public function gateway_purchase_failure_redirects_with_error(): void
    {
        $user = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->andThrow(new \Exception('اتصال به درگاه برقرار نشد'));

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal');

        $response->assertRedirect();
        $this->assertEquals('اتصال به درگاه برقرار نشد', session('transaction-error'));

        $order->refresh();
        $this->assertEquals('unpaid', $order->status);
    }

    // =====================================================================
    //  بازگشت از درگاه (verify)
    // =====================================================================

    #[Test]
    public function successful_gateway_verification_marks_order_paid(): void
    {
        $user = $this->newUser();
        $gateway = $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 120000);

        // تراکنش ایجادشده در مرحله purchase
        Transaction::create([
            'status' => false, 'amount' => 120000, 'factorNumber' => $order->id,
            'mobile' => $user->username, 'message' => 'تراکنش ایجاد شد',
            'transId' => 'TX-VERIFY-1', 'token' => 'TX-VERIFY-1',
            'user_id' => $user->id,
            'transactionable_type' => Order::class, 'transactionable_id' => $order->id,
            'gateway_id' => $gateway->id,
        ]);

        $receipt = new Receipt('zarinpal', 'REF-99887766');

        Payment::shouldReceive('via')->with('zarinpal')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('amount')->andReturnSelf();
        Payment::shouldReceive('transactionId')->andReturnSelf();
        Payment::shouldReceive('verify')->andReturn($receipt);

        $response = $this->actingAs($user, 'web')
            ->withSession(['transactionId' => 'TX-VERIFY-1', 'amount' => 120000])
            ->get('/orders/payment/callback/zarinpal');

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('paid', $order->status, 'بعد از تأیید درگاه، سفارش باید پرداخت شود');

        // تراکنش موفق ثبت شده و شماره پیگیری ذخیره شده است
        $this->assertDatabaseHas('transactions', [
            'transId' => 'TX-VERIFY-1',
            'status'  => 1,
            'traceNumber' => 'REF-99887766',
        ]);
    }

    #[Test]
    public function failed_gateway_verification_keeps_order_unpaid(): void
    {
        $user = $this->newUser();
        $gateway = $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 120000);

        Transaction::create([
            'status' => false, 'amount' => 120000, 'factorNumber' => $order->id,
            'mobile' => $user->username, 'message' => 'تراکنش ایجاد شد',
            'transId' => 'TX-FAIL-1', 'token' => 'TX-FAIL-1',
            'user_id' => $user->id,
            'transactionable_type' => Order::class, 'transactionable_id' => $order->id,
            'gateway_id' => $gateway->id,
        ]);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('amount')->andReturnSelf();
        Payment::shouldReceive('transactionId')->andReturnSelf();
        Payment::shouldReceive('verify')->andThrow(new \Exception('پرداخت توسط کاربر لغو شد'));

        $response = $this->actingAs($user, 'web')
            ->withSession(['transactionId' => 'TX-FAIL-1', 'amount' => 120000])
            ->get('/orders/payment/callback/zarinpal');

        $response->assertRedirect();
        $this->assertEquals('پرداخت توسط کاربر لغو شد', session('transaction-error'));

        $order->refresh();
        $this->assertEquals('unpaid', $order->status, 'سفارش نباید پرداخت شود');

        // پیام خطا روی تراکنش ثبت شده باشد
        $this->assertDatabaseHas('transactions', [
            'transId' => 'TX-FAIL-1',
            'status'  => 0,
        ]);
    }

    #[Test]
    public function cancel_at_gateway_leaves_order_unpaid_and_retryable(): void
    {
        $user = $this->newUser();
        $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user);

        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->andThrow(new \Exception('کاربر از پرداخت انصراف داد'));

        $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal')
            ->assertRedirect();

        // کاربر می‌تواند دوباره تلاش کند
        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=wallet&force=no');
        // درگاه wallet بدون موجودی → دوباره خطای purchase (mock همچنان فعال)
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('unpaid', $order->status);
    }

    // =====================================================================
    //  جلوگیری از دستکاری قیمت
    // =====================================================================

    #[Test]
    public function client_cannot_tamper_order_price_during_payment(): void
    {
        $user = $this->newUser();
        $gateway = $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 300000);

        $capturedInvoice = null;
        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->with(
            \Mockery::capture($capturedInvoice),
            \Mockery::on(fn ($closure) => true)
        )->andReturnSelf();
        Payment::shouldReceive('pay')->andReturnSelf();
        Payment::shouldReceive('render')->andReturn('<form>ok</form>');

        // درخواست با پارامترهای دستکاری‌شده
        $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal&price=1&amount=1');

        // مبلغ فاکتور باید از قیمت سمت سرور باشد نه ورودی کاربر
        $this->assertNotNull($capturedInvoice);
        $this->assertEquals(300000, (int) $capturedInvoice->getAmount());
    }

    #[Test]
    public function order_store_ignores_client_supplied_price_fields(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['price' => 100000]);
        $this->addToCart($user, $product, null, 2);
        $carrier = $this->newCarrier();
        $province = \App\Models\Province::find($carrier->province_id);
        $city = \App\Models\City::find($carrier->city_id);
        $address = $this->newAddress($user, [], $province, $city);
        $this->newGateway('zarinpal');

        $this->actingAs($user, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $address->id,
                'carrier_id_store' => $carrier->id,
                // فیلدهای دستکاری‌شده توسط کلاینت — باید نادیده گرفته شوند
                'price'      => 1,
                'status'     => 'paid',
                'paid_at'    => '2020-01-01',
                'user_id'    => 999,
            ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        // ۲×۱۰۰,۰۰۰ + ۴۰,۰۰۰ ارسال — نه مقدار دستکاری‌شده «۱»
        $this->assertEquals(240000, $order->price);
        $this->assertEquals('unpaid', $order->status);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertNull($order->paid_at);
    }

    // =====================================================================
    //  PCI — عدم ذخیره اطلاعات کارت
    // =====================================================================

    #[Test]
    public function no_card_data_is_stored_anywhere_in_payment_flow(): void
    {
        $user = $this->newUser();
        $gateway = $this->newGateway('zarinpal');
        $order = $this->newUnpaidOrder($user, 80000);

        // مرحله purchase
        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('callbackUrl')->andReturnSelf();
        Payment::shouldReceive('purchase')->with(
            \Mockery::on(fn ($invoice) => true),
            \Mockery::on(function ($closure) {
                if (is_callable($closure)) { $closure(null, 'TX-PCI-1'); }
                return true;
            })
        )->andReturnSelf();
        Payment::shouldReceive('pay')->andReturnSelf();
        Payment::shouldReceive('render')->andReturn('<form>bank</form>');

        $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal&card_number=6037999999999999');

        // مرحله verify
        Payment::shouldReceive('via')->andReturnSelf();
        Payment::shouldReceive('config')->andReturnSelf();
        Payment::shouldReceive('amount')->andReturnSelf();
        Payment::shouldReceive('transactionId')->andReturnSelf();
        Payment::shouldReceive('verify')->andReturn(new Receipt('zarinpal', 'REF-PCI'));

        $this->actingAs($user, 'web')
            ->withSession(['transactionId' => 'TX-PCI-1', 'amount' => 80000])
            ->get('/orders/payment/callback/zarinpal?card_number=6037999999999999&pan=6037-****');

        // هیچ رکوردی نباید شماره کارت را ذخیره کرده باشد
        $this->assertDatabaseMissing('transactions', ['cardNumber' => '6037999999999999']);

        $transaction = Transaction::where('transId', 'TX-PCI-1')->first();
        $this->assertNull($transaction->cardNumber, 'ستون cardNumber باید همیشه null بماند');

        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }

    // =====================================================================
    //  پرداخت مبلغ صفر (رایگان)
    // =====================================================================

    #[Test]
    public function zero_price_order_is_paid_without_gateway(): void
    {
        $user = $this->newUser();
        $order = $this->newUnpaidOrder($user, 0);

        $response = $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=wallet');

        $order->refresh();
        $this->assertEquals('paid', $order->status, 'سفارش رایگان باید بدون درگاه پرداخت شود');
    }
}
