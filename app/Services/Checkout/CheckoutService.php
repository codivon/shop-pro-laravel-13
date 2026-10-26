<?php

namespace App\Services\Checkout;

use App\Events\OrderCreated;
use App\Events\OrderPaid as OrderPaidEvent;
use App\Jobs\CancelOrder;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\City;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Price;
use App\Models\Seller;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\WalletHistory;
use App\Services\HolidayService;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;

/**
 * فاز ۹۵ — سرویس مرکزی تسویه‌حساب (چک‌اوت) برای API نسخهٔ ۱
 * --------------------------------------------------------------------------
 * قواعد کسب‌وکار عیناً از فلوی وب (Theme WeblakShop — MainController::checkout
 * و OrderController::store/pay/payUsingWallet/orderPaid/verify) آینه شده‌اند:
 *
 *  • تفکیک محصولات فیزیکی/دانلودی و گروه‌بندی بر اساس فروشنده (store | seller_N)
 *  • اعتبارسنجی روش ارسال هر گروه (وجود، canUseCarrier، محاسبهٔ هزینه از تعرفه)
 *  • اعتبارسنجی تاریخ تحویل برای روش‌های user_select (HolidayService + جلالی)
 *  • انتقال تخفیف سبد به سفارش (discount_id / discount_percent / discount_price)
 *  • رویداد OrderCreated + جاب CancelOrder با تأخیر option('order_cancel')
 *  • پرداخت کیف پول (فقط مبلغ کامل — بدون شارژ خودکار در v1)
 *  • درگاه شتابی: رزرو موجودی → purchase → ردیف تراکنش با توکن متعلق به API
 *    (بدون نشست — verify از طریق GET|POST /api/v1/payments/verify/{token})
 *  • درگاه ماژولی (payyar/snappay/digipay) فقط اگر module_is_active باشد
 *
 * تفاوت‌های مستند v1 نسبت به وب:
 *  • پرداخت اقساطی (installment_enabled) و خرید اعتباری (credit_enabled) از
 *    طریق API پشتیبانی نمی‌شود و با ۴۲۲ پیام فارسی رد می‌شود.
 *  • کسری کیف پول به‌صورت خودکار به درگاه بانکی منتقل نمی‌شود (رفتار وب) —
 *    خطای تمیز ۴۲۲ برگردانده می‌شود.
 */
class CheckoutService
{
    public function __construct(
        protected HolidayService $holidayService,
        protected StockMovementService $stockService,
    ) {}

    /* =========================================================================
     |  خلاصهٔ صفحهٔ چک‌اوت (GET /api/v1/checkout)
     * ========================================================================= */

    /**
     * همهٔ داده‌های لازم برای رندر صفحهٔ تسویه‌حساب.
     */
    public function summary($user, ?int $cityId = null): array
    {
        $cart = $user->cart;

        if (!$cart || !$cart->products->count()) {
            throw new CheckoutException('سبد خرید شما خالی است.');
        }

        if (!check_cart_quantity()) {
            throw new CheckoutException('موجودی برخی از کالاهای سبد شما تغییر کرده است. لطفاً سبد خرید را به‌روزرسانی کنید.');
        }

        $discountStatus = check_cart_discount();

        if (!$discountStatus['status']) {
            throw new CheckoutException($discountStatus['message'] ?? 'کد تخفیف سبد خرید معتبر نیست.');
        }

        // شهر مبنای محاسبهٔ ارسال: پارامتر city_id یا شهر آدرس فعال/اول کاربر
        $cityId = $cityId ?: $this->defaultCityId($user);

        // canUseCarrier روی ستون city_id سبد کار می‌کند — فقط در حافظه تنظیم می‌شود
        $cart->city_id = $cityId;

        $physicalProducts = $cart->products->filter(fn ($p) => $p->type === 'physical');
        $downloadProducts = $cart->products->filter(fn ($p) => $p->type === 'download');

        $items = [];
        $groups = [];
        $physicalTotal = 0;
        $totalWeight = 0;

        foreach ($physicalProducts as $product) {
            $price = Price::with(['seller'])->find($product->pivot->price_id);

            if (!$price) {
                continue;
            }

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = $this->newGroup($groupId, $sellerId);
            }

            $itemPayload = [
                'product_id'     => $product->id,
                'title'          => $product->title,
                'slug'           => $product->slug,
                'image'          => $product->image ? asset($product->image) : null,
                'type'           => 'physical',
                'quantity'       => $product->pivot->quantity,
                'price'          => $price->price,
                'discount_price' => $price->discount_price ?? $price->price,
                'price_id'       => $price->id,
                'weight'         => ($product->weight ?? 0) * $product->pivot->quantity,
                'group_id'       => $groupId,
            ];

            $items[] = $itemPayload;

            $groups[$groupId]['items'][] = $itemPayload;
            $groups[$groupId]['total_weight'] += $itemPayload['weight'];
            $groups[$groupId]['total_price'] += $itemPayload['discount_price'] * $itemPayload['quantity'];

            $physicalTotal += $itemPayload['discount_price'] * $itemPayload['quantity'];
            $totalWeight += $itemPayload['weight'];
        }

        // روش‌های ارسال قابل استفاده برای هر گروه فیزیکی (آینهٔ MainController::checkout)
        foreach ($groups as $groupId => &$group) {
            $carriers = Carrier::active()
                ->when($group['is_store'], fn ($query) => $query->whereNull('seller_id'))
                ->when(!$group['is_store'], fn ($query) => $query->where('seller_id', $group['seller_id']))
                ->get();

            foreach ($carriers as $carrier) {
                $usable = $cart->canUseCarrier($carrier->id);

                if (!$usable['status']) {
                    continue;
                }

                $shippingCost = $this->calculateCarrierCost($carrier, $group['total_weight'], $cityId);

                if ($shippingCost === false) {
                    continue;
                }

                $group['carriers'][] = [
                    'id'             => $carrier->id,
                    'title'          => $carrier->title,
                    'image'          => $carrier->image ? asset($carrier->image) : null,
                    'description'    => $carrier->description,
                    'shipping_cost'  => $shippingCost,
                    'is_free'        => $shippingCost == 0,
                    'delivery_type'  => $carrier->delivery_time_type,
                    'delivery_text'  => $carrier->delivery_time_type == 'default'
                        ? ($carrier->default_delivery_range ?? 'ارسال در 3 الی 6 روز کاری')
                        : null,
                    'delivery_dates' => $carrier->delivery_time_type == 'user_select'
                        ? $this->getDeliveryDates($carrier)
                        : null,
                    'carrige_forward' => (bool) $carrier->carrige_forward,
                ];
            }

            // پیش‌فرض: اولین روش ارسال قابل استفاده (مطابق انتخاب وب)
            if (!empty($group['carriers'])) {
                $group['selected_carrier_id'] = $group['carriers'][0]['id'];
                $group['shipping_cost'] = $group['carriers'][0]['shipping_cost'];
                $group['delivery_info'] = $group['carriers'][0]['delivery_text'];
            }
        }
        unset($group);

        $downloadItems = [];
        $downloadTotal = 0;

        foreach ($downloadProducts as $product) {
            $price = Price::find($product->pivot->price_id);

            if (!$price) {
                continue;
            }

            $finalPrice = $price->discount_price ?? $price->price;
            $downloadTotal += $finalPrice * $product->pivot->quantity;

            $downloadItems[] = [
                'product_id'     => $product->id,
                'title'          => $product->title,
                'slug'           => $product->slug,
                'image'          => $product->image ? asset($product->image) : null,
                'type'           => 'download',
                'quantity'       => $product->pivot->quantity,
                'price'          => $price->price,
                'discount_price' => $finalPrice,
                'price_id'       => $price->id,
                'group_id'       => null,
            ];
        }

        $items = array_merge($items, $downloadItems);

        $shippingTotal = array_sum(array_column($groups, 'shipping_cost'));
        $discountAmount = $cart->totalDiscount();

        $wallet = $user->getWallet();

        return [
            'items'          => $items,
            'download_items' => $downloadItems,
            'groups'         => array_values($groups),
            'totals'         => [
                'physical_total'  => $physicalTotal,
                'download_total'  => $downloadTotal,
                'shipping_total'  => $shippingTotal,
                'discount_amount' => $discountAmount,
                'final_price'     => max(0, $physicalTotal + $downloadTotal + $shippingTotal - $discountAmount),
            ],
            'weight'         => $totalWeight,
            'addresses'      => $this->userAddresses($user),
            'payment'        => [
                'wallet'          => [
                    'enabled' => true,
                    'balance' => (int) $wallet->balance(),
                ],
                'gateways'        => Gateway::active()->orderBy('ordering')->get()
                    ->map(fn ($g) => ['key' => $g->key, 'title' => $g->name])->values()->all(),
                'module_gateways' => $this->activeModuleGateways(),
            ],
            'discount'       => $this->cartDiscountPayload($cart),
            'city_id'        => $cityId,
        ];
    }

    /* =========================================================================
     |  ثبت سفارش (POST /api/v1/checkout)
     * ========================================================================= */

    /**
     * ثبت سفارش از سبد کاربر و حل روش پرداخت — آینهٔ کامل OrderController::store.
     *
     * خروجی: ['order' => Order, 'paid' => bool, 'payment' => ?array, 'token' => ?string]
     */
    public function placeOrder($user, array $data, ?Request $request = null): array
    {
        $cart = $user->cart;

        if (!$cart || !$cart->products->count() || !check_cart_quantity()) {
            throw new CheckoutException('سبد خرید شما خالی است یا موجودی کالاها تغییر کرده است.');
        }

        $discountStatus = check_cart_discount();

        if (!$discountStatus['status']) {
            throw new CheckoutException($discountStatus['message'] ?? 'کد تخفیف سبد خرید معتبر نیست.');
        }

        $physicalProducts = $cart->products->filter(fn ($p) => $p->type === 'physical');
        $downloadProducts = $cart->products->filter(fn ($p) => $p->type === 'download');

        $hasPhysical = $physicalProducts->isNotEmpty();

        $address = null;

        if ($hasPhysical) {
            $address = Address::find($data['address'] ?? null);

            if (!$address) {
                throw new CheckoutException('انتخاب آدرس برای محصولات فیزیکی الزامی است.');
            }
        }

        // شهر مبنای اعتبارسنجی روش ارسال = شهر آدرس
        $cityId = $address?->city_id ?? $this->defaultCityId($user);

        // canUseCarrier روی cart->city_id کار می‌کند — تنظیم فقط در حافظه
        $cart->city_id = $cityId;

        // ========== گروه‌بندی فیزیکی‌ها بر اساس فروشنده ==========
        $sellerGroups = [];
        $physicalTotal = 0;

        foreach ($physicalProducts as $product) {
            $price = Price::with(['seller'])->find($product->pivot->price_id);

            if (!$price) {
                continue;
            }

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            if (!isset($sellerGroups[$groupId])) {
                $group = $this->newGroup($groupId, $sellerId);
                $group['carrier_id'] = $data['carriers'][$groupId] ?? null;
                $group['delivery_date'] = $data['delivery_dates'][$groupId] ?? null;
                $sellerGroups[$groupId] = $group;
            }

            $finalPrice = $price->discount_price ?? $price->price;

            $sellerGroups[$groupId]['products'][] = [
                'product'     => $product,
                'price'       => $price,
                'quantity'    => $product->pivot->quantity,
                'weight'      => ($product->weight ?? 0) * $product->pivot->quantity,
                'final_price' => $finalPrice,
            ];

            $sellerGroups[$groupId]['total_weight'] += ($product->weight ?? 0) * $product->pivot->quantity;
            $sellerGroups[$groupId]['price'] += $price->price * $product->pivot->quantity;
            $sellerGroups[$groupId]['total_price'] += $finalPrice * $product->pivot->quantity;

            $physicalTotal += $finalPrice * $product->pivot->quantity;
        }

        // ========== قیمت محصولات دانلودی ==========
        $downloadTotalPrice = 0;
        $downloadItems = [];

        foreach ($downloadProducts as $product) {
            $price = Price::find($product->pivot->price_id);

            if (!$price) {
                continue;
            }

            $finalPrice = $price->discount_price ?? $price->price;
            $downloadTotalPrice += $finalPrice * $product->pivot->quantity;

            $downloadItems[] = [
                'product'     => $product,
                'price'       => $price,
                'quantity'    => $product->pivot->quantity,
                'final_price' => $finalPrice,
            ];
        }

        // ========== اعتبارسنجی روش ارسال و تاریخ تحویل هر گروه (فقط فیزیکی) ==========
        $totalShippingCost = 0;
        $errors = [];

        foreach ($sellerGroups as $groupId => &$group) {
            if (!$group['carrier_id']) {
                $errors[] = "روش ارسال برای مرسوله {$group['name']} انتخاب نشده است";
                continue;
            }

            $carrier = Carrier::find($group['carrier_id']);

            if (!$carrier) {
                $errors[] = "روش ارسال نامعتبر برای مرسوله {$group['name']}";
                continue;
            }

            $carrierResult = $cart->canUseCarrier($carrier->id);

            if (!$carrierResult['status']) {
                $errors[] = "روش ارسال {$carrier->title} برای مرسوله {$group['name']} قابل استفاده نیست: {$carrierResult['message']}";
                continue;
            }

            $shippingCost = $this->calculateCarrierCost($carrier, $group['total_weight'], $cityId);

            if ($shippingCost === false) {
                $errors[] = "محاسبه هزینه ارسال برای مرسوله {$group['name']} امکان پذیر نیست";
                continue;
            }

            $group['shipping_cost'] = $shippingCost;
            $totalShippingCost += $shippingCost;

            // تاریخ تحویل فقط برای روش‌های user_select الزامی و باید قابل انتخاب باشد
            if ($carrier->delivery_time_type == 'user_select') {
                if (!$group['delivery_date']) {
                    $errors[] = "لطفاً تاریخ تحویل برای مرسوله {$group['name']} را انتخاب کنید";
                    continue;
                }

                $isValidDate = false;

                foreach ($this->getDeliveryDates($carrier) as $date) {
                    if ($date['date'] == $group['delivery_date'] && $date['is_selectable']) {
                        $isValidDate = true;
                        break;
                    }
                }

                if (!$isValidDate) {
                    $errors[] = "تاریخ تحویل انتخاب شده برای مرسوله {$group['name']} معتبر نیست";
                    continue;
                }
            }
        }
        unset($group);

        if ($hasPhysical && empty($sellerGroups)) {
            $errors[] = 'محصولات فیزیکی یافت نشد';
        }

        if (!empty($errors)) {
            throw new CheckoutException(implode('، ', $errors));
        }

        // ========== پیش‌شرط کیف پول قبل از ساخت سفارش (v1: بدون شارژ خودکار) ==========
        $finalPrice = $physicalTotal + $downloadTotalPrice + $totalShippingCost - $cart->totalDiscount();
        $finalPrice = max(0, $finalPrice);

        if (($data['gateway'] ?? null) === 'wallet' && $finalPrice > 0) {
            $walletBalance = (int) $user->getWallet()->balance();

            if ($walletBalance < $finalPrice) {
                throw new CheckoutException(sprintf(
                    'موجودی کیف پول شما (%s تومان) برای پرداخت مبلغ سفارش (%s تومان) کافی نیست. لطفاً کیف پول خود را شارژ کنید یا از درگاه بانکی استفاده کنید.',
                    number_format($walletBalance),
                    number_format($finalPrice)
                ));
            }
        }

        // ========== ایجاد سفارش + آیتم‌ها (اتمیک) ==========
        $order = DB::transaction(function () use ($user, $cart, $data, $address, $sellerGroups, $downloadItems, $physicalProducts, $hasPhysical, $finalPrice, $totalShippingCost) {
            $gateway = Gateway::where('key', $data['gateway'])->first();

            $payload = [
                'shipping_cost'   => $totalShippingCost,
                'price'           => $finalPrice,
                'status'          => 'unpaid',
                'discount_amount' => $cart->totalDiscount(),
                'discount_id'     => $cart->discount_id,
                'user_id'         => $user->id,
                'description'     => $data['description'] ?? null,
            ];

            if ($cart->discount) {
                if ($cart->discount->type == 'percent') {
                    $payload['discount_percent'] = $cart->discount->amount;
                } else {
                    $payload['discount_price'] = $cart->discount->amount;
                }
            }

            if ($hasPhysical) {
                $payload['name'] = $address->fullname;
                $payload['mobile'] = $address->mobile;
                $payload['province_id'] = $address->province_id;
                $payload['city_id'] = $address->city_id;
                $payload['postal_code'] = $address->postal_code;
                $payload['address'] = $address->address;
                $payload['location'] = $address->lat . ',' . $address->lng;
            } else {
                $payload['name'] = $user->name ?? '';
                $payload['mobile'] = $user->mobile ?? '';
            }

            if ($gateway) {
                $payload['gateway_id'] = $gateway->id;
            }

            $order = Order::create($payload);

            $sellerIds = [];

            // -- آیتم‌های فیزیکی (همان ستون‌های فلوی وب) --
            foreach ($physicalProducts as $product) {
                $price = Price::with(['seller'])->find($product->pivot->price_id);

                if (!$price) {
                    continue;
                }

                $groupId = $price->seller_id ? 'seller_' . $price->seller_id : 'store';
                $group = $sellerGroups[$groupId] ?? null;

                $order->items()->create([
                    'product_id'    => $product->id,
                    'seller_id'     => $price->seller_id,
                    'title'         => $product->title,
                    'price'         => $price->discount_price ?? $price->price,
                    'real_price'    => $price->price,
                    'quantity'      => $product->pivot->quantity,
                    'discount'      => $price->discount ?? 0,
                    'price_id'      => $product->pivot->price_id,
                    'shipping_cost' => $group['shipping_cost'] ?? 0,
                    'carrier_id'    => $group['carrier_id'] ?? null,
                    'delivery_date' => $group['delivery_date'] ?? null,
                    'attributes'    => $this->getProductAttributes($price),
                    'product_type'  => 'physical',
                ]);

                if ($price->seller_id) {
                    $sellerIds[] = $price->seller_id;
                }
            }

            // -- آیتم‌های دانلودی --
            foreach ($downloadItems as $item) {
                $product = $item['product'];
                $price = $item['price'];

                $order->items()->create([
                    'product_id'      => $product->id,
                    'seller_id'       => $price->seller_id,
                    'title'           => $product->title,
                    'price'           => $item['final_price'],
                    'real_price'      => $price->price,
                    'quantity'        => $item['quantity'],
                    'discount'        => $price->discount ?? 0,
                    'price_id'        => $product->pivot->price_id,
                    'shipping_cost'   => 0,
                    'carrier_id'      => null,
                    'delivery_date'   => null,
                    'attributes'      => $this->getProductAttributes($price),
                    'product_type'    => 'download',
                    'is_downloadable' => true,
                ]);

                if ($price->seller_id) {
                    $sellerIds[] = $price->seller_id;
                }
            }

            if (count($sellerIds)) {
                $order->update(['seller_id' => json_encode(array_unique($sellerIds))]);
            }

            return $order;
        });

        // جاب لغو خودکار سفارش پرداخت‌نشده + رویداد ایجاد سفارش
        $hour = (int) option('order_cancel', 1) ?: 1;
        CancelOrder::dispatch($order)->delay(now()->addHours($hour));

        event(new OrderCreated($order));

        // ========== حل روش پرداخت (آینهٔ resolvePaymentGateway) ==========
        return $this->resolvePaymentGateway($order, $data, $request);
    }

    /**
     * حل روش پرداخت سفارش تازه‌ساخته‌شده — آینهٔ OrderController::resolvePaymentGateway + pay.
     */
    protected function resolvePaymentGateway(Order $order, array $data, ?Request $request = null): array
    {
        $selectedGateway = $data['gateway'] ?? null;

        // ===== ۱. درگاه‌های ماژولی (payyar / snappay / digipay) =====
        foreach ($this->paymentModuleGateways() as $gatewayKey => $controllerClass) {
            if ($selectedGateway !== $gatewayKey) {
                continue;
            }

            $moduleName = $this->extractModuleName($controllerClass);

            if (!function_exists('module_is_active') || !module_is_active($moduleName)) {
                throw new CheckoutException("ماژول {$moduleName} فعال نیست.");
            }

            try {
                $controller = app($controllerClass);

                $result = $controller->initiatePayment($request ?: request(), $order);
            } catch (\Throwable $e) {
                Log::error('CheckoutService: خطا در درگاه ماژولی', [
                    'order_id' => $order->id,
                    'gateway'  => $gatewayKey,
                    'module'   => $moduleName,
                    'error'    => $e->getMessage(),
                ]);

                throw new CheckoutException("خطا در اتصال به درگاه {$moduleName}. لطفاً بعداً تلاش کنید.");
            }

            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return [
                    'order'   => $order,
                    'paid'    => false,
                    'payment' => ['type' => 'url', 'url' => $result->getTargetUrl()],
                ];
            }

            $html = null;

            if (is_string($result)) {
                $html = $result;
            } elseif (is_object($result) && method_exists($result, 'getContent')) {
                $html = $result->getContent();
            }

            if ($html !== null) {
                return [
                    'order'   => $order,
                    'paid'    => false,
                    'payment' => ['type' => 'html', 'html' => $html],
                ];
            }

            throw new CheckoutException("پاسخ نامعتبر از درگاه {$moduleName}.");
        }

        // ===== ۲. رزرو موجودی فیزیکی (آینهٔ pay() — قبل از هر پرداختی) =====
        try {
            DB::transaction(function () use ($order) {
                $this->reserveOrderStock($order);
            });
        } catch (\Throwable $e) {
            throw new CheckoutException($e->getMessage());
        }

        // ===== ۳. سفارش مبلغ صفر (پرداخت رایگان) =====
        if ((int) $order->price === 0) {
            $this->finalizePaidOrder($order);

            return ['order' => $order->fresh(), 'paid' => true, 'payment' => null];
        }

        // ===== ۴. کیف پول (فقط مبلغ کامل — v1 بدون شارژ خودکار برای کسری) =====
        if ($selectedGateway === 'wallet') {
            $paid = $order->payUsingWallet();

            if (!$paid) {
                throw new CheckoutException(sprintf(
                    'موجودی کیف پول شما برای پرداخت مبلغ سفارش (%s تومان) کافی نیست. لطفاً کیف پول خود را شارژ کنید یا از درگاه بانکی استفاده کنید.',
                    number_format($order->price)
                ));
            }

            $this->finalizePaidOrder($order);

            return ['order' => $order->fresh(), 'paid' => true, 'payment' => null];
        }

        // ===== ۵. درگاه شتابی (جدول gateways) — بدون نشست، با توکن اختصاصی API =====
        $gateway = Gateway::where('key', $selectedGateway)->first();

        if (!$gateway) {
            throw new CheckoutException('روش پرداخت انتخاب‌شده معتبر نیست.');
        }

        // توکن متعلق به همین ردیف تراکنش — کلید verify بدون نشست
        $token = Str::random(64);

        try {
            $gatewayConfigs = get_gateway_configs($gateway->key);

            $response = ShetabitPayment::via($gateway->key)
                ->config($gatewayConfigs)
                ->callbackUrl(route('api.payments.verify', ['token' => $token]))
                ->purchase(
                    (new \Shetabit\Multipay\Invoice)->amount(intval($order->price)),
                    function ($driver, $transactionId) use ($order, $gateway, $token) {
                        // همان ستون‌های فلوی وب؛ token = توکن تولیدشدهٔ API (نه نشست)
                        DB::table('transactions')->insert([
                            'status'               => false,
                            'amount'               => $order->price,
                            'factorNumber'         => $order->id,
                            'mobile'               => $order->mobile,
                            'message'              => 'تراکنش ایجاد شد برای درگاه ' . $gateway->key,
                            'transID'              => (string) $transactionId,
                            'token'                => $token,
                            'user_id'              => $order->user_id,
                            'transactionable_type' => Order::class,
                            'transactionable_id'   => $order->id,
                            'gateway_id'           => $gateway->id,
                            'created_at'           => Carbon::now(),
                            'updated_at'           => Carbon::now(),
                        ]);
                    }
                )->pay();
        } catch (\Throwable $e) {
            Log::error('CheckoutService: خطا در خرید درگاه شتابی', [
                'order_id' => $order->id,
                'gateway'  => $gateway->key,
                'error'    => $e->getMessage(),
            ]);

            throw new CheckoutException('خطا در اتصال به درگاه پرداخت. لطفاً بعداً تلاش کنید.');
        }

        // RedirectionForm (شِتابیت): آدرس اکشن + متد + فیلدهای فرم برای وب‌ویو اپلیکیشن
        $payment = [
            'type'   => 'form',
            'url'    => $response->getAction(),
            'method' => $response->getMethod(),
            'inputs' => $response->getInputs(),
            'token'  => $token,
        ];

        return [
            'order'   => $order,
            'paid'    => false,
            'payment' => $payment,
        ];
    }

    /* =========================================================================
     |  تأیید پرداخت بدون نشست (GET|POST /api/v1/payments/verify/{token})
     * ========================================================================= */

    /**
     * تأیید تراکنش درگاه بر اساس توکن — آینهٔ OrderController::verify بدون session.
     *
     * پارامترهای query/body درگاه در همان Request می‌مانند تا متد verify()
     * شِتابیت آن‌ها را بخواند (callbackUrl واقعی همین روت است).
     */
    public function verifyByToken(string $token): array
    {
        /** @var Transaction|null $transaction */
        $transaction = Transaction::where('status', false)->where('token', $token)->first();

        if (!$transaction) {
            throw new CheckoutException('تراکنش مورد نظر یافت نشد یا قبلاً پردازش شده است.', 404);
        }

        $order = $transaction->transactionable;

        if (!$order instanceof Order) {
            throw new CheckoutException('سفارش مرتبط با این تراکنش یافت نشد.', 404);
        }

        if ($order->status == 'paid') {
            // رفتار idempotent: تراکنش قبلاً تأیید و سفارش پرداخت شده است
            return ['order' => $order, 'already_paid' => true];
        }

        $gateway = $transaction->gateway_id ? Gateway::find($transaction->gateway_id) : null;

        if (!$gateway) {
            throw new CheckoutException('درگاه پرداخت این تراکنش یافت نشد.', 404);
        }

        try {
            $receipt = ShetabitPayment::via($gateway->key)
                ->config(get_gateway_configs($gateway->key))
                ->amount(intval($transaction->amount))
                ->transactionId($transaction->transId ?? $transaction->transID)
                ->verify();

            DB::table('transactions')->where('token', $token)->update([
                'status'       => 1,
                'amount'       => $order->price,
                'factorNumber' => (string) $order->id,
                'mobile'       => $order->mobile,
                'traceNumber'  => $receipt->getReferenceId(),
                'message'      => ($transaction->message ? $transaction->message . '<br>' : '') . 'پرداخت موفق با درگاه ' . $gateway->key,
                'updated_at'   => Carbon::now(),
            ]);

            $this->finalizePaidOrder($order);

            return ['order' => $order->fresh(), 'already_paid' => false];
        } catch (\Throwable $exception) {
            DB::table('transactions')->where('token', $token)->update([
                'message'    => ($transaction->message ? $transaction->message . '<br>' : '') . $exception->getMessage(),
                'updated_at' => Carbon::now(),
            ]);

            Log::warning('CheckoutService: تأیید درگاه ناموفق', [
                'token'    => $token,
                'order_id' => $order->id,
                'error'    => $exception->getMessage(),
            ]);

            throw new CheckoutException('پرداخت تأیید نشد: ' . $exception->getMessage());
        }
    }

    /* =========================================================================
     |  نهایی‌سازی سفارش پرداخت‌شده (آینهٔ OrderController::orderPaid)
     * ========================================================================= */

    /**
     * تأیید رزروها، وضعیت paid، رویداد OrderPaid و پاک‌سازی سبد.
     * در صورت خطا: آزادسازی رزرو + برگشت وجه (کیف پول/تراکنش) + لغو سفارش.
     */
    public function finalizePaidOrder(Order $order): void
    {
        DB::beginTransaction();

        try {
            // بررسی مجدد موجودی با قفل (فقط فیزیکی — پیش‌فروش مستثنی)
            foreach ($order->items as $item) {
                if (!$item->product->isPhysical() || $item->product->pre_order) {
                    continue;
                }

                $price = $item->get_price;

                if (!$price) {
                    throw new \Exception("محصول {$item->title} یافت نشد");
                }

                $lockedPrice = Price::where('id', $price->id)->lockForUpdate()->first();
                $availableStock = $lockedPrice->stock - ($lockedPrice->reserved_stock ?? 0);

                if ($availableStock < $item->quantity) {
                    throw new \Exception(
                        "موجودی محصول {$item->title} کافی نیست. موجودی فعلی: {$availableStock} - درخواستی: {$item->quantity}"
                    );
                }
            }

            // تأیید رزرو و خروج از انبار
            foreach ($order->items as $item) {
                if (!$item->product->isPhysical() || $item->product->pre_order) {
                    continue;
                }

                $price = $item->get_price;

                if ($price) {
                    $this->stockService->confirmReservation(
                        $price,
                        $item->quantity,
                        $order->id,
                        $item->id
                    );

                    $item->update([
                        'stock_before' => $price->stock + $item->quantity,
                        'stock_after'  => $price->stock,
                    ]);
                }
            }

            $order->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::warning('CheckoutService: خطا در نهایی‌سازی سفارش', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            $this->cancelOrderAndRefund($order, $e->getMessage());

            throw new CheckoutException('سفارش شما به دلیل خطا در ثبت موجودی لغو شد: ' . $e->getMessage());
        }

        // رویداد پرداخت موفق (بعد از commit — مطابق وب)
        event(new OrderPaidEvent($order));

        // پاک‌سازی سبد خرید کاربر (آیتم‌ها + کد تخفیف)
        $this->clearCart($order->user);
    }

    /**
     * لغو سفارش و برگشت وجه — آینهٔ OrderController::cancelOrderAndRefund
     * (+ برگشت مستقیم کیف پول برای مسیر wallet که ردیف Transaction ندارد).
     */
    protected function cancelOrderAndRefund(Order $order, string $errorMessage): void
    {
        DB::beginTransaction();

        try {
            $refundAmount = 0;

            // ۱. آزادسازی رزروها
            foreach ($order->items as $item) {
                $price = $item->get_price;

                if ($price && ($price->reserved_stock ?? 0) >= $item->quantity) {
                    DB::table('prices')
                        ->where('id', $price->id)
                        ->where('reserved_stock', '>=', $item->quantity)
                        ->decrement('reserved_stock', $item->quantity);
                }
            }

            $wallet = $order->user->getWallet();

            // ۲. برگشت وجه تراکنش بانکی موفق (مسیر درگاه شتابی)
            $transaction = $order->transactions()
                ->where('status', true)
                ->where('amount', $order->price)
                ->first();

            if ($transaction && $wallet) {
                $refundAmount = $this->depositRefund($order, $wallet, $errorMessage);
            }

            // ۳. برگشت برداشت کیف پول (مسیر wallet — ردیف تراکنش بانکی ندارد)
            if (!$refundAmount) {
                $walletWithdraw = WalletHistory::where('order_id', $order->id)
                    ->where('type', 'withdraw')
                    ->where('status', 'success')
                    ->exists();

                if ($walletWithdraw && $wallet) {
                    $this->depositRefund($order, $wallet, $errorMessage);
                }
            }

            $order->update([
                'status'        => 'cancelled',
                'failed_reason' => $errorMessage,
                'failed_at'     => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('CheckoutService: خطا در لغو و برگشت وجه سفارش', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * واریز برگشت وجه به کیف پول + ثبت تاریخچهٔ refund.
     */
    protected function depositRefund(Order $order, $wallet, string $errorMessage): int
    {
        if (!$wallet->deposit($order->price, "برگشت هزینه سفارش لغو شده #{$order->id} - دلیل: {$errorMessage}")) {
            return 0;
        }

        WalletHistory::create([
            'user_id'     => $order->user_id,
            'type'        => 'refund',
            'amount'      => $order->price,
            'description' => "برگشت هزینه سفارش لغو شده #{$order->id} - {$errorMessage}",
            'status'      => 'success',
            'order_id'    => $order->id,
        ]);

        return $order->price;
    }

    /* =========================================================================
     |  کمکی‌های مشترک (آینهٔ متدهای خصوصی وب)
     * ========================================================================= */

    /**
     * رزرو موجودی آیتم‌های فیزیکی سفارش (آینهٔ بلاک رزرو در pay()).
     */
    protected function reserveOrderStock(Order $order): void
    {
        foreach ($order->items as $item) {
            // فاز ۷۸ — آیتم‌های پیش‌فروش رزرو نمی‌شوند
            if (!$item->product->isPhysical() || $item->product->pre_order) {
                continue;
            }

            $price = $item->get_price;

            if ($price) {
                $availableStock = $price->stock - $price->reserved_stock;

                if ($availableStock < $item->quantity) {
                    throw new \Exception("موجودی کافی برای محصول {$item->product->title} وجود ندارد");
                }

                $this->stockService->reserve(
                    $price,
                    $item->quantity,
                    $order->id,
                    $item->id
                );
            }
        }
    }

    /**
     * پاک‌سازی سبد خرید کاربر: حذف آیتم‌ها + کد تخفیف.
     */
    protected function clearCart($user): void
    {
        $cart = $user->cart;

        if ($cart) {
            $cart->products()->detach();
            $cart->update(['discount_id' => null]);
        }
    }

    /**
     * گروه جدید مرسوله — نام فروشگاه/فروشنده مطابق وب.
     */
    protected function newGroup(string $groupId, ?int $sellerId): array
    {
        $sellerInfo = null;

        if ($sellerId) {
            $seller = Seller::find($sellerId);

            $sellerInfo = $seller ? [
                'id'   => $seller->id,
                'name' => $seller->seller_info->business_name ?? $seller->name,
            ] : null;
        }

        return [
            'group_id'            => $groupId,
            'seller_id'           => $sellerId,
            'seller_info'         => $sellerInfo,
            'is_store'            => is_null($sellerId),
            'name'                => $sellerInfo ? $sellerInfo['name'] : option('info_site_title'),
            'items'               => [],
            'products'            => [],
            'total_weight'        => 0,
            'price'               => 0,
            'total_price'         => 0,
            'carrier_id'          => null,
            'delivery_date'       => null,
            'shipping_cost'       => 0,
            'carriers'            => [],
            'selected_carrier_id' => null,
            'delivery_info'       => null,
        ];
    }

    /**
     * شهر مبنای پیش‌فرض = آدرس فعال، سپس اولین آدرس کاربر.
     */
    protected function defaultCityId($user): ?int
    {
        $address = $user->addresses()->where('active', 1)->first()
            ?: $user->addresses()->first();

        return $address?->city_id;
    }

    /**
     * لیست آدرس‌های کاربر برای رندر چک‌اوت.
     */
    protected function userAddresses($user): array
    {
        return $user->addresses()->orderByDesc('active')->get()
            ->map(fn (Address $address) => [
                'id'          => $address->id,
                'fullname'    => $address->fullname,
                'mobile'      => $address->mobile,
                'province'    => $address->province?->name,
                'city'        => $address->city?->name,
                'province_id' => $address->province_id,
                'city_id'     => $address->city_id,
                'postal_code' => $address->postal_code,
                'address'     => $address->address,
                'active'      => (bool) $address->active,
            ])->all();
    }

    /**
     * اطلاعات تخفیف سبد برای نمایش.
     */
    protected function cartDiscountPayload($cart): ?array
    {
        if (!$cart->discount) {
            return null;
        }

        return [
            'id'             => $cart->discount->id,
            'code'           => $cart->discount->code,
            'type'           => $cart->discount->type,
            'amount'         => $cart->discount->amount,
            'total_discount' => $cart->discountCodeTotal(),
        ];
    }

    /**
     * محاسبهٔ هزینهٔ ارسال از تعرفه — آینهٔ calculateCarrierCost وب.
     */
    public function calculateCarrierCost($carrier, $weight, $cityId)
    {
        if ($carrier->free_shipping_weight && $weight >= $carrier->free_shipping_weight) {
            return 0;
        }

        $destinationCity = City::find($cityId);

        if (!$destinationCity) {
            return false;
        }

        $destinationProvinceId = $destinationCity->province_id;
        $carrierProvinceId = $carrier->province_id;
        $isWithinProvince = ($destinationProvinceId == $carrierProvinceId);
        $type = $isWithinProvince ? 'within_province' : 'extra_province';

        $tariff = Tariff::where('carrier_id', $carrier->id)
            ->where('type', $type)
            ->where('max_weight', '>=', $weight)
            ->orderBy('max_weight', 'asc')
            ->first();

        if ($tariff) {
            return $tariff->shipping_cost;
        }

        if ($carrier->extra_cost) {
            return $weight * $carrier->extra_cost;
        }

        return false;
    }

    /**
     * تاریخ‌های قابل انتخاب ارسال برای روش user_select — آینهٔ getDeliveryDates وب.
     */
    public function getDeliveryDates($carrier): array
    {
        $rangeDays = (int) $carrier->user_select_ranges;

        if (!$rangeDays || $rangeDays <= 0) {
            $rangeDays = 7;
        }

        $startDate = now()->addDays($carrier->start_days_after_order);
        $dates = [];

        for ($i = 0; $i < $rangeDays; $i++) {
            $currentDate = clone $startDate;
            $currentDate->addDays($i);

            $jalali = Jalalian::fromDateTime($currentDate);
            $isFriday = $currentDate->dayOfWeek == 5;
            $isHoliday = $this->isHoliday($currentDate);

            $isSelectable = true;
            if ($carrier->disable_fridays && $isFriday) {
                $isSelectable = false;
            }
            if ($carrier->disable_holidays && $isHoliday) {
                $isSelectable = false;
            }

            $dates[] = [
                'date'          => $currentDate->format('Y-m-d'),
                'jalali'        => $jalali->format('Y/m/d'),
                'display'       => $jalali->format('l j F Y'),
                'displayDate'   => $jalali->format('j F Y'),
                'day_name'      => $jalali->format('l'),
                'is_friday'     => $isFriday,
                'is_holiday'    => $isHoliday,
                'is_selectable' => $isSelectable,
            ];
        }

        return $dates;
    }

    /**
     * تعطیلی تاریخ (HolidayService با تاریخ جلالی).
     */
    protected function isHoliday($date): bool
    {
        $jalali = Jalalian::fromDateTime($date);

        return $this->holidayService->isHoliday($jalali->format('Y/m/d'));
    }

    /**
     * ویژگی‌های محصول برای آیتم سفارش — آینهٔ getProductAttributes وب.
     */
    protected function getProductAttributes($price): string
    {
        $allAttributes = [];
        $getAttributes = $price->get_attributes()->with('group')->get();

        foreach ($getAttributes as $attribute) {
            $attributeGroupsName = $attribute->group->name;

            if (!isset($allAttributes[$attributeGroupsName])) {
                $allAttributes[$attributeGroupsName] = [];
            }

            $allAttributes[$attributeGroupsName][] = [
                'name'  => $attribute->name,
                'value' => $attribute->value,
            ];
        }

        return json_encode($allAttributes);
    }

    /* =========================================================================
     |  درگاه‌های ماژولی (آینهٔ OrderController::paymentModuleGateways)
     * ========================================================================= */

    /**
     * نقشهٔ کلید → کنترلر درگاه‌های ماژولی.
     */
    public function paymentModuleGateways(): array
    {
        return [
            // فاز ۸۷ — پکیج «پرداخت‌یار» (PayYar): درگاه مستقیم + اسنپ‌پی
            'payyar'  => \Modules\PayYar\Http\Controllers\PayYarController::class,
            'snappay' => \Modules\PayYar\Http\Controllers\SnappPayController::class,
            // سازگاری نصب‌های قدیمی پکیج «درگاه‌های پرداخت و اقساطی» (DigiPay)
            'digipay' => \Modules\DigiPay\Http\Controllers\DigiPayController::class,
        ];
    }

    /**
     * کلیدهای درگاه‌های ماژولیِ فعال — آینهٔ StoreOrderRequest::paymentModuleGateways.
     */
    public function privateGatewayKeys(): array
    {
        $gateways = [];

        if (function_exists('module_is_active') && module_is_active('PayYar')) {
            $gateways[] = 'payyar';
            $gateways[] = 'snappay';
        }

        if (function_exists('module_is_active') && module_is_active('DigiPay')) {
            if (!in_array('payyar', $gateways)) {
                $gateways[] = 'digipay';
            }
            if (!in_array('snappay', $gateways)) {
                $gateways[] = 'snappay';
            }
        }

        return $gateways;
    }

    /**
     * درگاه‌های ماژولی فعال برای بلوک payment_methods خلاصهٔ چک‌اوت.
     */
    protected function activeModuleGateways(): array
    {
        $map = [
            'payyar'  => 'PayYar',
            'snappay' => 'PayYar',
            'digipay' => 'DigiPay',
        ];

        $result = [];

        foreach ($this->privateGatewayKeys() as $key) {
            $result[] = ['key' => $key, 'module' => $map[$key] ?? $key];
        }

        return $result;
    }

    /**
     * استخراج نام ماژول از namespace کنترلر — آینهٔ extractModuleName وب.
     */
    protected function extractModuleName(string $controllerClass): string
    {
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $controllerClass, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
