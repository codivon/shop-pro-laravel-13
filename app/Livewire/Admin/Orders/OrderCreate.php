<?php

namespace App\Livewire\Admin\Orders;

use App\Events\OrderCreated;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layout')]
#[Title('ثبت سفارش جدید')]
class OrderCreate extends Component
{
    /* ---------------- اطلاعات کاربر ---------------- */

    public string $username = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $userSearch = '';

    public $userResults = null;

    public bool $showUserResults = false;

    /* ---------------- آدرس ---------------- */

    public $provinceId = '';

    public $cityId = '';

    public string $postalCode = '';

    public string $address = '';

    /* ---------------- محصولات ---------------- */

    public string $productSearch = '';

    public $productResults = null;

    public bool $showProductResults = false;

    public array $orderProducts = [];

    /* ---------------- اطلاعات سفارش ---------------- */

    public $carrierId = '';

    public string $shippingCost = '';

    public string $discountAmount = '';

    public string $shippingStatus = 'pending';

    public string $description = '';

    public bool $saving = false;

    public function mount(): void
    {
        if (! auth('adminPanel')->user()?->can('orders.create')) {
            abort(403);
        }
    }

    /* ---------------- مراجع (کش‌شده) ---------------- */

    #[Computed]
    public function provinces()
    {
        return Province::detectLang()->orderBy('ordering')->get(['id', 'name']);
    }

    #[Computed]
    public function carriers()
    {
        return Carrier::active()->get(['id', 'title']);
    }

    /* ---------------- جستجوی کاربر (مطابق orders/api/userInfo) ---------------- */

    public function updatedUserSearch(): void
    {
        if (mb_strlen(trim($this->userSearch)) < 3) {
            $this->userResults = null;
            $this->showUserResults = false;

            return;
        }

        $term = $this->userSearch;

        $this->userResults = User::query()
            ->where('username', 'like', "%{$term}%")
            ->latest()
            ->take(10)
            ->get(['id', 'username', 'first_name', 'last_name'])
            ->map(function (User $user) {
                $address = $user->address()->first(['province_id', 'city_id', 'postal_code', 'address']);

                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'first_name' => (string) $user->first_name,
                    'last_name' => (string) $user->last_name,
                    'address' => $address ? [
                        'province_id' => $address->province_id,
                        'city_id' => $address->city_id,
                        'postal_code' => (string) $address->postal_code,
                        'address' => (string) $address->address,
                    ] : null,
                ];
            })
            ->values()->all();

        $this->showUserResults = ! empty($this->userResults);
    }

    public function selectUser(int $userId): void
    {
        $user = User::with('address')->find($userId);

        if (! $user) {
            return;
        }

        $this->username = (string) $user->username;
        $this->firstName = (string) $user->first_name;
        $this->lastName = (string) $user->last_name;
        $this->userSearch = (string) $user->username;

        if ($address = $user->address()->first()) {
            $this->provinceId = $address->province_id ?? '';
            $this->cityId = $address->city_id ?? '';
            $this->postalCode = (string) $address->postal_code;
            $this->address = (string) $address->address;
        }

        $this->showUserResults = false;
        $this->userResults = null;
    }

    /* ---------------- جستجوی محصول (مطابق orders/api/productsList) ---------------- */

    public function updatedProductSearch(): void
    {
        if (mb_strlen(trim($this->productSearch)) < 3) {
            $this->productResults = null;
            $this->showProductResults = false;

            return;
        }

        $term = $this->productSearch;

        $baseQuery = fn () => Product::query()
            ->with(['prices' => fn ($q) => $q->select('id', 'product_id', 'price', 'discount_price', 'regular_price', 'discount', 'discount_expire_at', 'stock', 'cart_max', 'attribute_hash')])
            ->available()
            ->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('title_en', 'like', "%{$term}%");
            });

        // orderByStock روی MySQL با if() کار می‌کند؛ در سایر درایورها (مثل sqlite) به‌صورت ایمن بازمی‌گردیم
        try {
            $products = $baseQuery()->orderByStock()->latest()->take(10)->get();
        } catch (\Throwable) {
            $products = $baseQuery()->latest()->take(10)->get();
        }

        $this->productResults = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->imageUrl(),
                'prices' => $product->prices->map(function (Price $price) {
                    return [
                        'id' => $price->id,
                        'label' => trim($price->getAttributesName()) ?: 'نسخه پیش‌فرض',
                        'sale' => (int) $price->discountPrice(),
                        'regular' => (int) $price->regularPrice(),
                        'discount' => $price->hasDiscount() ? (int) $price->discount : 0,
                        'cart_max' => $price->cart_max,
                        'stock' => (int) $price->stock,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $this->showProductResults = ! empty($this->productResults);
    }

    public function addProduct(int $productId, int $priceId): void
    {
        foreach ($this->orderProducts as $row) {
            if ($row['price_id'] === $priceId) {
                $this->dispatch('toast', message: 'این نسخه از محصول قبلاً به سفارش اضافه شده است.', type: 'error');

                return;
            }
        }

        $product = Product::query()
            ->with(['prices' => fn ($q) => $q->whereKey($priceId)])
            ->find($productId);

        if (! $product || ! $price = $product->prices->first()) {
            return;
        }

        $this->orderProducts[] = [
            'product_id' => $product->id,
            'price_id' => $price->id,
            'title' => $product->title,
            'image' => $product->imageUrl(),
            'variant' => trim($price->getAttributesName()) ?: 'نسخه پیش‌فرض',
            'sale' => (int) $price->discountPrice(),
            'regular' => (int) $price->regularPrice(),
            'discount' => $price->hasDiscount() ? (int) $price->discount : 0,
            'cart_max' => $price->cart_max,
            'quantity' => 1,
        ];

        $this->productSearch = '';
        $this->productResults = null;
        $this->showProductResults = false;
    }

    public function removeProduct(int $index): void
    {
        unset($this->orderProducts[$index]);
        $this->orderProducts = array_values($this->orderProducts);
    }

    /* ---------------- محاسبات زنده ---------------- */

    #[Computed]
    public function productsTotal(): int
    {
        return (int) collect($this->orderProducts)
            ->sum(fn ($row) => (int) $row['sale'] * (int) $row['quantity']);
    }

    #[Computed]
    public function regularTotal(): int
    {
        return (int) collect($this->orderProducts)
            ->sum(fn ($row) => (int) $row['regular'] * (int) $row['quantity']);
    }

    #[Computed]
    public function finalPrice(): int
    {
        return max(0, $this->productsTotal + (int) str_replace(',', '', $this->shippingCost ?: '0') - (int) str_replace(',', '', $this->discountAmount ?: '0'));
    }

    /* ---------------- ذخیره (پورت کامل OrderController@store) ---------------- */

    public function store(): void
    {
        if (! auth('adminPanel')->user()?->can('orders.create')) {
            abort(403);
        }

        $shippingCost = (int) str_replace(',', '', $this->shippingCost ?: '0');
        $discountAmount = (int) str_replace(',', '', $this->discountAmount ?: '0');

        $rules = [
            'username' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            'provinceId' => 'required|exists:provinces,id',
            'cityId' => 'required|exists:cities,id',
            'postalCode' => 'nullable|string',
            'address' => 'nullable|string',
            'orderProducts' => 'required|array|min:1',
            'orderProducts.*.product_id' => 'required|exists:products,id',
            'orderProducts.*.price_id' => 'required|exists:prices,id',
            'orderProducts.*.quantity' => 'required|numeric|min:1',
            'discountAmount' => 'nullable|numeric|min:0',
            'carrierId' => 'nullable|exists:carriers,id',
            'shippingCost' => 'nullable|numeric|min:0',
            'shippingStatus' => 'required|in:pending,waiting,sent,canceled',
            'description' => 'nullable|string',
        ];

        $messages = [
            'username.required' => 'نام کاربری مشتری الزامی است',
            'firstName.required' => 'نام مشتری الزامی است',
            'lastName.required' => 'نام خانوادگی مشتری الزامی است',
            'provinceId.required' => 'انتخاب استان الزامی است',
            'cityId.required' => 'انتخاب شهر الزامی است',
            'orderProducts.required' => 'حداقل یک محصول به سفارش اضافه کنید',
            'orderProducts.min' => 'حداقل یک محصول به سفارش اضافه کنید',
            'orderProducts.*.quantity.min' => 'تعداد هر محصول باید حداقل ۱ باشد',
            'shippingStatus.required' => 'انتخاب وضعیت ارسال الزامی است',
        ];

        try {
            $this->validate($rules, $messages);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();
            $this->dispatch('toast', message: $first ?? 'خطا در اعتبارسنجی فرم', type: 'error');
            throw $e;
        }

        // بررسی موجودی و حداکثر خرید (مطابق بررسی‌های Price)
        foreach ($this->orderProducts as $row) {
            /** @var Price|null $price */
            $price = Price::query()->find($row['price_id']);

            if (! $price || (int) $price->product_id !== (int) $row['product_id']) {
                $this->dispatch('toast', message: 'نسخه انتخابی محصول نامعتبر است.', type: 'error');

                return;
            }

            $quantity = (int) $row['quantity'];

            if ($price->stock < $quantity || ($price->cart_max !== null && $price->cart_max < $quantity)) {
                $this->dispatch('toast', message: 'موجودی محصول «' . $price->product->title . ' ' . $price->getAttributesName() . '» کافی نیست.', type: 'error');

                return;
            }
        }

        $order = DB::transaction(function () use ($shippingCost, $discountAmount) {
            $user = User::firstOrCreate(
                ['username' => $this->username],
                ['first_name' => $this->firstName, 'last_name' => $this->lastName]
            );

            $orderPrice = 0;
            $orderItems = [];

            foreach ($this->orderProducts as $row) {
                $product = Product::find($row['product_id']);
                $price = $product->prices()->find($row['price_id']);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => $price->discountPrice(),
                    'real_price' => $price->tomanPrice(),
                    'quantity' => (int) $row['quantity'],
                    'discount' => $price->discount,
                    'price_id' => $price->id,
                ];

                $orderPrice += $price->discountPrice() * (int) $row['quantity'];
            }

            $orderPrice += $shippingCost;
            $orderPrice -= $discountAmount;

            $order = Order::create([
                'user_id' => $user->id,
                'name' => $this->firstName . ' ' . $this->lastName,
                'mobile' => $this->username,
                'province_id' => $this->provinceId,
                'city_id' => $this->cityId,
                'postal_code' => $this->postalCode,
                'carrier_id' => $this->carrierId ?: null,
                'address' => $this->address,
                'description' => $this->description,
                'shipping_cost' => $shippingCost ?: 0,
                'status' => 'paid',
                'shipping_status' => $this->shippingStatus,
                'discount_amount' => $discountAmount,
                'price' => $orderPrice,
            ]);

            $order->items()->createMany($orderItems);

            return $order;
        });

        // اگر کاربر آدرس ثبت‌شده ندارد، آدرس سفارش را برایش ثبت کنیم تا
        // لیسنر OrderCreated\SaveAddress هنگام ساخت آدرس به خطای NOT NULL نخورد
        if ($order->hasPhysicalProduct() && $order->user && ! $order->user->address()->exists()) {
            $order->user->address()->create([
                'fullname' => trim($this->firstName . ' ' . $this->lastName) ?: $this->username,
                'mobile' => $this->username,
                'province_id' => $this->provinceId,
                'city_id' => $this->cityId,
                'province_name' => \App\Models\Province::find($this->provinceId)?->name ?? '',
                'city_name' => \App\Models\City::find($this->cityId)?->name ?? '',
                'postal_code' => $this->postalCode ?: '0',
                'address' => $this->address ?: '—',
                'buildingNumber' => 0,
                'active' => 1,
            ]);
        }

        event(new OrderCreated($order));

        activity()
            ->performedOn($order)
            ->causedBy(auth('adminPanel')->user())
            ->event('created')
            ->withProperties([
                'action' => 'create_order',
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'items_count' => $order->items()->count(),
                'price' => $order->price,
                'ip' => request()->ip(),
            ])
            ->log('مدیر ' . $this->adminName() . ' سفارش دستی با شماره ' . $order->id . ' ثبت کرد');

        $this->saving = true;

        session()->flash('np-toast', 'سفارش با شماره ' . $order->id . ' با موفقیت ثبت شد.');

        $this->redirect(route('np.orders.show', $order->id), navigate: true);
    }

    protected function adminName(): string
    {
        $admin = auth('adminPanel')->user();

        return $admin?->full_name ?? $admin?->name ?? 'مدیر';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.orders.create');
    }
}
