<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\AdminPanel\OrderPersistence;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('admin-panel::layout')]
#[Title('جزئیات سفارش')]
class OrderShow extends Component
{
    public Order $order;

    /** آیتم‌های سفارش (هر آیتم = یک مرسوله) */
    public $orderItems;

    public $installmentPlan = null;

    public bool $confirmingDelete = false;

    public function mount(Order $order)
    {
        if (! auth('adminPanel')->user()?->can('orders.view')) {
            abort(403);
        }

        $this->order = $order;
        $this->orderItems = $order->items()
            ->with(['product', 'seller', 'carrier'])
            ->latest()
            ->get();

        if (module_is_ready('InstallmentPayment', ['installment_settings'])) {
            $this->installmentPlan = \Modules\InstallmentPayment\Models\InstallmentPlan::where('order_id', $order->id)->first();
        }
    }

    /** حذف کامل سفارش (پورت destroy قدیمی) */
    public function delete(OrderPersistence $persistence)
    {
        if (! auth('adminPanel')->user()?->can('orders.delete')) {
            abort(403);
        }

        $persistence->destroy($this->order);

        session()->flash('np-toast', 'سفارش با موفقیت حذف شد.');

        return $this->redirect(route('np.orders.index'), navigate: true);
    }

    /** مراحل وضعیت ارسال (تایم‌لاین) */
    public static function shippingSteps(): array
    {
        return [
            'w-pending' => 'در انتظار بررسی',
            'pending' => 'در حال بررسی',
            'processing' => 'در حال پردازش',
            'waiting' => 'منتظر ارسال',
            'sent' => 'ارسال شد',
            'post-sent' => 'تحویل به پست',
            'delivered' => 'تحویل داده شد',
        ];
    }

    /** برچسب وضعیت بازگشت کالا */
    public static function returnStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'pending' => 'مرجوعی در حال بررسی',
            'approved' => 'مرجوعی تایید شد',
            'shipped_by_customer' => 'محصول ارسال شد',
            'received' => 'محصول دریافت شد',
            'reshipped' => 'ارسال مجدد',
            'completed' => 'مرجوعی تکمیل شد',
            'rejected' => 'مرجوعی رد شد',
            'cancelled' => 'مرجوعی لغو شد',
            'failed' => 'مرجوعی ناموفق',
            default => null,
        };
    }

    /** برچسب نحوه پرداخت */
    public function paymentMethodLabel(): string
    {
        if ($this->installmentPlan) {
            return 'اقساطی';
        }

        if ($this->order->status !== 'paid') {
            return '—';
        }

        return $this->order->gateway == 'wallet' ? 'کیف پول' : ($this->order->gateway ?? '—');
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.orders.show');
    }
}
