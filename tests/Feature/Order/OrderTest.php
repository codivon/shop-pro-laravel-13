<?php

namespace Tests\Feature\Order;

use App\Events\OrderPaid;
use App\Jobs\CancelOrder;
use App\Models\Order;
use App\Models\Price;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های سفارش و پس از خرید:
 *  - مشاهده سفارش در تاریخچه
 *  - ایمیل/پیامک تأیید سفارش (ایونت OrderPaid)
 *  - لغو سفارش در بازه مجاز (جاب لغو خودکار)
 *  - پیگیری مرجوعی (ReturnRequest)
 */
class OrderTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
        Queue::fake();
    }

    // =====================================================================
    //  تاریخچه سفارش
    // =====================================================================

    #[Test]
    public function order_history_shows_own_orders(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        $response = $this->actingAs($user, 'web')->get('/profile/orders');

        $response->assertStatus(200);
        $response->assertSee('#' . $order->id, false);
    }

    #[Test]
    public function order_history_filters_by_status(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$paidOrder] = $this->newPaidOrder($user, $product);
        $unpaidOrder = $user->orders()->create([
            'name' => 'خریدار', 'mobile' => $user->username,
            'province_id' => 1, 'city_id' => 1, 'postal_code' => '1',
            'address' => 'آدرس', 'shipping_cost' => 0, 'price' => 1000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'web')->get('/profile/orders?status=paid');
        $content = $response->getContent();

        $this->assertStringContainsString((string) $paidOrder->id, $content);
        $this->assertStringNotContainsString((string) $unpaidOrder->id, $content);
    }

    #[Test]
    public function user_can_view_own_order_details(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        $response = $this->actingAs($user, 'web')->get('/profile/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee($product->title, false);
    }

    #[Test]
    public function user_cannot_view_other_users_order(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($owner, $product);

        $this->actingAs($intruder, 'web')
            ->get('/profile/orders/' . $order->id)
            ->assertNotFound();
    }

    #[Test]
    public function user_cannot_print_other_users_order(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($owner, $product);

        $this->actingAs($intruder, 'web')
            ->get('/profile/orders/' . $order->id . '/print')
            ->assertNotFound();
    }

    // =====================================================================
    //  تأیید سفارش (ایمیل/پیامک)
    // =====================================================================

    #[Test]
    public function order_paid_event_notifies_admins(): void
    {
        Notification::fake();

        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        event(new OrderPaid($order));

        // اعلان پرداخت سفارش برای مدیران ارسال می‌شود (بدون شرط)
        $admins = \App\Models\Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::assertSentTo($admins, \App\Notifications\Order\OrderPaid::class);
    }

    #[Test]
    public function order_paid_event_sends_user_sms_when_enabled(): void
    {
        Notification::fake();
        option_update('user_sms_on_order_paid', 'on');

        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        event(new OrderPaid($order));

        Notification::assertSentTo([$user], \App\Notifications\Sms\OrderPaidSms::class);
    }

    #[Test]
    public function wallet_payment_triggers_order_paid_notifications(): void
    {
        Notification::fake();
        option_update('user_sms_on_order_paid', 'on');

        $user = $this->newUser();
        $wallet = $user->getWallet();
        $wallet->histories()->create([
            'type' => 'deposit', 'amount' => 500000,
            'description' => 'شارژ', 'source' => 'user', 'status' => 'success',
        ]);

        $order = $user->orders()->create([
            'name' => 'خریدار', 'mobile' => $user->username,
            'province_id' => 1, 'city_id' => 1, 'postal_code' => '1',
            'address' => 'آدرس', 'shipping_cost' => 0, 'price' => 100000,
            'status' => 'unpaid',
        ]);
        $product = $this->newProduct([], ['price' => 100000]);
        $order->items()->create([
            'product_id' => $product->id, 'title' => $product->title,
            'price' => 100000, 'real_price' => 100000, 'quantity' => 1,
            'price_id' => $product->prices()->first()->id, 'product_type' => 'physical',
        ]);

        $this->actingAs($user, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=wallet')
            ->assertRedirect();

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        Notification::assertSentTo([$user], \App\Notifications\Sms\OrderPaidSms::class);
    }

    // =====================================================================
    //  لغو سفارش
    // =====================================================================

    #[Test]
    public function unpaid_order_is_auto_cancelled_by_job(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 10]);
        $price = $product->prices()->first();

        $order = $user->orders()->create([
            'name' => 'خریدار', 'mobile' => $user->username,
            'province_id' => 1, 'city_id' => 1, 'postal_code' => '1',
            'address' => 'آدرس', 'shipping_cost' => 0, 'price' => 100000,
            'status' => 'unpaid',
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'title' => $product->title,
            'price' => 100000, 'real_price' => 100000, 'quantity' => 2,
            'price_id' => $price->id, 'product_type' => 'physical',
        ]);

        // شبیه‌سازی رزرو موجودی (مثل فلوی pay)
        $price->increment('reserved_stock', 2);

        // اجرای جاب لغو خودکار
        (new CancelOrder($order->fresh()))->handle();

        $order->refresh();
        $this->assertEquals('canceled', $order->status, 'سفارش پرداخت‌نشده باید لغو شود');

        // رزرو موجودی آزاد شده باشد
        $this->assertEquals(0, $price->fresh()->reserved_stock);
    }

    #[Test]
    public function paid_order_is_not_cancelled_by_job(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        (new CancelOrder($order->fresh()))->handle();

        $this->assertEquals('paid', $order->fresh()->status, 'سفارش پرداخت‌شده نباید لغو شود');
    }

    #[Test]
    public function stock_restocked_when_unpaid_order_cancelled(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct([], ['stock' => 5]);
        $price = $product->prices()->first();

        $order = $user->orders()->create([
            'name' => 'خریدار', 'mobile' => $user->username,
            'province_id' => 1, 'city_id' => 1, 'postal_code' => '1',
            'address' => 'آدرس', 'shipping_cost' => 0, 'price' => 100000,
            'status' => 'unpaid',
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'title' => $product->title,
            'price' => 100000, 'real_price' => 100000, 'quantity' => 2,
            'price_id' => $price->id, 'product_type' => 'physical',
        ]);

        // شبیه‌سازی فلوی واقعی: در OrderCreated موجودی کم می‌شود و رزرو می‌شود
        $price->update(['stock' => $price->stock - 2]);
        $price->increment('reserved_stock', 2);
        $stockAfterCreate = $price->fresh()->stock; // ۳

        (new CancelOrder($order->fresh()))->handle();

        // لغو سفارش باید موجودی را برگرداند: ۳ + ۲ = ۵ و رزرو آزاد شود
        $this->assertEquals($stockAfterCreate + 2, $price->fresh()->stock);
        $this->assertEquals(0, $price->fresh()->reserved_stock);
    }

    // =====================================================================
    //  مرجوعی
    // =====================================================================

    #[Test]
    public function delivered_item_within_period_can_be_returned(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($user, $product, [], [
            'shipping_status' => 'delivered',
            'delivery_date'   => now()->subDays(2)->toDateString(),
        ]);
        $reason = $this->newReturnReason();

        // صفحه فرم مرجوعی
        $this->actingAs($user, 'web')
            ->get('/profile/returns/create/' . $order->id . '/' . $item->id)
            ->assertStatus(200);

        // ثبت درخواست مرجوعی
        $response = $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'محصول مغایرت داشت',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('return_requests', [
            'order_id'   => $order->id,
            'user_id'    => $user->id,
            'status'     => 'pending',
            'quantity'   => $item->quantity,
        ]);

        // مشاهده درخواست در لیست مرجوعی‌ها
        $index = $this->actingAs($user, 'web')->get('/profile/returns');
        $index->assertStatus(200);
    }

    #[Test]
    public function return_request_after_period_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($user, $product, [], [
            'shipping_status' => 'delivered',
            'delivery_date'   => now()->subDays(30)->toDateString(), // خارج از مهلت ۷ روزه
        ]);
        $reason = $this->newReturnReason();

        $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'درخواست دیرهنگام',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('return_requests', ['order_item_id' => $item->id]);
    }

    #[Test]
    public function return_request_requires_delivered_status(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($user, $product, [], [
            'shipping_status' => 'w-pending', // هنوز ارسال نشده
        ]);
        $reason = $this->newReturnReason();

        $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'تست',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('return_requests', ['order_item_id' => $item->id]);
    }

    #[Test]
    public function user_cannot_return_other_users_order_item(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($owner, $product, [], [
            'shipping_status' => 'delivered',
        ]);
        $reason = $this->newReturnReason();

        $this->actingAs($intruder, 'web')
            ->get('/profile/returns/create/' . $order->id . '/' . $item->id)
            ->assertNotFound();

        $this->actingAs($intruder, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'تست دسترسی',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('return_requests', ['order_item_id' => $item->id]);
    }

    #[Test]
    public function duplicate_return_request_is_rejected(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($user, $product, [], [
            'shipping_status' => 'delivered',
            'delivery_date'   => now()->subDay()->toDateString(),
        ]);
        $reason = $this->newReturnReason();

        $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'اولین درخواست',
            ]);

        // درخواست دوم برای همان آیتم باید رد شود
        $response = $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'درخواست تکراری',
            ]);

        $response->assertRedirect();
        $this->assertStringContainsString('قبلاً', (string) session('error'));

        $this->assertEquals(1, ReturnRequest::where('order_item_id', $item->id)->count());
    }

    #[Test]
    public function user_can_view_own_return_request_details(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($user, $product, [], [
            'shipping_status' => 'delivered',
            'delivery_date'   => now()->subDay()->toDateString(),
        ]);
        $reason = $this->newReturnReason();

        $this->actingAs($user, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'درخواست نمایش',
            ]);

        $returnRequest = ReturnRequest::where('order_item_id', $item->id)->first();
        $this->assertNotNull($returnRequest);

        $this->actingAs($user, 'web')
            ->get('/profile/returns/' . $returnRequest->id)
            ->assertStatus(200);
    }

    #[Test]
    public function user_cannot_view_other_users_return_request(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $product = $this->newProduct();
        [$order, $item] = $this->newPaidOrder($owner, $product, [], [
            'shipping_status' => 'delivered',
            'delivery_date'   => now()->subDay()->toDateString(),
        ]);
        $reason = $this->newReturnReason();

        $returnRequest = $this->actingAs($owner, 'web')
            ->post('/profile/returns/create/' . $order->id . '/' . $item->id, [
                'return_reason_id' => $reason->id,
                'description'      => 'تست',
            ]);

        $returnRequestModel = ReturnRequest::where('order_item_id', $item->id)->first();

        $this->actingAs($intruder, 'web')
            ->get('/profile/returns/' . $returnRequestModel->id)
            ->assertNotFound();
    }
}
