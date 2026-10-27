<?php

namespace App\Livewire\Seller\Orders;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — جزئیات سفارش از نگاه فروشنده (پورت OrderSellerController@show).
 *
 * گارد مالکیت: سفارش باید حداقل یک آیتم با seller_id فروشندهٔ فعلی داشته باشد
 * (پنل قدیم این گارد را نداشت — طبق قرارداد فاز ۱۰۸ گارد اضافه شد).
 *
 * فرم تغییر وضعیت مرسوله: فرم کلاسیک POST به روت قدیمی seller.orders.shipping-status
 * با همان فیلدهای اسکریپت قدیم: shipping_status / cancel_reason / canceled_refund_amount.
 * دکمهٔ پرینت: لینک کلاسیک به seller.orders.print (کنترلر قدیمی).
 */
#[Title('جزئیات سفارش')]
class Show extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        // گارد مالکیت — سفارشِ بدون آیتمِ این فروشنده ۴۰۴ است
        if (! $order->items()->where('seller_id', sellerID())->exists()) {
            abort(404);
        }

        $this->order = $order;
    }

    public function render()
    {
        $order = $this->order->load(['items.product', 'items.get_price.get_attributes.group', 'province', 'city']);

        // آیتم‌های سهم این فروشنده — همان مبنای ویوی قدیمی ($orderItem)
        $orderItem = $order->items->firstWhere('seller_id', (int) sellerID());

        // محاسبات فاکتور — همان فرمول ویوی قدیمی show.blade.php
        $totalAmount = ($orderItem->shipping_cost ?? 0);
        $totalDiscount = 0;

        foreach ($orderItem->products() as $item) {
            $itemPrice = $item->real_price * $item->quantity;
            $itemDiscountAmount = ($itemPrice * ($item->discount ?? 0) / 100);
            $totalDiscount += $itemDiscountAmount;
            $totalAmount += $itemPrice - $itemDiscountAmount;
        }

        $finalPayable = $totalAmount;

        // گنجاندن هزینهٔ ارسال در «مبلغ قابل پرداخت نهایی» — همان tfoot ویوی قدیمی
        $finalPayable = $totalAmount + ($orderItem->shipping_cost ?? 0);

        // گیت‌های فرم تغییر وضعیت — همان شرط‌های ویوی قدیمی
        $disableStatus = false;
        if ($order->status != 'paid') {
            $disableStatus = true;
        }
        if ($orderItem->refunded) {
            $disableStatus = true;
        }
        if ($orderItem->shipping_status == 'canceled') {
            $disableStatus = true;
        }

        return view('sellers.panel.orders.show', [
            'order' => $order,
            'orderItem' => $orderItem,
            'totalAmount' => $totalAmount,
            'totalDiscount' => $totalDiscount,
            'finalPayable' => $finalPayable,
            'disableStatus' => $disableStatus,
            'indexUrl' => route('seller.orders.index'),
            'printUrl' => route('seller.orders.print', ['order' => $order]),
            'shippingStatusUrl' => route('seller.orders.shipping-status', ['order' => $order]),
            'deliveryDate' => $this->deliveryDate($orderItem->delivery_date),
        ]);
    }

    /**
     * تاریخ تحویل — همان رفتار ویوی قدیمی: میلادی → جلالی؛ نامعتبر → همان مقدار خام.
     */
    private function deliveryDate(?string $deliveryDate): ?string
    {
        if (! $deliveryDate) {
            return null;
        }

        try {
            return jdate(Carbon::parse($deliveryDate))->format('Y/m/d');
        } catch (\Throwable) {
            return $deliveryDate;
        }
    }
}
