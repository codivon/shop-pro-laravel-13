<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Services\AdminPanel\OrderPersistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('سفارشات فروشندگان')]
class SellerOrders extends Component
{
    use WithPagination;

    /** فروشنده (در صفحه‌ی اختصاصی یک فروشنده مقدار دارد) */
    public ?Seller $seller = null;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'order_id', except: '')]
    public string $orderId = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'shipping_status', except: '')]
    public string $shippingStatus = '';

    #[Url(as: 'product_name', except: '')]
    public string $productName = '';

    #[Url(as: 'from_date', except: '')]
    public string $fromDate = '';

    #[Url(as: 'to_date', except: '')]
    public string $toDate = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- تغییر وضعیت ارسال گروهی ---------------- */

    public bool $bulkStatusModal = false;

    public string $bulkShippingStatus = '';

    public bool $bulkBackMoney = false;

    /* ---------------- حذف گروهی ---------------- */

    public bool $confirmingBulkDelete = false;

    public function mount(?Seller $seller = null): void
    {
        auth('adminPanel')->user()?->can('sellers.orders') ?: abort(403);

        $this->seller = $seller;
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['fullname', 'orderId', 'status', 'shippingStatus', 'productName', 'fromDate', 'toDate', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function applyFilters(): void
    {
        $this->fullname = '';
        $this->orderId = '';
        $this->status = '';
        $this->shippingStatus = '';
        $this->productName = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- کوئری لیست ---------------- */

    /** شناسه‌های سفارش‌های دارای قلم برای این فروشنده */
    protected function sellerOrderIds(): array
    {
        return OrderItem::where('seller_id', $this->seller->id)
            ->pluck('order_id')
            ->unique()
            ->values()
            ->all();
    }

    protected function baseQuery()
    {
        if ($this->seller) {
            $query = Order::whereIn('id', $this->sellerOrderIds());
        } else {
            // سراسری: سفارشات فروشندگان (مثل apiOrdersIndex قدیمی)
            $query = Order::whereNotNull('seller_id');
        }

        $query->with(['user:id,username,first_name,last_name']);

        // فیلترها — مطابق Order::filter قدیمی
        if ($this->fullname !== '') {
            $query->whereHas('user', fn ($q) => $q->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ['%' . $this->fullname . '%']));
        }

        if ($this->orderId !== '') {
            $query->where('id', $this->orderId);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->shippingStatus !== '') {
            $query->whereHas('items', fn ($q) => $q->where('shipping_status', $this->shippingStatus)->when($this->seller, fn ($w) => $w->where('seller_id', $this->seller->id)));
        }

        if ($this->productName !== '') {
            $query->whereHas('items', fn ($q) => $q->where('title', 'like', '%' . $this->productName . '%')->when($this->seller, fn ($w) => $w->where('seller_id', $this->seller->id)));
        }

        if ($this->fromDate !== '') {
            try {
                $query->where('created_at', '>=', Jalalian::fromFormat('Y-m-d', $this->fromDate)->toCarbon()->startOfDay());
            } catch (\Throwable) {
            }
        }

        if ($this->toDate !== '') {
            try {
                $query->where('created_at', '<=', Jalalian::fromFormat('Y-m-d', $this->toDate)->toCarbon()->endOfDay());
            } catch (\Throwable) {
            }
        }

        return $query->latest('id');
    }

    #[Computed]
    public function orders()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->orders->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- عملیات گروهی ---------------- */

    public function openBulkStatus(): void
    {
        auth('adminPanel')->user()?->can('orders.update') ?: abort(403);
        $this->bulkStatusModal = true;
    }

    public function applyBulkStatus(OrderPersistence $persistence): void
    {
        auth('adminPanel')->user()?->can('orders.update') ?: abort(403);

        $this->validate([
            'bulkShippingStatus' => 'required|in:w-pending,pending,waiting,sent,canceled',
        ], [
            'bulkShippingStatus.required' => 'انتخاب وضعیت ارسال الزامی است',
        ]);

        try {
            $persistence->bulkShippingStatus($this->selected, $this->bulkShippingStatus, $this->bulkBackMoney);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $errors) {
                foreach ($errors as $message) {
                    $this->dispatch('toast', message: $message, type: 'error');
                }
            }

            return;
        }

        $this->bulkStatusModal = false;
        $this->bulkShippingStatus = '';
        $this->bulkBackMoney = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'وضعیت ارسال سفارشات انتخاب‌شده تغییر کرد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('orders.delete') ?: abort(403);

        app(OrderPersistence::class)->multipleDestroy($this->selected);

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'سفارشات انتخاب شده با موفقیت حذف شدند.', type: 'success');
    }

    /* ---------------- خروجی اکسل ---------------- */

    public function exportExcel(): void
    {
        auth('adminPanel')->user()?->can('sellers.orders.export') ?: abort(403);

        $query = http_build_query([
            'query' => array_filter([
                'id' => $this->orderId ?: null,
                'fullname' => $this->fullname ?: null,
                'status' => $this->status ?: null,
                'shipping_status' => $this->shippingStatus ?: null,
                'product_name' => $this->productName ?: null,
                'from_date' => $this->fromDate ?: null,
                'to_date' => $this->toDate ?: null,
                'export_type' => 'excel',
            ]),
            'export_type' => 'excel',
        ]);

        /*
         | خروجی اکسل
         | هر فروشنده → SellerOrdersExport قدیمی (به روت legacy با تزریق seller)
         | سراسری → اکسپورت سالم فاز ۲ (orders) — چون ویوهای اکسل قدیمی به
         | $request->seller وابسته‌اند و برای لیست سراسری (بدون فروشنده) همیشه
         | ۵۰۰ می‌دادند؛ به‌جای تکرار آن باگ از مسیر سالم سفارشات استفاده می‌کنیم.
         */
        $route = $this->seller
            ? route('np.sellers.orders.export', ['seller' => $this->seller])
            : route('np.orders.export');

        $this->dispatch('open-url', url: $route . '?' . $query);
    }

    /* ---------------- نمایش ---------------- */

    /** قیمت کل از نگاه فروشنده */
    public function orderPrice(Order $order): int|string
    {
        if ($this->seller && method_exists($order, 'priceSeller')) {
            return $order->priceSeller($this->seller->id);
        }

        return $order->price;
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'پرداخت شده',
            'unpaid' => 'پرداخت نشده',
            'canceled', 'cancelled' => 'لغو شده',
            'pending_down_payment' => 'در انتظار پیش‌پرداخت',
            'active' => 'اقساطی فعال',
            'completed' => 'اقساط تکمیل',
            'defaulted' => 'اقساط معوق',
            default => $status ?? '—',
        };
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.orders')
            ->layout('admin-panel::layout');
    }
}
