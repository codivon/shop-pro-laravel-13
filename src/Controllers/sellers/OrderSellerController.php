<?php

namespace Themes\SaniarTheme\src\Controllers\sellers;

use App\Events\OrderPaid;
use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Exports\OrdersExport;
use App\Exports\SellerOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Favorite;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SizeType;
use App\Models\Transaction;
use App\Models\WalletHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;
use function GuzzleHttp\Promise\all;

class OrderSellerController extends Controller
{
    /**
     * سانیار فاز I: سفارش فقط وقتی معتبر است که حداقل یک آیتم از این فروشنده داشته باشد
     * (اسکوپ امنیتی — نسخهٔ WeblakShop این بررسی را ندارد)
     */
    private function assertSellerOrder(Order $order): OrderItem
    {
        $orderItem = $order->items()->where('seller_id', sellerID())->first();
        if (!$orderItem) {
            abort(404);
        }
        return $orderItem;
    }

    public function index()
    {
        $sizeTypes = SizeType::latest()->get();

        return view('front::sellers.panel.orders.index' , compact('sizeTypes'));
    }

    /**
     * سانیار فاز I: دیتاتیبل سبک — ردیف‌های seller-friendly به‌جای سری‌سازی خام مدل
     * (میزبان روابط user/items را eager-load نمی‌کرد و WeblakShop به فیلدهای ناموجود
     * مانند links.seller_view و priceSeller در JS تکیه داشت)
     */
    public function apiIndex(Request $request)
    {
        $order_ids = OrderItem::where('seller_id', sellerID())->pluck('order_id')->unique()->all();

        $orders = Order::with([
            'user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'username', 'mobile');
            },
            'items' => function ($q) {
                $q->where('seller_id', sellerID())
                    ->select('id', 'order_id', 'seller_id', 'shipping_status', 'refunded', 'tracking_code', 'quantity');
            },
        ])->whereIn('id', $order_ids)->filter($request);

        $orders = datatable($request, $orders);

        $rows = $orders->getCollection()->map(function (Order $order) {
            $items = $order->items->where('seller_id', sellerID());
            $first = $items->first();

            return [
                'id' => $order->id,
                'user' => $order->user ? trim($order->user->first_name . ' ' . $order->user->last_name) : '—',
                'username' => $order->user ? $order->user->username : null,
                'name' => $order->name,
                'mobile' => $order->mobile,
                'status' => $order->status,
                'shipping_status' => $first ? $first->shipping_status : $order->shipping_status,
                'refunded' => $first ? (bool)$first->refunded : false,
                'items_count' => $items->count(),
                'quantity' => (int)$items->sum('quantity'),
                'price_seller' => (int)$order->priceSeller(),
                'created_at' => jdate($order->created_at)->format('Y/m/d H:i'),
                'show_url' => route('seller.orders.show', ['order' => $order]),
                'print_url' => route('seller.orders.print', ['order' => $order]),
            ];
        });

        return response()->json([
            'data' => $rows,
            'meta' => [
                'page' => $orders->currentPage(),
                'pages' => $orders->lastPage(),
                'perpage' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order)
    {
        $this->assertSellerOrder($order);

        return view('front::sellers.panel.orders.show', compact('order'));
    }

    /**
     * سانیار فاز I: چاپ گروهی فقط سفارش‌های همین فروشنده (اسکوپ whereHas)
     * و نما سمت تم — نه نمای دفتر پشتیبان.
     */
    private function sellerOrdersByIds(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            $ids = explode(',', (string)$ids);
        }

        return Order::paid()
            ->whereHas('items', function ($q) {
                $q->where('seller_id', sellerID());
            })
            ->whereIn('id', $ids)
            ->get();
    }

    public function printAllShippingFormsMin(Request $request)
    {
        $orders = $this->sellerOrdersByIds($request);

        return view('front::sellers.panel.orders.shipping-forms-min', compact('orders'));
    }

    public function printAllShippingForms(Request $request)
    {
        $orders = $this->sellerOrdersByIds($request);

        return view('front::sellers.panel.orders.shipping-forms', compact('orders'));
    }

    public function printAll(Request $request)
    {
        $orders = $this->sellerOrdersByIds($request);

        return view('front::sellers.panel.orders.print-all', compact('orders'));
    }

    public function shipping_status(Order $order, Request $request)
    {
        $request->validate([
            'shipping_status' => 'required|in:w-pending,pending,processing,waiting,sent,post-sent,delivered,canceled,refunded',
            'cancel_reason' => 'nullable|string|max:500',
            'canceled_refund_amount' => 'nullable|boolean'
        ]);


        // سانیار فاز I: اسکوپ فروشنده — سفارشِ بدون آیتم از این فروشنده ۴۰۴ است
        $orderItem = $this->assertSellerOrder($order);
        $orderItems=$order->items()->where('seller_id',sellerID())->get();

        $sellerId = $orderItem->seller_id;

        // بررسی پرداخت شده بودن سفارش
        if (!$order->isPaid()) {
            throw ValidationException::withMessages([
                'message' => 'سفارش پرداخت نشده است'
            ]);
        }

        if ($orderItem->refunded) {
            throw ValidationException::withMessages([
                'message' => 'این آیتم سفارش قبلاً لغو شده و وجه آن برگشت داده شده است. امکان تغییر وضعیت وجود ندارد.'
            ]);
        }

        // سانیار فاز I: کل عملیات اتمیک — در نسخهٔ اصلی وضعیت پیش از بازگشت وجه
        // ذخیره می‌شد و با خطای لغو، مرسوله نیمه‌کاره «لغو بدون بازگشت وجه» می‌ماند
        DB::transaction(function () use ($order, $orderItem, $request, $sellerId) {
            // به‌روزرسانی وضعیت ارسال برای همه آیتم‌های همین فروشنده
            $order->items()->where('seller_id', $sellerId)->update([
                'shipping_status' => $request->shipping_status
            ]);

            // ذخیره دلیل لغو (در صورت وجود)
            if ($request->filled('cancel_reason')) {
                $order->items()->where('seller_id', $sellerId)->update([
                    'cancel_reason' => $request->cancel_reason,
                    'canceled_at' => now()
                ]);
            }

            // در صورت لغو سفارش و بازگشت وجه
            if ($request->shipping_status == 'canceled' && $request->boolean('canceled_refund_amount')) {
                $this->applyRefund($order, $orderItem, $sellerId);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'وضعیت با موفقیت تغییر کرد'
        ]);
    }
    /**
     * سانیار فاز I — بازنویسی امن بازگشت وجه (۳ اصلاح نسبت به کپی WeblakShop):
     * ۱) کل جریان داخل DB::transaction (نسخهٔ اصلی بدون تراکنش بود: با خطای
     *    «کیف پول کاربر یافت نشد»، موجودی فروشنده کم می‌شد ولی چیزی ثبت نمی‌شد)
     * ۲) همهٔ بررسی‌ها (کیف پول کاربر/فروشنده + برگشت مضاعف) قبل از هر تغییری
     * ۳) مبلغ بازگشت = جمع (price × quantity) + هزینه ارسال — نسخهٔ اصلی
     *    quantity را در حساب نمی‌آورد و برای تعداد>۱ کمتر از مبلغ واقعی برمی‌گرداند
     */
    private function applyRefund(Order $order, OrderItem $orderItem, $sellerId)
    {
        $user = $order->user;
        $orderItems = $order->items()->where('seller_id', $sellerId)->get();

        // محاسبه مبلغ کل برای بازگشت (قیمت واحد پس از تخفیف × تعداد + هزینه ارسال)
        $totalAmount = $orderItems->sum(fn ($item) => $item->price * $item->quantity)
            + ($orderItems->first()?->shipping_cost ?: 0);

        $seller = $orderItem->seller_id ? $orderItem->seller : null;

        // ---------- بررسی‌های پیش از هر تغییری ----------
        if ($seller && (!$seller->wallet || !$seller->getWallet())) {
            throw ValidationException::withMessages([
                'message' => 'کیف پول فروشنده یافت نشد'
            ]);
        }

        if ($seller && $seller->getWallet()->balance() < $totalAmount) {
            throw ValidationException::withMessages([
                'message' => "موجودی کیف پول فروشنده ({$seller->business_name}) برای برگشت وجه کافی نیست. موجودی فعلی: " . number_format($seller->wallet->balance) . " تومان"
            ]);
        }

        if (!$user || !$user->wallet) {
            throw ValidationException::withMessages([
                'message' => 'کیف پول کاربر یافت نشد'
            ]);
        }

        // جلوگیری از برگشت وجه مضاعف برای همین سفارش
        $existingHistory = WalletHistory::where([
            'wallet_id' => $user->wallet->id,
            'order_id' => $order->id,
            'orderCanceled' => 1
        ])->first();

        if ($existingHistory) {
            throw ValidationException::withMessages([
                'message' => 'برای این سفارش قبلاً بازگشت وجه ثبت شده است'
            ]);
        }

        // ---------- ۱) کسر از کیف پول فروشنده ----------
        if ($seller) {
            $seller->wallet->decrement('balance', $totalAmount);

            $this->createWalletHistory(
                $seller->wallet,
                'withdraw',
                $totalAmount,
                $order->id,
                $orderItem->id,
                "برداشت بابت لغو سفارش شماره {$order->id} - فروشنده: {$seller->business_name}",
                'seller'
            );
        }

        // ---------- ۲) واریز به کیف پول کاربر ----------
        $user->wallet->increment('balance', $totalAmount);

        $this->createWalletHistory(
            $user->wallet,
            'deposit',
            $totalAmount,
            $order->id,
            $orderItem->id,
            "بازگشت وجه سفارش لغو شده شماره {$order->id}",
            'user'
        );

        // ---------- ۳) ثبت وضعیت refund روی آیتم‌های این فروشنده ----------
        $order->items()->where('seller_id', $sellerId)->update([
            'refunded' => true,
            'refunded_at' => now(),
            'refunded_amount' => $totalAmount
        ]);
    }

    /**
     * ایجاد تاریخچه کیف پول
     */
    private function createWalletHistory($wallet, $type, $amount, $orderId, $orderItemId, $description, $source = 'admin')
    {
        return $wallet->histories()->create([
            'type' => $type,  // deposit (واریز) یا withdraw (برداشت)
            'source' => $source, // admin, seller, user
            'status' => 'success',
            'amount' => $amount,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'description' => $description,
            'orderCanceled' => 1
        ]);
    }

    public function shippingsStatus(Request $request)
    {

        $request->validate([
            'status' => 'required',
        ]);

        // سانیار فاز I: فقط سفارش‌های همین فروشنده
        $orders = Order::whereHas('items', function ($q) {
            $q->where('seller_id', sellerID());
        })->whereIn('id', $request->ids)->get();

        foreach ($orders as $order) {
            if (!$order->isPaid()) {
                throw ValidationException::withMessages(['id' => 'سفارش شماره ' . $order->id . ' پرداخت نشده است ']);
            }
            if ($order->shipping_status=="canceled") {
                throw ValidationException::withMessages(['id' => 'سفارش شماره ' . $order->id . ' لغو شده است ']);
            }

            if ($order->reserved()) {
                throw ValidationException::withMessages(['id' => 'سفارش شماره ' . $order->id . ' رزرو شده است ']);
            }
        }

        foreach ($orders as $order) {
            $order->update([
                'shipping_status' => $request->status
            ]);

            $order->reservedOrders()->update([
                'shipping_status' => $request->status
            ]);
        }

        return response('success');
    }

    public function notCompleted()
    {

        $prices = Price::whereHas('orderItems', function ($q) {
            $q->where('seller_id',sellerID())->whereHas('order', function ($q2) {
                $q2->notCompleted();
            })->whereHas('product', function ($q3) {
                $q3->physical();
            });
        })->paginate(20);

        return view('front::sellers.panel.orders.not-completed', compact('prices'));
    }

    public function print(Order $order)
    {
        $this->assertSellerOrder($order);

        return view('front::sellers.panel.orders.print', compact('order'));
    }

    public function shippingForm(Order $order)
    {
        $this->assertSellerOrder($order);

        return view('front::sellers.panel.orders.shipping-form-single', compact('order'));
    }

    /**
     * سانیار فاز I: ثبت کد رهگیری seller-friendly — معادلی برای
     * admin.orders.set-tracking-code که در WeblakShop پشت گارد Admin است و
     * برای فروشنده 403 می‌دهد (باگ لاتنت ثبت‌شده در فاز H).
     */
    public function set_tracking_code(Order $order, Request $request)
    {
        $request->validate([
            'tracking_code' => 'required|string|max:255',
        ]);

        $this->assertSellerOrder($order);

        $order->items()->where('seller_id', sellerID())->update([
            'tracking_code' => $request->tracking_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کد رهگیری با موفقیت ثبت شد',
        ]);
    }

    public function export(Request $request)
    {
        $orderId = [];
        $orders = Order::filter($request)->get();
        foreach ($orders as $order){
            foreach ($order->items()->get() as $order_item){
                if ($order_item->seller_id==sellerID()){
                    $orderId[]=$order_item->order_id;
                }
            }
        }
        $orderId=array_unique($orderId);
        $orders = Order::whereIn('id',$orderId)->filter($request)->get();
        // سانیار فاز I: فرم خروجی GET است — در درخواست GET، input() از بگ query
        // می‌خواند نه POST؛ نسخهٔ اصلی seller() را فقط به بگ POST می‌داد و
        // نمای خروجی میزبان روی $request->seller->id خطای ۵۰۰ می‌گرفت (باگ لاتنت WeblakShop)
        $request->query->add(['seller' => seller()]);
        $request->request->add(['seller' => seller()]);
        switch ($request->export_type) {

            case 'excel': {
                return $this->exportExcel($orders ,$request);
                break;
            }
            default: {
                // سانیار فاز I: exportPrint در نسخهٔ اصلی تعریف نشده بود (۵۰۰)؛ پیش‌فرض = اکسل
                return $this->exportExcel($orders ,$request);
            }
        }
    }

    private function exportExcel($orders)
    {
        return Excel::download(new SellerOrdersExport($orders), 'orders.xlsx');
    }

}
