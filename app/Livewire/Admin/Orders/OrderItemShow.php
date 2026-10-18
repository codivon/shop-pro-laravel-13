<?php

namespace App\Livewire\Admin\Orders;

use App\Models\OrderItem;
use App\Models\WalletHistory;
use App\Services\AdminPanel\OrderPersistence;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layout')]
#[Title('جزئیات مرسوله')]
class OrderItemShow extends Component
{
    public OrderItem $orderItem;

    /** وضعیت ارسال انتخاب‌شده در پنل */
    public string $shippingStatus = '';

    public string $trackingCode = '';

    /** مودال لغو */
    public bool $cancelModal = false;

    public string $cancelReason = '';

    public bool $cancelRefundAmount = false;

    public function mount(OrderItem $orderItem)
    {
        if (! auth('adminPanel')->user()?->can('orders.view')) {
            abort(403);
        }

        $this->orderItem = $orderItem->load(['order', 'product', 'seller.seller_info']);
        $this->shippingStatus = $orderItem->shipping_status;
        $this->trackingCode = (string) $orderItem->tracking_code;

        activity()
            ->performedOn($orderItem->order)
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_order_item',
                'order_id' => $orderItem->order->id,
                'order_item_id' => $orderItem->id,
                'product_title' => $orderItem->product->title ?? 'نامشخص',
                'seller_id' => $orderItem->seller_id,
                'ip' => request()->ip(),
            ])
            ->log('مدیر ' . $this->adminName() . ' جزئیات آیتم سفارش شماره ' . $orderItem->order->id . ' را مشاهده کرد');
    }

    protected function adminName(): string
    {
        $admin = auth('adminPanel')->user();

        return $admin?->full_name ?? $admin?->name ?? 'مدیر';
    }

    /* ---------------- محاسبات نمایش ---------------- */

    /** آیا تغییر وضعیت مجاز است (مطابق منطق غیرفعال‌سازی قدیمی) */
    public function getDisableStatusProperty(): bool
    {
        if ($this->orderItem->refunded) {
            return true;
        }

        if ($this->orderItem->order && ! $this->orderItem->order->isPaid()) {
            return true;
        }

        if ($this->orderItem->shipping_status == 'canceled') {
            return true;
        }

        return false;
    }

    /** آیا سفارش قبلاً لغو و وجهش برگشت داده شده */
    public function getOrderCanceledProperty(): ?WalletHistory
    {
        if (! $this->orderItem->order?->user) {
            return null;
        }

        return WalletHistory::where([
            'order_id' => $this->orderItem->order->id,
            'order_item_id' => $this->orderItem->id,
            'orderCanceled' => 1,
        ])->first();
    }

    /** مبلغ قابل بازگشت (نرمال یا پیش‌پرداخت اقساط) */
    public function getRefundAmountProperty(): int
    {
        $order = $this->orderItem->order;

        return (int) ($order->price ?? 0);
    }

    /** آیتم‌های همان فروشنده در این سفارش (محصولات مرسوله) */
    public function getSellerItemsProperty()
    {
        return $this->orderItem->order->items->where('seller_id', $this->orderItem->seller_id);
    }

    /** نام فروشنده (مطابق منطق قدیمی) */
    public function getSellerNameProperty(): string
    {
        if ($this->orderItem->seller) {
            return $this->orderItem->seller->seller_info->business_name
                ?? $this->orderItem->seller->name
                ?? 'فروشنده';
        }

        return 'فروشگاه اصلی';
    }

    public function getPaymentMethodLabelProperty(): string
    {
        $order = $this->orderItem->order;

        if (module_is_ready('InstallmentPayment', ['installment_settings']) && $order->installmentPlan) {
            return 'اقساطی';
        }

        if ($order->status !== 'paid') {
            return '—';
        }

        return $order->gateway == 'wallet' ? 'کیف پول' : ($order->gateway ?? '—');
    }

    /* ---------------- اکشن‌ها ---------------- */

    public function updateStatus(OrderPersistence $persistence): void
    {
        if (! auth('adminPanel')->user()?->can('orders.update')) {
            abort(403);
        }

        $this->validate([
            'shippingStatus' => 'required|in:w-pending,pending,processing,waiting,sent,post-sent,delivered,canceled,refunded',
        ]);

        if ($this->shippingStatus === 'canceled') {
            $this->cancelModal = true;

            return;
        }

        $this->applyStatus($persistence);
    }

    public function confirmCancel(OrderPersistence $persistence): void
    {
        if (! auth('adminPanel')->user()?->can('orders.update')) {
            abort(403);
        }

        $this->validate([
            'cancelReason' => 'nullable|string|max:500',
        ]);

        $this->applyStatus($persistence, 'canceled');

        $this->cancelModal = false;
        $this->cancelReason = '';
    }

    protected function applyStatus(OrderPersistence $persistence, ?string $forceStatus = null): void
    {
        try {
            $persistence->changeItemShippingStatus(
                $this->orderItem,
                $forceStatus ?? $this->shippingStatus,
                $this->cancelReason ?: null,
                $this->cancelRefundAmount
            );
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'خطا در تغییر وضعیت';
            $this->dispatch('toast', message: $message, type: 'error');

            return;
        }

        $this->orderItem->refresh();
        $this->shippingStatus = $this->orderItem->shipping_status;

        $this->dispatch('toast', message: 'وضعیت با موفقیت تغییر کرد', type: 'success');
    }

    public function updateTracking(OrderPersistence $persistence): void
    {
        if (! auth('adminPanel')->user()?->can('orders.update')) {
            abort(403);
        }

        $this->validate([
            'trackingCode' => 'required',
        ], [
            'trackingCode.required' => 'کد رهگیری الزامی است',
        ]);

        try {
            $persistence->updateItemTrackingCode($this->orderItem, $this->trackingCode);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'خطا در ثبت کد رهگیری';
            $this->dispatch('toast', message: $message, type: 'error');

            return;
        }

        $this->orderItem->refresh();
        $this->trackingCode = (string) $this->orderItem->tracking_code;

        $this->dispatch('toast', message: 'کد رهگیری با موفقیت ثبت شد', type: 'success');
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
    }

    public static function shippingSteps(): array
    {
        return OrderShow::shippingSteps();
    }

    public static function shippingStatusLabel(?string $status): string
    {
        return OrderIndex::shippingStatusLabel($status);
    }

    public static function returnStatusLabel(?string $status): ?string
    {
        return OrderShow::returnStatusLabel($status);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.orders.item');
    }
}
