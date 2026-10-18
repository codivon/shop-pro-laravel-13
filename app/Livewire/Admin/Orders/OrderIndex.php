<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\AdminPanel\OrderPersistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست سفارشات')]
class OrderIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'username', except: '')]
    public string $username = '';

    #[Url(as: 'order_id', except: '')]
    public string $orderId = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'shipping_status', except: '')]
    public string $shippingStatus = '';

    #[Url(as: 'payment_type', except: '')]
    public string $paymentType = '';

    #[Url(as: 'product_name', except: '')]
    public string $productName = '';

    #[Url(as: 'product_id', except: '')]
    public string $productId = '';

    #[Url(as: 'from_date', except: '')]
    public string $fromDate = '';

    #[Url(as: 'to_date', except: '')]
    public string $toDate = '';

    #[Url(as: 'sort', except: '')]
    public string $sortField = '';

    #[Url(as: 'dir', except: '')]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- تغییر وضعیت ارسال گروهی ---------------- */

    public bool $bulkStatusModal = false;

    public string $bulkShippingStatus = '';

    public bool $bulkBackMoney = false;

    /* ---------------- حذف ---------------- */

    public bool $confirmingBulkDelete = false;

    /* ---------------- خروجی اکسل (پورت export قدیمی) ---------------- */

    public bool $exportModal = false;

    public array $exportColumns = [
        'row', 'id', 'user_id', 'name', 'mobile', 'province', 'city', 'postal_code',
        'address', 'carrier', 'created_at', 'shipping_cost', 'total_discount', 'price', 'description', 'products',
    ];

    public function toggleExportColumn(string $column): void
    {
        if (in_array($column, $this->exportColumns, true)) {
            $this->exportColumns = array_values(array_diff($this->exportColumns, [$column]));
        } else {
            $this->exportColumns[] = $column;
        }
    }

    public function exportExcel(): void
    {
        $this->authorizeOrder('orders.export');

        $query = http_build_query([
            'query' => array_filter([
                'id' => $this->orderId ?: null,
                'fullname' => $this->fullname ?: null,
                'username' => $this->username ?: null,
                'status' => $this->status ?: null,
                'shipping_status' => $this->shippingStatus ?: null,
                'payment_type' => $this->paymentType ?: null,
                'product_name' => $this->productName ?: null,
                'product_id' => $this->productId ?: null,
                'from_date' => $this->fromDate ?: null,
                'to_date' => $this->toDate ?: null,
            ]),
            'filters' => array_fill_keys($this->exportColumns, '1'),
            'export_type' => 'excel',
        ]);

        $this->exportModal = false;

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('export')
            ->withProperties([
                'action' => 'export_orders',
                'export_type' => 'excel',
                'columns' => $this->exportColumns,
                'ip' => request()->ip(),
            ])
            ->log('مدیر ' . ($this->adminName()) . ' خروجی اکسل سفارشات را دریافت کرد');

        $this->dispatch('open-url', url: route('np.orders.export') . '?' . $query);
    }

    protected function adminName(): string
    {
        $admin = auth('adminPanel')->user();

        return $admin?->full_name ?? $admin?->name ?? 'مدیر';
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['fullname', 'username', 'orderId', 'status', 'shippingStatus', 'paymentType', 'productName', 'productId', 'fromDate', 'toDate', 'sortField', 'sortDir', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->fullname = '';
        $this->username = '';
        $this->orderId = '';
        $this->status = '';
        $this->shippingStatus = '';
        $this->paymentType = '';
        $this->productName = '';
        $this->productId = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'desc';
        }
    }

    /* ---------------- کوئری لیست (مطابق Order::filter قدیمی) ---------------- */

    protected function baseQuery()
    {
        $query = Order::query()
            ->with(['user:id,username,first_name,last_name'])
            ->with(['items' => function ($q) {
                $q->select('id', 'order_id', 'seller_id', 'shipping_status')
                    ->with('seller:id');
            }]);

        // نام و نام خانوادگی
        if ($this->fullname !== '') {
            $query->whereHas('user', function ($q) {
                $q->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ['%' . $this->fullname . '%']);
            });
        }

        // نام کاربری
        if ($this->username !== '') {
            $query->whereHas('user', fn ($q) => $q->where('username', 'like', '%' . $this->username . '%'));
        }

        // شماره سفارش
        if ($this->orderId !== '') {
            $query->where('id', $this->orderId);
        }

        // وضعیت پرداخت
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        // وضعیت ارسال (via items — مطابق قدیم)
        if ($this->shippingStatus !== '') {
            $query->whereHas('items', fn ($q) => $q->where('shipping_status', $this->shippingStatus));
        }

        // نوع پرداخت
        if ($this->paymentType === 'installment') {
            $query->whereHas('installmentPlan', fn ($q) => $q->whereIn('status', ['pending_down_payment', 'active']));
        } elseif ($this->paymentType === 'normal') {
            $query->whereDoesntHave('installmentPlan');
        }

        // نام محصول
        if ($this->productName !== '') {
            $query->whereHas('items', fn ($q) => $q->where('title', 'like', '%' . $this->productName . '%'));
        }

        // آیدی محصول
        if ($this->productId !== '') {
            $query->whereHas('items', fn ($q) => $q->where('product_id', 'like', '%' . $this->productId . '%'));
        }

        // بازه تاریخ شمسی
        if ($this->fromDate !== '') {
            try {
                $from = Jalalian::fromFormat('Y-m-d', $this->fromDate)->toCarbon()->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Throwable) {
            }
        }

        if ($this->toDate !== '') {
            try {
                $to = Jalalian::fromFormat('Y-m-d', $this->toDate)->toCarbon()->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Throwable) {
            }
        }

        // مرتب‌سازی
        $field = $this->sortField !== '' ? $this->sortField : 'created_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc']) ? $this->sortDir : 'desc';

        if ($field === 'fullname') {
            $query->select('orders.*')
                ->join('users', 'users.id', '=', 'orders.user_id')
                ->orderByRaw("CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) {$dir}");
        } else {
            $query->orderBy($field, $dir);
        }

        return $query;
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
        $this->authorizeOrder('orders.update');
        $this->bulkStatusModal = true;
    }

    public function applyBulkStatus(OrderPersistence $persistence): void
    {
        $this->authorizeOrder('orders.update');

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

    /* ---------------- حذف ---------------- */

    public function bulkDelete(): void
    {
        $this->authorizeOrder('orders.delete');

        $persistence = app(OrderPersistence::class);
        $persistence->multipleDestroy($this->selected);

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'سفارشات انتخاب شده با موفقیت حذف شدند.', type: 'success');
    }

    protected function authorizeOrder(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    /** برچسب وضعیت پرداخت سفارش */
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

    /** برچسب وضعیت ارسال مرسوله */
    public static function shippingStatusLabel(?string $status): string
    {
        return match ($status) {
            'w-pending' => 'در انتظار بررسی',
            'pending' => 'در حال بررسی',
            'processing' => 'در حال پردازش',
            'waiting' => 'منتظر ارسال',
            'sent' => 'ارسال شد',
            'post-sent' => 'تحویل به پست',
            'delivered' => 'تحویل داده شد',
            'canceled' => 'ارسال لغو شد',
            'refunded' => 'مرجوع شده',
            default => $status ?? '—',
        };
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.orders.index')
            ->layout('admin-panel::layout');
    }
}
