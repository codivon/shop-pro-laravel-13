<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Events\OrderPaid;
use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Exports\OrdersExport;
use App\Exports\SellerOrdersExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Datatable\Order\OrderCollection;
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
    public function index()
    {
        $sizeTypes = SizeType::latest()->get();

        return view('front::sellers.panel.orders.index' , compact('sizeTypes'));
    }

    public function apiIndex(Request $request)
    {
        $order_ids=[];
        $orderItem_ids=OrderItem::where('seller_id',sellerID())->get();
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $orders = Order::whereIn('id',$order_ids)->filter($request);

        $orders = datatable($request, $orders);

        return new OrderCollection($orders);
    }

    public function show(Order $order)
    {
        return view('front::sellers.panel.orders.show', compact('order'));
    }
    public function printAllShippingFormsMin(Request $request)
    {

        foreach ($request->ids as $id) {
            $orders = Order::paid()->whereIn('id', $request->ids)->get();
        }

        return view('back.orders.print-all-shipping-form-min', compact('orders'));
    }

    public function printAllShippingForms(Request $request)
    {

        foreach ($request->ids as $id) {
            $orders = Order::paid()->whereIn('id', $request->ids)->get();
        }

        return view('back.orders.print-all-shipping-forms', compact('orders'));
    }

    public function printAll(Request $request)
    {

        foreach ($request->ids as $id) {
            $orders = Order::paid()->whereIn('id', $request->ids)->get();
        }

        return view('front::sellers.panel.orders.print-all', compact('orders'));
    }

    public function shipping_status(Order $order, Request $request)
    {
        $request->validate([
            'shipping_status' => 'required|in:w-pending,pending,processing,waiting,sent,post-sent,delivered,canceled,refunded',
            'cancel_reason' => 'nullable|string|max:500',
            'canceled_refund_amount' => 'nullable|boolean'
        ]);


        $orderItem=$order->items()->where('seller_id',sellerID())->first();
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

        // به‌روزرسانی وضعیت ارسال برای همه آیتم‌های同一 فروشنده
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
            $this->refundOrderItems($order, $orderItem, $sellerId);
        }

        return response()->json([
            'success' => true,
            'message' => 'وضعیت با موفقیت تغییر کرد'
        ]);
    }
    private function refundOrderItems(Order $order, OrderItem $orderItem, $sellerId)
    {
        $user = $order->user;
        $orderItems = $order->items()->where('seller_id', $sellerId)->get();

        // محاسبه مبلغ کل برای بازگشت
        $totalAmount = $orderItems->sum('price') + $orderItems->first()?->shipping_cost;

        // ========== بررسی موجودی کیف پول فروشنده ==========
        if ($orderItem->seller_id) {
            $seller = $orderItem->seller;

            if (!$seller || !$seller->wallet) {
                throw ValidationException::withMessages([
                    'message' => 'کیف پول فروشنده یافت نشد'
                ]);
            }

            // بررسی موجودی کافی فروشنده
            if ($seller->getWallet()->balance() < $totalAmount) {
                throw ValidationException::withMessages([
                    'message' => "موجودی کیف پول فروشنده ({$seller->business_name}) برای برگشت وجه کافی نیست. موجودی فعلی: " . number_format($seller->wallet->balance) . " تومان"
                ]);
            }
        }

        // ========== 1. کم کردن از حساب فروشنده (برداشت از کیف پول فروشنده) ==========
        if ($orderItem->seller_id) {
            $seller = $orderItem->seller;

            // کم کردن از موجودی فروشنده
            $seller->wallet->decrement('balance', $totalAmount);

            // ثبت در تاریخچه کیف پول فروشنده (برداشت)
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

        // ========== 2. اضافه کردن به حساب کاربر (واریز به کیف پول کاربر) ==========
        $user = $order->user;

        if (!$user || !$user->wallet) {
            throw ValidationException::withMessages([
                'message' => 'کیف پول کاربر یافت نشد'
            ]);
        }

        // بررسی اینکه قبلاً برای این سفارش برگشت وجه انجام نشده باشد
        $existingHistory = WalletHistory::where([
            'wallet_id' => $user->wallet->id,
            'order_id' => $order->id,
            'orderCanceled' => 1
        ])->first();

        if (!$existingHistory) {
            // اضافه کردن به موجودی کاربر
            $user->wallet->increment('balance', $totalAmount);

            // ثبت در تاریخچه کیف پول کاربر (واریز)
            $this->createWalletHistory(
                $user->wallet,
                'deposit',
                $totalAmount,
                $order->id,
                $orderItem->id,
                "بازگشت وجه سفارش لغو شده شماره {$order->id}",
                'user'
            );
        }

        // ========== 3. به‌روزرسانی وضعیت refund در order_items ==========
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

        $orders = Order::whereIn('id', $request->ids)->get();

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

        return view('front::sellers.panel.orders.print', compact('order'));
    }

    public function shippingForm(Order $order)
    {

        return view('front::sellers.panel.orders.shipping-form', compact('order'));
    }

    public function export(Request $request)
    {

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
        $request->request->add(['seller' => seller()]);
        switch ($request->export_type) {

            case 'excel': {
                return $this->exportExcel($orders ,$request);
                break;
            }
            default: {
                return $this->exportPrint($orders, $request);
            }
        }
    }

    private function exportExcel($orders)
    {
        return Excel::download(new SellerOrdersExport($orders), 'orders.xlsx');
    }

}
