<?php

namespace Tests\Support;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Cart;
use App\Models\Category;
use App\Models\City;
use App\Models\Discount;
use App\Models\Gateway;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use App\Models\ReturnReason;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * زیرساخت مشترک تست‌های فروشگاه.
 *
 * نکات مهم:
 *  - از DatabaseTransactions استفاده می‌کنیم (دیتابیس واقعی MySQL توسعه،
 *    همه‌چیز داخل تراکنش است و در پایان هر تست rollback می‌شود).
 *  - REMOTE_ADDR روی 127.0.0.1 تنظیم می‌شود تا middleware ForceHttps
 *    (وایت‌لیست) ریدایرکت https انجام ندهد.
 *  - تمام ساخت داده‌ها با helper های این trait انجام می‌شود تا تست‌ها
 *    مستقل از seed دیتابیس باشند.
 */
trait ShopTestSetup
{
    use DatabaseTransactions;

    protected function setUpShop(): void
    {
        // ForceHttps فقط IPهای خارج از وایت‌لیست را به https هدایت می‌کند
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        // کش options بین تست‌ها (array driver) را پاک می‌کنیم تا تغییرات
        // یک تست روی تست بعدی اثر نگذارد
        Cache::flush();

        // درس CI (فاز ۱۴۰): سفارش‌ها/پرداخت‌ها به province_id=1 و city_id=1
        // تکیه دارند که فقط روی دیتابیس سیدشده برقرار است. این گارد تست‌ها را
        // مستقل از وضعیت seed میزبان می‌کند (فلسفهٔ همین trait: «مستقل از seed»).
        // insertOrIgnore + explicit id = داخل تراکنش هر تست و rollback کامل.
        DB::table('provinces')->insertOrIgnore([
            ['id' => 1, 'name' => 'تهران', 'latitude' => '35.55', 'longitude' => '51.79'],
        ]);
        DB::table('cities')->insertOrIgnore([
            ['id' => 1, 'province_id' => 1, 'name' => 'تهران', 'latitude' => '35.55', 'longitude' => '51.79'],
        ]);
    }

    /**
     * ساخت کاربر تأییدشده با موبایل/ایمیل/رمز.
     */
    protected function newUser(array $attributes = []): User
    {
        // رمز عبور باید جداگانه هش شود تا مقدار خام روی هش بازنویسی نشود
        $password = $attributes['password'] ?? 'secret1234';
        unset($attributes['password']);

        $mobile = '0912'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        $defaults = [
            'username' => $attributes['username'] ?? $mobile,
            'mobile' => $attributes['mobile'] ?? $mobile,
            'email' => $attributes['email'] ?? $mobile.'@test.ir',
            'password' => Hash::make($password),
            'level' => 'user',
            'status' => 1,
            'verified_at' => now(),
        ];

        return User::create(array_merge($defaults, $attributes));
    }

    /**
     * لاگین به‌عنوان کاربر (گارد web).
     */
    protected function loginAsUser(User $user): self
    {
        $this->actingAs($user, 'web');

        return $this;
    }

    /**
     * ساخت دسته‌بندی منتشرشده.
     */
    protected function newCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'title' => 'دسته تست '.Str::random(6),
            'slug' => 'cat-'.Str::random(8),
            'type' => 'productcat',
            'published' => true,
        ], $attributes));
    }

    /**
     * ساخت محصول فیزیکی منتشرشده با یک قیمت (single price).
     */
    protected function newProduct(array $attributes = [], array $priceAttributes = []): Product
    {
        $category = $attributes['category'] ?? $this->newCategory();
        unset($attributes['category']);

        $defaults = [
            'product_id' => 'P'.Str::random(10),
            'title' => 'محصول تست '.Str::random(8),
            'title_en' => 'Test Product '.Str::random(6),
            'slug' => 'product-'.Str::random(10),
            'type' => 'physical',
            'category_id' => $category->id,
            'price_type' => 'single-price',
            'weight' => 500,
            'description' => 'توضیحات محصول تستی',
            'short_description' => 'خلاصه محصول تستی',
            'special' => false,
            'published' => true,
            'status' => 'Accept',
            'sell' => 0,
            'view' => 0,
        ];

        $product = Product::create(array_merge($defaults, $attributes));

        // اتصال دسته‌بندی هم به ستون category_id و هم به pivot دسته‌ها
        // (allPublishedProducts از relation categories استفاده می‌کند)
        $product->categories()->attach($category->id);

        $this->newPrice($product, array_merge([
            'price' => 100000,
            'discount' => 0,
            'stock' => 10,
            'published' => true,
        ], $priceAttributes));

        return $product->fresh();
    }

    /**
     * ساخت محصول دانلودی.
     */
    protected function newDownloadProduct(array $attributes = []): Product
    {
        return $this->newProduct(array_merge([
            'type' => 'download',
        ], $attributes));
    }

    /**
     * ساخت قیمت برای محصول.
     */
    protected function newPrice(Product $product, array $attributes = []): Price
    {
        $price = $attributes['price'] ?? 100000;

        return Price::create(array_merge([
            'product_id' => $product->id,
            'price' => $price,
            'discount_price' => $price,
            'regular_price' => $price,
            'discount' => 0,
            'stock' => 10,
            'published' => true,
            'seller_id' => null,
        ], $attributes));
    }

    /**
     * ساخت برند.
     */
    protected function newBrand(array $attributes = []): Brand
    {
        return Brand::create(array_merge([
            'name' => 'برند تست '.Str::random(6),
            'slug' => 'brand-'.Str::random(8),
        ], $attributes));
    }

    /**
     * ساخت استان/شهر.
     */
    protected function newCity(): array
    {
        $province = Province::create([
            'name' => 'استان تست '.Str::random(6),
        ]);

        $city = City::create([
            'name' => 'شهر تست '.Str::random(6),
            'province_id' => $province->id,
        ]);

        return [$province, $city];
    }

    /**
     * ساخت آدرس برای کاربر.
     * اگر $province و $city پاس شوند، همان‌ها استفاده می‌شوند (برای هم‌استانی با روش ارسال).
     */
    protected function newAddress(User $user, array $attributes = [], ?Province $province = null, ?City $city = null): Address
    {
        if (! $province || ! $city) {
            [$province, $city] = $this->newCity();
        }

        return Address::create(array_merge([
            'user_id' => $user->id,
            'fullname' => 'کاربر تست',
            'mobile' => substr($user->username, 0, 11),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => $province->name,
            'city_name' => $city->name,
            'postal_code' => '1234567890',
            'address' => 'آدرس کامل تستی',
            'buildingNumber' => 1,
            'unit' => 2,
            'lat' => '35.7',
            'lng' => '51.4',
            'active' => 1,
        ], $attributes));
    }

    /**
     * ساخت روش ارسال فعال با تعرفه (درون/برون استانی).
     */
    protected function newCarrier(array $attributes = [], array $tariffAttributes = []): Carrier
    {
        [$province, $city] = $this->newCity();

        $carrier = Carrier::create(array_merge([
            'title' => 'پست تست '.Str::random(4),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'is_active' => true,
            'max_package_weight' => 10000,
            'extra_cost' => null,
            'carrige_forward' => false,
            'covered_cities' => 'all',
            'delivery_time_type' => 'default',
            'start_days_after_order' => 1,
        ], $attributes));

        // تعرفه درون استانی: تا ۱۰ کیلوگرم — 40,000 تومان
        Tariff::create(array_merge([
            'carrier_id' => $carrier->id,
            'type' => 'within_province',
            'max_weight' => 10000,
            'shipping_cost' => 40000,
        ], $tariffAttributes));

        // تعرفه برون استانی: تا ۱۰ کیلوگرم — 60,000 تومان
        Tariff::create(array_merge([
            'carrier_id' => $carrier->id,
            'type' => 'extra_province',
            'max_weight' => 10000,
            'shipping_cost' => 60000,
        ], $tariffAttributes));

        return $carrier;
    }

    /**
     * ساخت درگاه پرداخت فعال.
     */
    protected function newGateway(string $key = 'zarinpal'): Gateway
    {
        $gateway = Gateway::firstOrNew(['key' => $key]);
        $gateway->name = 'درگاه تست '.$key;
        $gateway->is_active = true;
        $gateway->save();

        return $gateway;
    }

    /**
     * ساخت کد تخفیف.
     */
    protected function newDiscount(array $attributes = []): Discount
    {
        return Discount::create(array_merge([
            'title' => 'تخفیف تست '.Str::random(4),
            'code' => 'SAVE-'.Str::random(6),
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
            'type' => 'percent',
            'amount' => 10,
            'published' => true,
            'include_type' => 'all',
            'exclude_type' => 'none',
        ], $attributes));
    }

    /**
     * افزودن محصول به سبد کاربر (مستقیم از طریق مدل).
     */
    protected function addToCart(User $user, Product $product, ?Price $price = null, int $quantity = 1): Cart
    {
        $price = $price ?: $product->prices()->first();

        $cart = $user->getCart();

        $existing = $cart->products()
            ->wherePivot('product_id', $product->id)
            ->wherePivot('price_id', $price->id)
            ->first();

        if ($existing) {
            $cart->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('price_id', $price->id)
                ->updateExistingPivot($product->id, [
                    'quantity' => $existing->pivot->quantity + $quantity,
                ]);
        } else {
            $cart->products()->attach([
                $product->id => [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price_id' => $price->id,
                ],
            ]);
        }

        return $cart->fresh();
    }

    /**
     * ساخت دلیل مرجوعی.
     */
    protected function newReturnReason(): ReturnReason
    {
        return ReturnReason::create([
            'title' => 'مغایرت کالا',
            'is_active' => true,
        ]);
    }

    /**
     * ساخت سفارش پرداخت‌شده ساده برای تست‌های تاریخچه/مرجوعی.
     */
    protected function newPaidOrder(User $user, Product $product, array $orderAttributes = [], array $itemAttributes = [])
    {
        $price = $product->prices()->first();

        $order = $user->orders()->create(array_merge([
            'name' => 'خریدار تست',
            'mobile' => $user->username,
            'province_id' => 1,
            'city_id' => 1,
            'postal_code' => '1234567890',
            'address' => 'آدرس تست',
            'shipping_cost' => 0,
            'price' => $price->salePrice(),
            'status' => 'paid',
            'paid_at' => now(),
        ], $orderAttributes));

        $item = $order->items()->create(array_merge([
            'product_id' => $product->id,
            'title' => $product->title,
            'price' => $price->salePrice(),
            'real_price' => $price->price,
            'quantity' => 1,
            'discount' => 0,
            'price_id' => $price->id,
            'product_type' => $product->type,
            'shipping_status' => 'w-pending',
        ], $itemAttributes));

        return [$order, $item];
    }
}
