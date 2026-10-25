<?php

namespace Tests\Feature\Extra;

use App\Models\PriceAlert;
use App\Models\Sms;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Support\ShopTestSetup;

/**
 * فاز ۷۸ — تست‌های هشدار کاهش قیمت.
 *
 * پوشش: اشتراک مهمان/کاربر، رهگیری افت قیمت توسط PriceObserver،
 * پردازش Job (علامت‌خوردن بدون الگوی پیامک)، بی‌اثری افزایش قیمت،
 * ماندگاری هشدار وقتی قابل‌خرید نیست، اشتراک مجدد و لغو.
 */
class PriceAlertTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_can_subscribe_to_price_alert_with_current_target()
    {
        $product = $this->newProduct();

        $response = $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09121234567',
            'name'       => 'علی رضایی',
        ]);

        $response->assertOk();

        $alert = PriceAlert::query()
            ->where('product_id', $product->id)
            ->where('mobile', '09121234567')
            ->first();

        $this->assertNotNull($alert);
        $this->assertTrue($alert->active);
        $this->assertNull($alert->user_id);
        $this->assertEquals('علی رضایی', $alert->name);
        // هدف = قیمت مؤثرِ فعلیِ تنها قیمتِ محصول
        $this->assertEquals(100000, (float) $alert->target_price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logged_in_user_subscribes_without_mobile_input()
    {
        $product = $this->newProduct();
        $user    = $this->newUser();

        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), [
                'product_id' => $product->id,
            ])->assertOk();

        $alert = PriceAlert::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals($user->mobile ?: $user->username, $alert->mobile);
        $this->assertTrue($alert->active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function price_drop_marks_alert_as_notified_without_sms_pattern()
    {
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

        $price = $product->prices()->first();

        // افت قیمت مؤثر: ۱۰۰٬۰۰۰ ← ۸۰٬۰۰۰ (Observer باید Job را رانده باشد)
        $price->update([
            'price'          => 80000,
            'discount_price' => 80000,
            'regular_price'  => 80000,
        ]);

        $alert = PriceAlert::query()->where('product_id', $product->id)->first();

        $this->assertFalse($alert->active, 'هشدار باید پس از افت قیمت علامت خورده باشد');
        $this->assertNotNull($alert->notified_at);

        // الگوی پیامک تنظیم نشده → هیچ پیامکی ارسال نمی‌شود
        $this->assertEquals(0, Sms::count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function price_increase_does_not_notify_alerts()
    {
        $product = $this->newProduct();

        PriceAlert::create([
            'product_id'   => $product->id,
            'mobile'       => '09121234567',
            'target_price' => 50000,
            'active'       => true,
        ]);

        $product->prices()->first()->update([
            'price'          => 120000,
            'discount_price' => 120000,
            'regular_price'  => 120000,
        ]);

        $this->assertTrue(
            PriceAlert::query()->where('product_id', $product->id)->first()->active,
            'افزایش قیمت نباید هشدار را علامت بزند'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function alert_stays_active_when_dropped_product_is_not_buyable()
    {
        $product = $this->newProduct();

        PriceAlert::create([
            'product_id'   => $product->id,
            'mobile'       => '09121234567',
            'target_price' => 100000,
            'active'       => true,
        ]);

        // افت قیمت اما موجودی صفر → هیچ قیمت قابل‌خریدی نیست
        $product->prices()->first()->update([
            'price'          => 80000,
            'discount_price' => 80000,
            'regular_price'  => 80000,
            'stock'          => 0,
        ]);

        $this->assertTrue(
            PriceAlert::query()->where('product_id', $product->id)->first()->active,
            'وقتی کالا قابل‌خرید نیست، هشدار باید فعال بماند'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resubscribe_after_notification_reactivates_alert()
    {
        $product = $this->newProduct();

        PriceAlert::create([
            'product_id'   => $product->id,
            'mobile'       => '09121234567',
            'target_price' => 100000,
            'active'       => false,
            'notified_at'  => now(),
        ]);

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09121234567',
        ])->assertOk();

        $alert = PriceAlert::query()->where('product_id', $product->id)->first();

        $this->assertTrue($alert->active);
        $this->assertNull($alert->notified_at);
        $this->assertEquals(100000, (float) $alert->target_price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function logged_in_user_can_cancel_their_alert()
    {
        $product = $this->newProduct();
        $user    = $this->newUser();

        $this->actingAs($user, 'web')
            ->post(route('front.price-alert.store'), ['product_id' => $product->id])
            ->assertOk();

        $this->assertEquals(1, PriceAlert::count());

        $this->actingAs($user, 'web')
            ->delete(route('front.price-alert.destroy'), ['product_id' => $product->id])
            ->assertOk();

        $this->assertEquals(0, PriceAlert::count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_guest_mobile_is_rejected()
    {
        $product = $this->newProduct();

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '12345',
        ])->assertSessionHasErrors('mobile');

        $this->assertEquals(0, PriceAlert::count());
    }
}
