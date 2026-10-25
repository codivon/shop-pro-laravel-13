<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid as OrderPaidEvent;
use App\Http\Controllers\Controller;
use App\Jobs\CancelOrder;
use App\Models\Address;
use App\Models\Carrier;
use App\Models\City;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Price;
use App\Models\Seller;
use App\Models\SellerVariant;
use App\Models\Sms;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\WalletHistory;
use App\Notifications\Order\OrderCancelled as OrderCancelledNotification;
use App\Notifications\Sms\OrderCancelledSms;
use App\Services\HolidayService;
use App\Services\Sms\SmsService;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice;
use Themes\WeblakShop\src\Requests\StoreOrderRequest;

class OrderController extends Controller
{
    protected $holidayService;
    protected StockMovementService $stockService;

    public function __construct(HolidayService $holidayService,StockMovementService $stockService)
    {
        $this->holidayService = $holidayService;
        $this->stockService = $stockService;
    }
    public function index()
    {
        $query = auth()->user()->orders()->latest();

        if (request('status') === 'paid') {
            $query->where('status', 'paid');
        } elseif (request('status') === 'unpaid') {
            $query->where('status', 'unpaid');
        } elseif (request('status') === 'cancelled') {
            $query->whereIn('status', ['cancelled', 'failed']);
        }elseif (request('status') === 'returned') {
            $returnedOrderIds = \App\Models\ReturnRequest::where('user_id', auth()->id())
                ->whereNotIn('status', ['cancelled'])
                ->pluck('order_id')
                ->unique()
                ->toArray();
            $query->whereIn('id', $returnedOrderIds);
        }

        $orders = $query->paginate(10);
        $active = "orders";
        return view('front::user.orders.index', compact('orders', 'active'));
    }

    public function show(Order $order)
    {
        if ($order->user_id != auth()->user()->id) {
            abort(404);
        }

        $active = "orders";
        $gateways = Gateway::active()->get();
        $wallet = auth()->user()->getWallet();

        // بارگذاری روابط با Eager Loading
        $order->load([
            'items.product',
            'items.carrier',
            'items.get_price',
            'items.get_price.attributes',
            'items.get_price.attributes.group',
            'transactions',
            'user'
        ]);

        return view('front::user.orders.show', compact(
            'order',
            'gateways',
            'wallet',
            'active'
        ));
    }

    public function store(StoreOrderRequest $request)
    {
        $user = auth()->user();
        $cart = $user->cart;
        $address = Address::find($request->address);

        if (!$cart || !$cart->products->count() || !check_cart_quantity()) {
            return redirect()->route('front.cart');
        }

        if (!check_cart_discount()['status']) {
            return redirect()->route('front.checkout');
        }

        // ========== جدا کردن محصولات فیزیکی و دانلودی ==========
        $physicalProducts = $cart->products->filter(fn($p) => $p->type === 'physical');
        $downloadProducts = $cart->products->filter(fn($p) => $p->type === 'download');

        // اگر فقط محصول دانلودی در سبد است، نیازی به آدرس نیست
        if ($physicalProducts->isEmpty() && $downloadProducts->isNotEmpty()) {
            // آدرس برای محصولات دانلودی الزامی نیست
        }

        // ========== دسته‌بندی محصولات بر اساس فروشنده (فقط فیزیکی) ==========
        $sellerGroups = [];
        $physicalTotal = 0; // متغیر جدید برای مجموع قیمت نهایی فیزیکی

        foreach ($physicalProducts as $product) {
            $price = Price::with(['seller'])->find($product->pivot->price_id);
            if (!$price) continue;

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';

            if (!isset($sellerGroups[$groupId])) {
                if ($sellerId) {
                    $seller = Seller::find($sellerId);
                    $sellerInfo = $seller ? [
                        'id' => $seller->id,
                        'name' => $seller->seller_info->business_name ?? $seller->name,
                    ] : null;
                } else {
                    $sellerInfo = null;
                }

                // روش ارسال انتخاب شده برای این گروه
                $selectedCarrierId = $request->input("carrier_id_{$groupId}");
                $selectedDeliveryDate = $request->input("carrier_date_{$groupId}");

                $sellerGroups[$groupId] = [
                    'seller_id' => $sellerId,
                    'seller_info' => $sellerInfo,
                    'name' => $sellerInfo ? $sellerInfo['name'] : option('info_site_title'),
                    'groupId' => $groupId,
                    'products' => [],
                    'total_weight' => 0,
                    'price' => 0,          // قیمت اصلی (بدون تخفیف) برای محاسبه تخفیف کلی
                    'total_price' => 0,    // قیمت نهایی با تخفیف محصول
                    'carrier_id' => $selectedCarrierId,
                    'delivery_date' => $selectedDeliveryDate,
                    'shipping_cost' => 0
                ];
            }

            $finalPrice = $price->discount_price ?? $price->price;

            $sellerGroups[$groupId]['products'][] = [
                'product' => $product,
                'price' => $price,
                'quantity' => $product->pivot->quantity,
                'weight' => ($product->weight ?? 0) * $product->pivot->quantity,
                'final_price' => $finalPrice
            ];
            $sellerGroups[$groupId]['total_weight'] += ($product->weight ?? 0) * $product->pivot->quantity;
            $sellerGroups[$groupId]['price'] += ($price->price) * $product->pivot->quantity;
            $sellerGroups[$groupId]['total_price'] += $finalPrice * $product->pivot->quantity;

            // جمع کل قیمت نهایی فیزیکی
            $physicalTotal += $finalPrice * $product->pivot->quantity;
        }

        // ========== محاسبه قیمت کل محصولات دانلودی ==========
        $downloadTotalPrice = 0;
        $downloadItems = [];

        foreach ($downloadProducts as $product) {
            $price = Price::with(['seller'])->find($product->pivot->price_id);
            if (!$price) continue;

            $finalPrice = $price->discount_price ?? $price->price;
            $downloadTotalPrice += $finalPrice * $product->pivot->quantity;

            $downloadItems[] = [
                'product' => $product,
                'price' => $price,
                'quantity' => $product->pivot->quantity,
                'final_price' => $finalPrice
            ];
        }

        // ========== اعتبارسنجی و محاسبه هزینه ارسال برای هر گروه (فقط فیزیکی) ==========
        $totalShippingCost = 0;
        $errors = [];

        foreach ($sellerGroups as $groupId => &$group) {
            // 1. بررسی وجود روش ارسال
            if (!$group['carrier_id']) {
                $errors[] = "روش ارسال برای مرسوله {$group['name']} انتخاب نشده است";
                continue;
            }

            $carrier = Carrier::find($group['carrier_id']);
            if (!$carrier) {
                $errors[] = "روش ارسال نامعتبر برای مرسوله {$group['name']}";
                continue;
            }

            // 2. بررسی امکان استفاده از روش ارسال
            $carrierResult = $cart->canUseCarrier($carrier->id, $address->city_id);
            if (!$carrierResult['status']) {
                $errors[] = "روش ارسال {$carrier->title} برای مرسوله {$group['name']} قابل استفاده نیست: {$carrierResult['message']}";
                continue;
            }

            // 3. محاسبه هزینه ارسال
            $shippingCost = $this->calculateCarrierCost($carrier, $group['total_weight'], $address->city_id);
            if ($shippingCost === false) {
                $errors[] = "محاسبه هزینه ارسال برای مرسوله {$group['name']} امکان پذیر نیست";
                continue;
            }

            $group['shipping_cost'] = $shippingCost;
            $totalShippingCost += $shippingCost;

            // 4. اعتبارسنجی تاریخ تحویل برای روش‌های user_select
            if ($carrier->delivery_time_type == 'user_select') {
                if (!$group['delivery_date']) {
                    $errors[] = "لطفاً تاریخ تحویل برای مرسوله {$group['name']} را انتخاب کنید";
                    continue;
                }

                $deliveryDates = $this->getDeliveryDates($carrier);
                $isValidDate = false;
                foreach ($deliveryDates as $date) {
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

        // اگر فقط محصول دانلودی داریم و هیچ محصول فیزیکی نیست، نیازی به آدرس و ارسال نیست
        if ($physicalProducts->isEmpty() && $downloadProducts->isNotEmpty()) {
            // بدون خطای ارسال
        } elseif ($physicalProducts->isNotEmpty() && empty($sellerGroups)) {
            $errors[] = "محصولات فیزیکی یافت نشد";
        }

        if (!empty($errors)) {
            return redirect()->back()->withInput()->withErrors($errors);
        }

        // ========== ایجاد سفارش اصلی ==========
        $gateway = Gateway::where('key', $request->gateway)->first();
        $data = $request->validated();

        $discountAmount = $cart->totalDiscount();

        // محاسبه قیمت نهایی: مجموع قیمت نهایی فیزیکی + دانلودی + هزینه ارسال - تخفیف کلی
        $finalPrice = $physicalTotal + $downloadTotalPrice + $totalShippingCost - $discountAmount;

        $data['shipping_cost'] = $totalShippingCost;
        $data['price'] = $finalPrice;
        $data['status'] = 'unpaid';
        $data['discount_amount'] = $discountAmount;
        $data['discount_id'] = $cart->discount_id;

        if ($cart->discount) {
            if ($cart->discount->type == "percent") {
                $data['discount_percent'] = $cart->discount->amount;
            } else {
                $data['discount_price'] = $cart->discount->amount;
            }
        }

        $data['user_id'] = $user->id;

        // اطلاعات آدرس (برای محصولات فیزیکی الزامی است)
        if ($physicalProducts->isNotEmpty()) {
            $data['name'] = $address->fullname;
            $data['mobile'] = $address->mobile;
            $data['province_id'] = $address->province_id;
            $data['city_id'] = $address->city_id;
            $data['postal_code'] = $address->postal_code;
            $data['address'] = $address->address;
            $data['location'] = $address->lat . ',' . $address->lng;
        } else {
            // برای محصولات دانلودی، آدرس را خالی می‌گذاریم یا از اطلاعات کاربر استفاده می‌کنیم
            $data['name'] = $user->name ?? '';
            $data['mobile'] = $user->mobile ?? '';
            $data['province_id'] = null;
            $data['city_id'] = null;
            $data['postal_code'] = null;
            $data['address'] = null;
            $data['location'] = null;
        }

        if ($gateway) {
            $data['gateway_id'] = $gateway->id;
        }

        $order = Order::create($data);

        // ========== ایجاد آیتم‌های سفارش ==========
        $sellerIds = [];

        // -- محصولات فیزیکی --
        foreach ($physicalProducts as $product) {
            $price = Price::with(['seller'])->find($product->pivot->price_id);
            if (!$price) continue;

            $sellerId = $price->seller_id;
            $groupId = $sellerId ? 'seller_' . $sellerId : 'store';
            $group = $sellerGroups[$groupId] ?? null;

            // دریافت اطلاعات ارسال از گروه
            $carrier_id = null;
            $delivery_date = null;
            $shipping_cost = 0;

            if ($group) {
                $carrier_id = $group['carrier_id'] ?? null;
                $delivery_date = $group['delivery_date'] ?? null;
                $shipping_cost = $group['shipping_cost'] ?? 0;
            }

            $allAttributes = $this->getProductAttributes($price);

            $order->items()->create([
                'product_id' => $product->id,
                'seller_id' => $price->seller_id,
                'title' => $product->title,
                'price' => $price->discount_price ?? $price->price,
                'real_price' => $price->price,
                'quantity' => $product->pivot->quantity,
                'discount' => $price->discount ?? 0,
                'price_id' => $product->pivot->price_id,
                'shipping_cost' => $shipping_cost,
                'carrier_id' => $carrier_id,
                'delivery_date' => $delivery_date,
                'attributes' => $allAttributes,
                'product_type' => 'physical',
            ]);

            if ($price->seller_id) {
                $sellerIds[] = $price->seller_id;
            }
        }

        // -- محصولات دانلودی --
        foreach ($downloadItems as $item) {
            $product = $item['product'];
            $price = $item['price'];

            $allAttributes = $this->getProductAttributes($price);

            $order->items()->create([
                'product_id' => $product->id,
                'seller_id' => $price->seller_id,
                'title' => $product->title,
                'price' => $item['final_price'],
                'real_price' => $price->price,
                'quantity' => $item['quantity'],
                'discount' => $price->discount ?? 0,
                'price_id' => $product->pivot->price_id,
                'shipping_cost' => 0,
                'carrier_id' => null,
                'delivery_date' => null,
                'attributes' => $allAttributes,
                'product_type' => 'download',
                'is_downloadable' => true,
            ]);

            if ($price->seller_id) {
                $sellerIds[] = $price->seller_id;
            }
        }

        if (count($sellerIds)) {
            $order->update(['seller_id' => json_encode(array_unique($sellerIds))]);
        }

        // ==========  برسی پرداخت اقساطی قبل از حذف سبد ==========
        $isInstallment = $request->filled('installment_enabled') && $request->input('installment_enabled') == '1';
        $isCredit = $request->filled('credit_enabled') && $request->input('credit_enabled') == '1';

        if ($isInstallment) {
            $installmentService = app(\Modules\CreditPay\Services\InstallmentService::class);
            // ۱. بررسی اعتبار کاربر برای گرفتن اقساط
            $canApply = $installmentService->canUserApplyForInstallment($user->id,$price->discount_price ?? $price->price);
            if (!$canApply['can']) {
                return redirect()->back()->with('error', $canApply['reason'])->withInput();
            }
        }

        elseif ($isCredit) {

            // ۱. بررسی فعال بودن ماژول
            if (!function_exists('module_is_active') || !module_is_active('CreditPay')) {
                return redirect()->back()->with('error', 'ماژول خرید اعتباری فعال نیست.')->withInput();
            }

            // فاز ۸۸ — این بلاک فقط «اعتبارسنجی قبل از حذف سبد» است؛ سفارش اعتباری فقط یک‌بار و در
            // بلاک «مدیریت خرید اعتباری (CreditPay)» بعد از ایجاد سفارش ساخته می‌شود
            // (نسخهٔ قبلی همین‌جا هم سفارش می‌ساخت و هر خرید اعتباری «دو بار» ثبت می‌شد).
            $creditService = app(\Modules\CreditPay\Services\CreditService::class);

            // ۲. جمع‌آوری اطلاعات سبد
            $productIds = $cart->products->pluck('id')->toArray();
            $cartTotal  = (int) $cart->finalPrice();

            // ۳. بررسی شرط‌های خرید اعتباری
            $canUseCredit = $creditService->canUserUseCredit(
                $user->id,
                $cartTotal,
                $productIds
            );

            if (!$canUseCredit['can']) {
                return redirect()->back()->with('error', $canUseCredit['reason'])->withInput();
            }
        }
        // ==========  برسی پرداخت اعتباری قبل از حذف سبد ==========



       // $cart->delete();

        $hour = option('order_cancel', 1);
        CancelOrder::dispatch($order)->delay(now()->addHours($hour));

        event(new OrderCreated($order));

        // اگه پرداخت اقساطی انتخاب شده
        // ========== مدیریت پرداخت اقساطی ==========
        // اگه کاربر چک‌باکس اقساطی رو فعال کرده باشه، طرح اقساطی می‌سازیم
        // و مبلغ سفارش رو به مبلغ پیش‌پرداخت تغییر می‌دیم
        // روش پرداخت (کیف پول/درگاه) همچنان از طریق gateway$ انتخاب می‌شه

        // ========== جلوگیری از تداخل: فقط یکی از اقساطی یا اعتباری ==========
        $isInstallment = $request->filled('installment_enabled') && $request->input('installment_enabled') == '1';
        $isCredit = $request->filled('credit_enabled') && $request->input('credit_enabled') == '1';

        // اگه هر دو فعال بودن، ارور بده
        if ($isInstallment && $isCredit) {
            return redirect()->back()->with('error', 'امکان انتخاب همزمان پرداخت اقساطی و اعتباری وجود ندارد.')->withInput();
        }

        // ========== مدیریت پرداخت اقساطی ==========
        if ($isInstallment) {

            // بررسی فعال بودن ماژول
            if (!function_exists('module_is_active') || !module_is_active('CreditPay')) {
                return redirect()->back()->with('error', 'ماژول پرداخت اقساطی فعال نیست.')->withInput();
            }

            $installmentService = app(\Modules\CreditPay\Services\InstallmentService::class);

            // ===== تحلیل سبد: تفکیک آیتم‌های قابل خرید اقساطی از نقدی =====
            $cartItems = $installmentService->prepareCartItems($cart);
            $cartAnalysis = $installmentService->analyzeCart($cartItems);

            // اگه هیچ آیتم قابل خرید اقساطی وجود نداره → ارور
            if (!$cartAnalysis['has_credit_items']) {
                return redirect()->back()
                    ->with('error', 'هیچ محصولی در سبد شما قابل خرید اقساطی نیست.')
                    ->withInput();
            }

            // مبلغ قابل خرید اقساطی (آیتم‌های غیر فروشنده و غیر مستثنی)
            $installmentEligibleTotal = $cartAnalysis['credit_total'];
            // مبلغی که باید نقدی پرداخت شه (آیتم‌های فروشنده + مستثنی)
            $cashItemsTotal = $cartAnalysis['cash_total'];

            // بررسی اعتبار کاربر برای گرفتن اقساط (برای مبلغ قابل خرید اقساطی)
            $canApply = $installmentService->canUserApplyForInstallment($user->id, $installmentEligibleTotal);
            if (!$canApply['can']) {
                return redirect()->back()->with('error', $canApply['reason'])->withInput();
            }

            // اعتبارسنجی مقادیر ورودی
            $installmentCount = (int) $request->input('installment_count', 0);
            $downPaymentPercent = (float) $request->input('installment_down_payment_percent', 0);

            if ($installmentCount <= 0 || $downPaymentPercent <= 0) {
                return redirect()->back()->with('error', 'تنظیمات اقساط نامعتبر است. لطفاً طرح اقساطی را مجدداً انتخاب کنید.')->withInput();
            }

            // ===== محاسبه Markup (کارمزد) فقط روی آیتم‌های قابل خرید اقساطی =====
            $markupData = $installmentService->calculateMarkup($cartAnalysis['credit_items']);
            $markupAmount = (int) $markupData['markup_amount'];

            // مبلغ نهایی قابل خرید اقساطی (آیتم‌های اعتباری + markup)
            $totalForInstallment = $installmentEligibleTotal + $markupAmount;

            // ساخت طرح اقساطی فقط روی مبلغ قابل خرید اقساطی
            try {
                $plan = $installmentService->createPlan(
                    $user->id,
                    $order->id,
                    $totalForInstallment,
                    $installmentCount,
                    $downPaymentPercent
                );
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'خطا در ایجاد طرح اقساطی: ' . $e->getMessage())->withInput();
            }

            // مبلغ پیش‌پرداخت (فقط روی مبلغ قابل خرید اقساطی)
            $downPayment = $plan->down_payment;

            // ===== تغییر مبلغ سفارش به: مبلغ نقدی (آیتم‌های فروشنده/مستثنی) + هزینه ارسال + پیش‌پرداخت =====
            //     هزینه ارسال ($totalShippingCost) از OrderController محاسبه شده و جزو اقساط نیست
            $payNowAmount = $cashItemsTotal + $downPayment + $totalShippingCost;
            $order->update([
                'price' => $payNowAmount,
            ]);

            session()->flash('installment_plan_id', $plan->id);

            // اگه آیتم نقدی یا هزینه ارسال وجود داره، پیام به کاربر نمایش بده
            if ($cartAnalysis['has_cash_items'] || $totalShippingCost > 0) {
                $messages = $cartAnalysis['messages'];
                if ($totalShippingCost > 0) {
                    $messages[] = sprintf(
                        'هزینه ارسال (%s تومان) جزو اقساط نیست و باید نقدی پرداخت شود.',
                        number_format($totalShippingCost)
                    );
                }
                session()->flash('installment_cash_items_info', implode(' ', $messages));
            }

            \Log::info('Installment plan created', [
                'plan_id'              => $plan->id,
                'order_id'             => $order->id,
                'user_id'              => $user->id,
                'installment_total'    => $totalForInstallment,
                'cash_items_total'     => $cashItemsTotal,
                'shipping_cost'        => $totalShippingCost,
                'down_payment'         => $downPayment,
                'pay_now'              => $payNowAmount,
                'markup_amount'        => $markupAmount,
                'cash_items_count'     => count($cartAnalysis['cash_items']),
                'credit_items_count'   => count($cartAnalysis['credit_items']),
            ]);
        }

        // ========== مدیریت خرید اعتباری (CreditPay) ==========
        elseif ($isCredit) {

            // ۱. بررسی فعال بودن ماژول
            if (!function_exists('module_is_active') || !module_is_active('CreditPay')) {
                return redirect()->back()->with('error', 'ماژول خرید اعتباری فعال نیست.')->withInput();
            }

            $creditService = app(\Modules\CreditPay\Services\CreditService::class);

            // ۲. جمع‌آوری اطلاعات سبد
            $productIds = $cart->products->pluck('id')->toArray();
            $cartTotal  = (int) $cart->finalPrice();

            // ۳. بررسی شرط‌ها
            $canUseCredit = $creditService->canUserUseCredit(
                $user->id,
                $cartTotal,
                $productIds
            );

            if (!$canUseCredit['can']) {
                return redirect()->back()->with('error', $canUseCredit['reason'])->withInput();
            }

            $account = $canUseCredit['account'];

            // ۴. تحلیل سبد با prepareCartItems (نه toArray!)
            //    toArray ساختار pivot نداره و باعث می‌شه همه مقادیر 0 بشن
            $cartItems = $creditService->prepareCartItems($cart);
            $cartAnalysis = $creditService->analyzeCart($cartItems);

            // اگه هیچ آیتم اعتباری وجود نداره → ارور
            if (!$cartAnalysis['has_credit_items']) {
                return redirect()->back()
                    ->with('error', 'هیچ محصولی در سبد شما قابل خرید اعتباری نیست.')
                    ->withInput();
            }

            // ۵. محاسبه اعتبار واقعاً استفاده‌شده
            $availableCredit = (int) $account->available_credit;
            $creditEligibleTotal = $cartAnalysis['credit_total'];

            // اگه creditEligibleTotal صفره → یه مشکل وجود داره
            if ($creditEligibleTotal <= 0) {
                \Log::error('CreditPay: creditEligibleTotal is 0', [
                    'cartItems' => $cartItems,
                    'cartAnalysis' => $cartAnalysis,
                ]);
                return redirect()->back()
                    ->with('error', 'خطا در محاسبه مبلغ سبد. لطفاً با پشتیبانی تماس بگیرید.')
                    ->withInput();
            }

            $creditUsed = min($availableCredit, $creditEligibleTotal);

            // ۶. محاسبه markup فقط روی مبلغ اعتبار استفاده‌شده
            $markupData  = $creditService->calculateMarkup($cartAnalysis['credit_items']);
            $markupAmount = $markupData['markup_amount'];
            // اگه اعتبار کمتر از کل اقساطی است، markup را متناسب کاهش بده
            if ($creditUsed < $creditEligibleTotal && $creditEligibleTotal > 0) {
                $ratio = $creditUsed / $creditEligibleTotal;
                $markupAmount = (int) round($markupAmount * $ratio);
            }

            // ۷. محاسبه مبالغ نهایی
            $creditTotalWithMarkup = $creditUsed + $markupAmount;
            $creditShortfall = max(0, $creditEligibleTotal - $creditUsed);
            $cashItemsTotal  = $cartAnalysis['cash_total'];

            // مبلغ نقدی که کاربر باید بده:
            // - آیتم‌های نقدی (فروشنده/مستثنی)
            // - هزینه ارسال ($totalShippingCost از OrderController)
            // - مابقی مبلغ اقساطی که اعتبار پوشش نمی‌ده
            $cashPaid = $cashItemsTotal + $creditShortfall;

            // ۸. تنظیمات اقساط (فقط روی مبلغ اعتباری استفاده‌شده محاسبه می‌شه)
            $installmentsCount       = (int) \Modules\CreditPay\Models\CreditSetting::get('default_installments', 4);
            $firstInstallmentPercent = (float) \Modules\CreditPay\Models\CreditSetting::get('first_installment_percent', 25);
            $firstInstallmentAmount  = (int) round($creditTotalWithMarkup * $firstInstallmentPercent / 100);

            // ۹. مبلغ قابل پرداخت الان
            //    نقدی (مابقی + ارسال) + قسط اول
            $payNowAmount = $cashPaid + $firstInstallmentAmount + $totalShippingCost;

            // ===== ۱۰. بررسی موجودی کیف پول (اگه روش پرداخت کیف پول هست) =====
            if ($request->input('gateway') === 'wallet') {
                $wallet = $user->getWallet();
                $walletBalance = $wallet ? (int) $wallet->balance() : 0;

                if ($walletBalance < $payNowAmount) {
                    return redirect()->back()
                        ->with('error', sprintf(
                            'موجودی کیف پول شما (%s تومان) برای پرداخت مبلغ اعتباری (%s تومان) کافی نیست. لطفاً کیف پول خود را شارژ کنید یا از درگاه بانکی استفاده کنید.',
                            number_format($walletBalance),
                            number_format($payNowAmount)
                        ))
                        ->withInput();
                }
            }

            // ۱۱. ساخت سفارش اعتباری (بدون کسر اعتبار - کسر در orderPaid انجام می‌شه)
            try {
                $creditOrder = \Modules\CreditPay\Models\CreditOrder::create([
                    'order_id'                  => $order->id,
                    'account_id'                => $account->id,
                    'original_amount'           => $creditUsed,
                    'markup_amount'             => $markupAmount,
                    'total_amount'              => $creditTotalWithMarkup,
                    'credit_used'               => $creditUsed,
                    'cash_paid'                 => $cashPaid,
                    'installments_count'        => $installmentsCount,
                    'first_installment_percent' => $firstInstallmentPercent,
                    'first_installment_amount'  => $firstInstallmentAmount,
                    'first_installment_paid'    => false,
                    'status'                    => \Modules\CreditPay\Models\CreditOrder::STATUS_ACTIVE,
                ]);

                // ۱۲. ساخت اقساط (همگی در حالت pending) - فقط روی مبلغ اعتباری
                $remainingAmount = $creditTotalWithMarkup - $firstInstallmentAmount;
                $monthlyAmount   = $installmentsCount > 1 ? (int) round($remainingAmount / ($installmentsCount - 1)) : 0;

                // قسط اول (سررسید = امروز، برای پرداخت آنی)
                \Modules\CreditPay\Models\CreditInstallment::create([
                    'credit_order_id'    => $creditOrder->id,
                    'installment_number' => 1,
                    'amount'             => $firstInstallmentAmount,
                    'total_amount'       => $firstInstallmentAmount,
                    'due_date'           => now()->toDateString(),
                    'status'             => \Modules\CreditPay\Models\CreditInstallment::STATUS_PENDING,
                ]);

                // اقساط ماهانه بعدی
                for ($i = 2; $i <= $installmentsCount; $i++) {
                    \Modules\CreditPay\Models\CreditInstallment::create([
                        'credit_order_id'    => $creditOrder->id,
                        'installment_number' => $i,
                        'amount'             => $monthlyAmount,
                        'total_amount'       => $monthlyAmount,
                        'due_date'           => now()->addMonths($i - 1)->toDateString(),
                        'status'             => \Modules\CreditPay\Models\CreditInstallment::STATUS_PENDING,
                    ]);
                }

            } catch (\Exception $e) {
                \Log::error('Credit order creation failed', [
                    'user_id'  => $user->id,
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
                return redirect()->back()->with('error', 'خطا در ثبت خرید اعتباری: ' . $e->getMessage())->withInput();
            }

            // ۱۳. تغییر مبلغ سفارش به مبلغ نقدی + قسط اول + هزینه ارسال
            $order->update(['price' => $payNowAmount]);

            // ۱۴. ثبت در session برای استفاده در orderPaid
            session()->flash('credit_order_id', $creditOrder->id);

            // ۱۵. پیام به کاربر
            $messages = [];
            if ($cartAnalysis['has_cash_items']) {
                $messages = $cartAnalysis['messages'];
            }
            if ($creditShortfall > 0) {
                $messages[] = sprintf(
                    'فقط %s تومان از اعتبار شما برای اقساط استفاده شد. مابقی (%s تومان) نقدی پرداخت شد.',
                    number_format($creditUsed),
                    number_format($creditShortfall)
                );
            }
            if ($totalShippingCost > 0) {
                $messages[] = sprintf(
                    'هزینه ارسال (%s تومان) جزو اقساط نیست و نقدی پرداخت شد.',
                    number_format($totalShippingCost)
                );
            }
            if (!empty($messages)) {
                session()->flash('credit_cash_items_info', implode(' ', $messages));
            }

            \Log::info('Credit order created (pending payment)', [
                'credit_order_id'      => $creditOrder->id,
                'order_id'             => $order->id,
                'user_id'              => $user->id,
                'credit_eligible_total' => $creditEligibleTotal,
                'credit_used'           => $creditUsed,
                'markup_amount'         => $markupAmount,
                'credit_total_with_markup' => $creditTotalWithMarkup,
                'credit_shortfall'      => $creditShortfall,
                'cash_items_total'      => $cashItemsTotal,
                'shipping_cost'         => $totalShippingCost,
                'first_installment'     => $firstInstallmentAmount,
                'pay_now'               => $payNowAmount,
            ]);
        }



        return $this->resolvePaymentGateway($request, $order);
       // return $this->pay($order, $request);

    }

    protected function paymentModuleGateways(): array
    {
        return [
            // فاز ۸۷ — پکیج جدید «پرداخت‌یار» (PayYar): درگاه مستقیم + اسنپ‌پی
            'payyar'  => \Modules\PayYar\Http\Controllers\PayYarController::class,
            'snappay' => \Modules\PayYar\Http\Controllers\SnappPayController::class,
            // سازگاری نصب‌های قدیمی پکیج «درگاه‌های پرداخت و اقساطی» (DigiPay)
            'digipay' => \Modules\DigiPay\Http\Controllers\DigiPayController::class,
        ];
    }

    /**
     * =========================================================================
     *  متد مرکزی برای مدیریت gatewayهای پرداخت
     * =========================================================================
     *
     *  این متد:
     *  ۱. gateway انتخاب‌شده رو بررسی می‌کنه
     *  ۲. اگه متعلق به یکی از ماژول‌های پرداخت اختصاصی (DigiPay, SnappPay, etc)
     *     باشه، از اون ماژول استفاده می‌کنه
     *  ۳. در غیر این صورت به منطق قبلی (wallet یا درگاه‌های شتابی) برمی‌گرده
     *
     *  این متد به‌جای `return $this->pay($order, $request);` در انتهای متد
     *  store() استفاده می‌شه.
     * =========================================================================
     */
    protected function resolvePaymentGateway(StoreOrderRequest $request, Order $order)
    {
        $selectedGateway = $request->input('gateway');

        // ===== ۱. بررسی ماژول‌های پرداخت اختصاصی =====
        // اگه gateway انتخاب‌شده با یکی از keyهای ماژول‌های پرداخت اختصاصی
        // تطابق داشت، از اون ماژول استفاده می‌کنیم
        foreach ($this->paymentModuleGateways() as $gatewayKey => $controllerClass) {

            if ($selectedGateway !== $gatewayKey) {
                continue;
            }

            // نام ماژول از namespace استخراج می‌شه
            // مثلاً Modules\DigiPay\Http\Controllers\DigiPayController → DigiPay
            $moduleName = $this->extractModuleName($controllerClass);

            // بررسی فعال بودن ماژول
            if (!function_exists('module_is_active') || !module_is_active($moduleName)) {
                return redirect()->back()
                    ->with('error', "ماژول {$moduleName} فعال نیست.")
                    ->withInput();
            }

            // فراخوانی initiatePayment از کنترلر ماژول
            try {
                $controller = app($controllerClass);
                return $controller->initiatePayment($request, $order);
            } catch (\Exception $e) {
                Log::error("OrderController: Error in module gateway {$gatewayKey}", [
                    'order_id'   => $order->id,
                    'gateway'    => $gatewayKey,
                    'module'     => $moduleName,
                    'error'      => $e->getMessage(),
                ]);

                return redirect()->back()
                    ->with('error', "خطا در اتصال به درگاه {$moduleName}: " . $e->getMessage())
                    ->withInput();
            }
        }

        // ===== ۲. fallback به wallet یا درگاه‌های شتابی =====
        // wallet و درگاه‌های شتابی موجود در جدول gateways در StoreOrderRequest
        // به‌صورت خودکار validate می‌شن. ما فقط متد pay() قبلی رو صدا می‌زنیم.
        return $this->pay($order, $request);
    }

    /**
     * استخراج نام ماژول از namespace کنترلر.
     */
    private function extractModuleName(string $controllerClass): string
    {
        // Modules\DigiPay\Http\Controllers\DigiPayController → DigiPay
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $controllerClass, $matches)) {
            return $matches[1];
        }
        return '';
    }


    /**
     * استخراج ویژگی‌های محصول
     */
    private function getProductAttributes($price)
    {
        $allAttributes = [];
        $get_attributes = $price->get_attributes()->with('group')->get();

        foreach ($get_attributes as $attribute) {
            $attribute_groups_name = $attribute->group->name;

            if (!isset($allAttributes[$attribute_groups_name])) {
                $allAttributes[$attribute_groups_name] = [];
            }

            $allAttributes[$attribute_groups_name][] = [
                'name' => $attribute->name,
                'value' => $attribute->value,
            ];
        }

        return json_encode($allAttributes);
    }
    private function calculateCarrierCost($carrier, $weight, $cityId)
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

    private function getDeliveryDates($carrier)
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

            $jalali = \Morilog\Jalali\Jalalian::fromDateTime($currentDate);
            $isFriday = $currentDate->dayOfWeek == 5;
            $isHoliday = $this->isHoliday($currentDate);

            $isSelectable = true;
            if ($carrier->disable_fridays && $isFriday) $isSelectable = false;
            if ($carrier->disable_holidays && $isHoliday) $isSelectable = false;

            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'jalali' => $jalali->format('Y/m/d'),
                'display' => $jalali->format('l j F Y'),
                'displayDate' => $jalali->format('j F Y'),
                'day_name' => $jalali->format('l'),
                'is_friday' => $isFriday,
                'is_holiday' => $isHoliday,
                'is_selectable' => $isSelectable
            ];
        }

        return $dates;
    }

    private function isHoliday($date)
    {
        $jalali = \Morilog\Jalali\Jalalian::fromDateTime($date);
        $jalaliDate = $jalali->format('Y/m/d');

        // استفاده از HolidayService
        return $this->holidayService->isHoliday($jalaliDate);
    }



    public function pay(Order $order, Request $request)
    {
        if ($order->user_id != auth()->user()->id) {
            abort(404);
        }

        if ($order->status != 'unpaid') {
            return redirect()->route('front.orders.show', ['order' => $order])->with('error', 'سفارش شما لغو شده است یا قبلا پرداخت کرده اید');
        }
        // ========== رزرو موجودی قبل از پرداخت (فقط برای محصولات فیزیکی) ==========
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    // فقط برای محصولات فیزیکی موجودی رزرو شود
                    // فاز ۷۸ — آیتم‌های پیش‌فروش رزرو نمی‌شوند (کالا هنوز تولید/تأمین نشده)
                    if ($item->product->isPhysical() && !$item->product->pre_order) {
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
            });
        } catch (\Exception $e) {
            return redirect()->route('front.orders.show', ['order' => $order])
                ->with('error', $e->getMessage());
        }

        // اگر مبلغ سفارش صفر باشد (پرداخت رایگان)
        if ($order->price == 0) {
            return $this->orderPaid($order);
        }

        $gateways = Gateway::active()->pluck('key')->toArray();

        $request->validate([
            'gateway' => 'required|in:wallet,' . implode(',', $gateways)
        ]);

        $gateway = $request->gateway;

        if ($gateway == 'wallet') {
            return $this->payUsingWallet($order);
        }

        try {
            $gateway_configs = get_gateway_configs($gateway);

            return Payment::via($gateway)->config($gateway_configs)->callbackUrl(route('front.orders.verify', ['gateway' => $gateway]))->purchase(
                (new Invoice)->amount(intval($order->price)),
                function ($driver, $transactionId) use ($order, $gateway) {
                    DB::table('transactions')->insert([
                        'status'               => false,
                        'amount'               => $order->price,
                        'factorNumber'         => $order->id,
                        'mobile'               => auth()->user()->username,
                        'message'              => 'تراکنش ایجاد شد برای درگاه ' . $gateway,
                        'transID'              => (string) $transactionId,
                        'token'                => (string) $transactionId,
                        'user_id'              => auth()->user()->id,
                        'transactionable_type' => Order::class,
                        'transactionable_id'   => $order->id,
                        'gateway_id'           => Gateway::where('key', $gateway)->first()->id,
                        "created_at"           => Carbon::now(),
                        "updated_at"           => Carbon::now(),
                    ]);

                    session()->put('transactionId', (string) $transactionId);
                    session()->put('amount', $order->price);
                }
            )->pay()->render();
        } catch (Exception $e) {
            return redirect()
                ->route('front.orderResultInfo', ['order' => $order])
                ->with('transaction-error', $e->getMessage())
                ->with('order_id', $order->id);
        }
    }

    public function verify($gateway)
    {
        $transactionId = session()->get('transactionId');
        $amount = session()->get('amount');

        $transaction = Transaction::where('status', false)->where('transID', $transactionId)->firstOrFail();

        $order = $transaction->transactionable;

        $gateway_configs = get_gateway_configs($gateway);

        try {
            $receipt = Payment::via($gateway)->config($gateway_configs);

            if ($amount) {
                $receipt = $receipt->amount(intval($amount));
            }

            $receipt = $receipt->transactionId($transactionId)->verify();

            DB::table('transactions')->where('transID', (string) $transactionId)->update([
                'status'               => 1,
                'amount'               => $order->price,
                'factorNumber'         => $order->id,
                'mobile'               => $order->mobile,
                'traceNumber'          => $receipt->getReferenceId(),
                'message'              => $transaction->message . '<br>' . 'پرداخت موفق با درگاه ' . $gateway,
                'updated_at'           => Carbon::now(),
            ]);

            return $this->orderPaid($order);
        } catch (\Exception $exception) {

            DB::table('transactions')->where('transID', (string) $transactionId)->update([
                'message'              => $transaction->message . '<br>' . $exception->getMessage(),
                "updated_at"           => Carbon::now(),
            ]);

            return redirect()->route('front.orderResultInfo', ['order' => $order])->with('transaction-error', $exception->getMessage());
        }
    }

    private function payUsingWallet(Order $order)
    {
        $wallet  = $order->user->getWallet();
        $amount  = intval($wallet->balance() - $order->price);

        if ($amount >= 0) {
            $result = $order->payUsingWallet();

            if ($result) {
                // تأیید رزرو توسط متد orderPaid انجام می‌شود، بنابراین اینجا نیازی نیست
                return $this->orderPaid($order);
            }
        }

        $gateway = Gateway::active()->orderBy('ordering')->first();
        $amount  = abs($amount);

        if (!$gateway) {
            return redirect()->route('front.orders.show', ['order' => $order])
                ->with('transaction-error', 'درگاه فعالی برای پرداخت یافت نشد')
                ->with('order_id', $order->id);
        }

        $history = $wallet->histories()->create([
            'type'        => 'deposit',
            'amount'      => $amount,
            'description' => 'شارژ آنلاین کیف پول برای ثبت سفارش',
            'source'      => 'user',
            'status'      => 'fail',
            'order_id'    => $order->id
        ]);

        try {
            $gateway         = $gateway->key;
            $gateway_configs = get_gateway_configs($gateway);

            return Payment::via($gateway)->config($gateway_configs)->callbackUrl(route('front.wallet.verify', ['gateway' => $gateway]))->purchase(
                (new Invoice)->amount($amount),
                function ($driver, $transactionId) use ($history, $gateway, $amount) {
                    DB::table('transactions')->insert([
                        'status'               => false,
                        'amount'               => $amount,
                        'factorNumber'         => $history->id,
                        'mobile'               => auth()->user()->username,
                        'message'              => 'تراکنش ایجاد شد برای درگاه ' . $gateway,
                        'transID'              => $transactionId,
                        'token'                => $transactionId,
                        'user_id'              => auth()->user()->id,
                        'transactionable_type' => WalletHistory::class,
                        'transactionable_id'   => $history->id,
                        'gateway_id'           => Gateway::where('key', $gateway)->first()->id,
                        "created_at"           => Carbon::now(),
                        "updated_at"           => Carbon::now(),
                    ]);

                    session()->put('transactionId', $transactionId);
                    session()->put('amount', $amount);
                }
            )->pay()->render();
        } catch (Exception $e) {
            return redirect()->route('front.orderResultInfo', ['order' => $order])
                ->with('transaction-error', $e->getMessage())
                ->with('order_id', $order->id);
        }
    }

    /**
     * پرداخت سفارش و تایید نهایی
     */
    /**
     * پرداخت سفارش و تایید نهایی
     */
    private function orderPaid(Order $order)
    {
        DB::beginTransaction();

        try {
            // ========== بررسی مجدد موجودی با قفل (فقط برای محصولات فیزیکی) ==========
            foreach ($order->items as $item) {
                // اگر محصول دانلودی است، از بررسی موجودی صرف نظر کن
                if (!$item->product->isPhysical()) {
                    continue;
                }

                // فاز ۷۸ — پیش‌فروش: بدون کنترل موجودی
                if ($item->product->pre_order) {
                    continue;
                }

                $price = $item->get_price;

                if (!$price) {
                    throw new Exception("محصول {$item->title} یافت نشد");
                }

                // قفل کردن رکورد قیمت برای بررسی اتمیک
                $lockedPrice = Price::where('id', $price->id)->lockForUpdate()->first();

                // محاسبه موجودی واقعی (با در نظر گرفتن رزروهای دیگر)
                $availableStock = $lockedPrice->stock - ($lockedPrice->reserved_stock ?? 0);

                if ($availableStock < $item->quantity) {
                    throw new Exception(
                        "موجودی محصول {$item->title} کافی نیست. " .
                        "موجودی فعلی: {$availableStock} - درخواستی: {$item->quantity}"
                    );
                }
            }

            // ========== ثبت حرکات انبار (فقط برای محصولات فیزیکی) ==========
            foreach ($order->items as $item) {
                // اگر محصول دانلودی است، از ثبت حرکات انبار صرف نظر کن
                if (!$item->product->isPhysical()) {
                    continue;
                }

                // فاز ۷۸ — پیش‌فروش: حرکت انبار ثبت نمی‌شود
                if ($item->product->pre_order) {
                    continue;
                }

                $price = $item->get_price;

                if ($price) {
                    // تایید رزرو و خروج از انبار
                    $this->stockService->confirmReservation(
                        $price,
                        $item->quantity,
                        $order->id,
                        $item->id
                    );

                    // به‌روزرسانی موجودی در خود آیتم
                    $item->update([
                        'stock_before' => $price->stock + $item->quantity,
                        'stock_after' => $price->stock,
                    ]);
                }
            }

            /*// ========== فعال‌سازی لینک دانلود برای محصولات دانلودی ==========
            foreach ($order->items as $item) {
                if (!$item->product->isPhysical()) { // فقط دانلودی
                    $this->activateDownloadLink($item, $order);
                }
            }*/

            // به‌روزرسانی وضعیت سفارش
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            DB::commit();

            // ارسال ایونت پرداخت موفق
            event(new OrderPaidEvent($order));

            return redirect()->route('front.orderResultInfo', ['order' => $order])
                ->with('message', 'پرداخت با موفقیت انجام شد');

        } catch (Exception $e) {
            DB::rollBack();

            // لاگ خطا
            \Log::warning('خطا در هنگام پرداخت سفارش', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            // ========== لغو سفارش و برگشت هزینه ==========
            return $this->cancelOrderAndRefund($order, $e->getMessage());
        }
    }

    /**
     * لغو سفارش و برگشت هزینه به کیف پول کاربر
     */
    private function cancelOrderAndRefund(Order $order, $errorMessage)
    {
        DB::beginTransaction();

        try {
            $refundAmount = 0;

            // 1. برگشت موجودی رزرو شده (اگر قبلاً رزرو شده)
            foreach ($order->items as $item) {
                $price = $item->get_price;
                if ($price && ($price->reserved_stock ?? 0) >= $item->quantity) {
                    $decremented = DB::table('prices')
                        ->where('id', $price->id)
                        ->where('reserved_stock', '>=', $item->quantity)
                        ->decrement('reserved_stock', $item->quantity);

                    if (!$decremented) {
                        Log::warning("خطا در برگشت موجودی رزرو شده", [
                            'order_id' => $order->id,
                            'item_id' => $item->id,
                            'price_id' => $price->id
                        ]);
                    }
                }
            }

            // 2. برگشت هزینه به کیف پول کاربر (اگر پرداخت شده باشد)
            // بررسی وجود تراکنش موفق برای این سفارش
            $transaction = $order->transactions()
                ->where('status', true)
                ->where('amount', $order->price)
                ->first();

            if ($transaction) {
                // افزایش موجودی کیف پول
                $wallet = $order->user->getWallet();
                $depositResult = $wallet->deposit(
                    $order->price,
                    "برگشت هزینه سفارش لغو شده #{$order->id} - دلیل: {$errorMessage}"
                );

                if ($depositResult) {
                    $refundAmount = $order->price;

                    // ثبت تاریخچه برگشت وجه
                    WalletHistory::create([
                        'user_id' => $order->user_id,
                        'type' => 'refund',
                        'amount' => $order->price,
                        'description' => "برگشت هزینه سفارش لغو شده #{$order->id} - {$errorMessage}",
                        'status' => 'success',
                        'order_id' => $order->id
                    ]);

                    Log::info("برگشت وجه به کیف پول کاربر", [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'amount' => $order->price
                    ]);
                }
            }

            // 3. به‌روزرسانی وضعیت سفارش
            $order->update([
                'status' => 'cancelled',
                'failed_reason' => $errorMessage,
                'failed_at' => now(),
            ]);

            DB::commit();

            // 4. ارسال ایونت لغو سفارش
            event(new OrderCancelledNotification($order, $errorMessage, $refundAmount));

            // 5. ارسال نوتیفیکیشن به کاربر
            //$this->sendOrderCancellationNotifications($order, $errorMessage, $refundAmount);

            // 6. ارسال نوتیفیکیشن به ادمین (در ایونت هندل می‌شود)

            return redirect()->route('front.orders.show', ['order' => $order])
                ->with('error', "سفارش شما لغو شد: {$errorMessage}")
                ->with('refund_message', $refundAmount > 0 ? "مبلغ " . number_format($refundAmount) . " تومان به کیف پول شما برگشت داده شد" : null);

        } catch (Exception $e) {
            DB::rollBack();

            // لاگ خطای بحرانی
            Log::critical('خطا در لغو سفارش و برگشت وجه', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->price,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // در صورت خطا، مدیر سیستم باید مداخله کند
            $this->alertAdminForManualIntervention($order, $errorMessage, $e->getMessage());

            // حتی در صورت خطا، سعی کنید وضعیت سفارش را آپدیت کنید
            try {
                $order->update([
                    'status' => 'failed',
                    'failed_reason' => "خطای سیستمی: {$errorMessage}",
                    'failed_at' => now(),
                ]);
            } catch (\Exception $updateError) {
                // لاگ خطای آپدیت
                Log::error("خطا در آپدیت وضعیت سفارش: " . $updateError->getMessage());
            }

            return redirect()->route('front.orders.show', ['order' => $order])
                ->with('error', "خطای سیستمی: لطفاً با پشتیبانی تماس بگیرید. کد خطا: #{$order->id}");
        }
    }

    /**
     * ارسال نوتیفیکیشن‌های لغو سفارش به کاربر
     */
    private function sendOrderCancellationNotifications(Order $order, $errorMessage, $refundAmount = 0)
    {
        // 1. ارسال نوتیفیکیشن دیتابیس و WebPush
        try {
            $order->user->notify(new OrderCancelled($order, $errorMessage, $refundAmount));
        } catch (\Exception $e) {
            Log::warning("ارسال نوتیفیکیشن دیتابیس لغو سفارش ناموفق: " . $e->getMessage());

            // روش جایگزین: ذخیره مستقیم در دیتابیس
            try {
                $order->user->notifications()->create([
                    'type' => 'order_cancelled',
                    'title' => 'لغو سفارش',
                    'message' => "سفارش #{$order->id} به دلیل {$errorMessage} لغو شد." .
                        ($refundAmount > 0 ? " مبلغ " . number_format($refundAmount) . " تومان به کیف پول شما برگشت داده شد." : ""),
                    'data' => [
                        'order_id' => $order->id,
                        'reason' => $errorMessage,
                        'refund_amount' => $refundAmount
                    ]
                ]);
            } catch (\Exception $dbError) {
                Log::error("خطا در ذخیره نوتیفیکیشن در دیتابیس: " . $dbError->getMessage());
            }
        }

        // 2. ارسال SMS در صورت فعال بودن
        if (option('user_sms_on_order_cancelled', 'off') == 'on') {
            try {
                Notification::send($order->user, new OrderCancelledSms($order, $errorMessage, $refundAmount));
            } catch (\Exception $e) {
                Log::warning("ارسال SMS لغو سفارش به کاربر ناموفق: " . $e->getMessage());

                // روش جایگزین: استفاده از سرویس SMS مستقیم
                if (option('sms_order_cancel_enable', false)) {
                    try {

                        $smsServiceData=[
                            'refund_amount'=>number_format($refundAmount),
                            'reason'=>  $errorMessage,
                        ];
                        $smsService = new SmsService($order->user->mobile,$smsServiceData,Sms::TYPES['USER_ORDER_CANCELLED'],$order->user_id);
                        $smsService->sendSms();


                    } catch (\Exception $smsError) {
                        Log::error("خطا در ارسال SMS مستقیم: " . $smsError->getMessage());
                    }
                }
            }
        }
    }

    /**
     * اطلاع به مدیر برای مداخله دستی
     */
    private function alertAdminForManualIntervention(Order $order, $errorMessage, $systemError = null)
    {
        // 1. ذخیره در لاگ مخصوص
        Log::channel('refund_errors')->critical('نیاز به مداخله مدیر', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'user_mobile' => $order->mobile,
            'amount' => $order->price,
            'error' => $errorMessage,
            'system_error' => $systemError,
            'timestamp' => now()->toDateTimeString(),
            'order_status' => $order->status
        ]);

        // 2. ایجاد نوتیفیکیشن برای ادمین‌ها در دیتابیس
        try {
            $admins = \App\Models\Admin::whereIn('level', ['admin', 'creator'])->get();

            foreach ($admins as $admin) {
                $admin->notifications()->create([
                    'type' => 'order_cancellation_error',
                    'title' => 'خطا در لغو سفارش و برگشت وجه',
                    'message' => "سفارش #{$order->id} نیاز به بررسی دستی دارد. مبلغ: " . number_format($order->price) . " تومان",
                    'data' => [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'amount' => $order->price,
                        'error' => $errorMessage,
                        'action_needed' => true
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error("خطا در ارسال نوتیفیکیشن به ادمین: " . $e->getMessage());
        }

        // 3. ارسال SMS به ادمین (اختیاری)
        if (option('admin_sms_on_critical_error', 'off') == 'on') {
            try {
                $adminMobile = option('admin_mobile_number');
                if ($adminMobile) {

                    $smsServiceData=[
                        'refund_amount'=>number_format($order->price),
                        'reason'=>  $errorMessage,
                    ];
                    $smsService = new SmsService($adminMobile,$smsServiceData,Sms::TYPES['USER_ORDER_CANCELLED'],$order->user_id);
                    $smsService->sendSms();

                }
            } catch (\Exception $e) {
                Log::warning("ارسال SMS به ادمین ناموفق: " . $e->getMessage());
            }
        }
   }

    public function print(Order $order)
    {
        //$this->authorize('orders.view');
        if (auth()->user()->id!=$order->user_id){
            abort(404);
        }
        return view('front::user.orders.print', compact('order'));
    }

    /**
     * فعال‌سازی لینک دانلود برای محصولات دانلودی
     */
    private function activateDownloadLink($orderItem, $order)
    {
        $price = $orderItem->get_price;
        if (!$price) {
            return;
        }

        // فرض بر این است که مدل Price دارای رابطه file است
        $file = $price->file;
        if (!$file) {
            \Log::warning("فایل دانلودی برای محصول {$orderItem->title} یافت نشد", [
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id
            ]);
            return;
        }

        // ایجاد لینک دانلود
        $downloadLink = \App\Models\DownloadLink::create([
            'order_item_id' => $orderItem->id,
            'user_id' => $order->user_id,
            'file_id' => $file->id,
            'token' => \Illuminate\Support\Str::random(64),
            'expires_at' => now()->addDays(option('download_expiry_days', 30)),
            'max_downloads' => option('max_downloads', 5),
            'download_count' => 0,
            'is_active' => true,
        ]);

        $orderItem->update([
            'download_link_id' => $downloadLink->id,
            'download_activated_at' => now(),
        ]);

        // ارسال ایمیل حاوی لینک دانلود (اختیاری)
        try {
            \Mail::to($order->user->email)->send(new \App\Mail\DownloadLinkMail($orderItem, $downloadLink));
        } catch (\Exception $e) {
            \Log::warning("ارسال ایمیل لینک دانلود ناموفق: " . $e->getMessage());
        }
    }

}
