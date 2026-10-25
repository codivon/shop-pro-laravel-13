<?php

namespace Tests\Feature\Extra;

use App\Jobs\CancelOrder;
use App\Models\Price;
use Tests\TestCase;
use Tests\Support\ShopTestSetup;

use function check_cart_quantity;

/**
 * فاز ۷۸ — تست‌های پیش‌فروش.
 *
 * پوشش: افزودنی بودن محصول پیش‌فروشِ ناموجود، بلاک‌بودن محصولِ معمولیِ
 * ناموجود، سقف یک عدد در سبد (وب و API)، عبور check_cart_quantity،
 * و دست‌نخوردن انبار در لغو خودکار سفارش پیش‌فروش.
 */
class PreOrderTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pre_order_product_is_addable_even_when_out_of_stock()
    {
        $product = $this->newProduct(['pre_order' => true], ['stock' => 0]);

        $this->assertTrue($product->addableToCart());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regular_out_of_stock_product_is_blocked_at_cart()
    {
        $product = $this->newProduct(['pre_order' => false], ['stock' => 0]);
        $price   = $product->prices()->first();

        $response = $this->post(route('front.cart.store', ['product' => $product->slug]), [
            'price_id' => $price->id,
        ]);

        $response->assertJson(['status' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function guest_can_add_pre_order_product_to_web_cart()
    {
        $product = $this->newProduct(['pre_order' => true], ['stock' => 0]);
        $price   = $product->prices()->first();

        $response = $this->post(route('front.cart.store', ['product' => $product->slug]), [
            'price_id' => $price->id,
        ]);

        $response->assertJson(['status' => 'success']);

        $cart = \App\Models\Cart::query()->latest('id')->first();

        $this->assertNotNull($cart);
        $this->assertTrue(
            $cart->products()->wherePivot('product_id', $product->id)->exists()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pre_order_product_is_capped_to_single_quantity_in_web_cart()
    {
        $user    = $this->newUser();
        $product = $this->newProduct(['pre_order' => true], ['stock' => 0]);
        $price   = $product->prices()->first();

        $this->actingAs($user, 'web')
            ->post(route('front.cart.store', ['product' => $product->slug]), [
                'price_id' => $price->id,
            ])->assertJson(['status' => 'success']);

        $this->assertEquals(
            1,
            $user->getCart()->products()->wherePivot('product_id', $product->id)->first()->pivot->quantity
        );

        $response = $this->actingAs($user, 'web')
            ->post(route('front.cart.store', ['product' => $product->slug]), [
                'price_id' => $price->id,
            ]);

        $response->assertJson(['status' => 'error']);
        $this->assertStringContainsString(
            'تنها یک عدد',
            (string) $response->json('message')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function api_cart_enforces_single_quantity_for_pre_order_products()
    {
        $user    = $this->newUser();
        $product = $this->newProduct(['pre_order' => true], ['stock' => 0]);
        $price   = $product->prices()->first();

        \App\Models\Apikey::create(['key' => 'test-api-key-78', 'is_active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart', [
                'price_id' => $price->id,
                'quantity' => 1,
            ], ['x-api-key' => 'test-api-key-78'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart', [
                'price_id' => $price->id,
                'quantity' => 1,
            ], ['x-api-key' => 'test-api-key-78'])
            ->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function check_cart_quantity_passes_with_pre_order_items()
    {
        $user    = $this->newUser();
        $product = $this->newProduct(['pre_order' => true], ['stock' => 0]);

        $this->actingAs($user, 'web');
        $this->addToCart($user, $product);

        $this->assertTrue(check_cart_quantity());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function auto_cancel_job_never_inflates_stock_of_pre_order_items()
    {
        $user    = $this->newUser();
        $product = $this->newProduct(['pre_order' => true], ['stock' => 3]);
        $price   = $product->prices()->first();

        // شناسه استان/شهر واقعی برای قید FK جدول orders
        [$province, $city] = $this->newCity();

        [$order, $item] = $this->newPaidOrder($user, $product, [
            'status'      => 'unpaid',
            'paid_at'     => null,
            'province_id' => $province->id,
            'city_id'     => $city->id,
        ]);

        (new CancelOrder($order->fresh()))->handle();

        $freshPrice = Price::find($price->id);

        $this->assertEquals(3, $freshPrice->stock, 'موجودی پیش‌فروش نباید در لغو تغییر کند');
        $this->assertEquals(0, $freshPrice->reserved_stock);
    }
}
