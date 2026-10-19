<?php

namespace App\Livewire\Admin\Warehouses;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * تاریخچه حرکات انبار — پورت ۱:۱ Back\WarehouseController@movements
 *
 * گیت «warehouses.movements» عیناً قدیمی.
 * خروجی اکسل این صفحه در قدیمی به متد exportMovements اشاره می‌کرد که هرگز در کنترلر
 * تعریف نشده بود (روت شکسته legacy) — پورت نشد؛ در گزارش مستند شده است.
 */
#[Title('تاریخچه حرکات انبار')]
class WarehouseMovements extends Component
{
    use WithPagination;

    public $warehouse; // untyped — Livewire نمی‌تواند مدل تایپ‌شده را hydrate کند (الگوی همه کامپوننت‌های فازها)

    #[Url(as: 'type', except: 'all')]
    public string $type = 'all';

    #[Url(as: 'product_id', except: '')]
    public $productId = null;

    #[Url(as: 'from_date', except: '')]
    public string $fromDate = '';

    #[Url(as: 'to_date', except: '')]
    public string $toDate = '';

    public function mount(Warehouse $warehouse): void
    {
        auth('adminPanel')->user()?->can('warehouses.movements') ?: abort(403);

        $this->warehouse = $warehouse;
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['type', 'productId', 'fromDate', 'toDate'])) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function movements()
    {
        return StockMovement::where('warehouse_id', $this->warehouse->id)
            ->with(['price.product', 'order'])
            ->when($this->type && $this->type !== 'all', fn ($query) => $query->where('type', $this->type))
            ->when($this->productId && $this->productId !== 'all', function ($query) {
                $query->whereHas('price', function ($q) {
                    $q->where('product_id', $this->productId);
                });
            })
            ->when($this->fromDate, function ($query) {
                // پورت دقیق قدیمی: Jalalian::fromFormat('Y/m/d')
                $fromDate = Jalalian::fromFormat('Y/m/d', $this->fromDate)->toCarbon();
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($this->toDate, function ($query) {
                $toDate = Jalalian::fromFormat('Y/m/d', $this->toDate)->toCarbon();
                $query->whereDate('created_at', '<=', $toDate);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(30);
    }

    /** لیست محصولات برای فیلتر — پورت قدیمی */
    #[Computed]
    public function products()
    {
        return Product::whereHas('prices', function ($q) {
            $q->where('warehouse_id', $this->warehouse->id);
        })->get();
    }

    /**
     * آمار نوع حرکات — قدیمی روی صفحه‌بندی جاری شمرده بود (باگ نمایشی)؛
     * اینجا روی کل رکوردهای انبار با فیلتر جاری محاسبه می‌شود (رفع مستند).
     */
    #[Computed]
    public function stats(): array
    {
        $base = StockMovement::where('warehouse_id', $this->warehouse->id)
            ->when($this->productId && $this->productId !== 'all', function ($query) {
                $query->whereHas('price', function ($q) {
                    $q->where('product_id', $this->productId);
                });
            })
            ->when($this->fromDate, function ($query) {
                $fromDate = Jalalian::fromFormat('Y/m/d', $this->fromDate)->toCarbon();
                $query->whereDate('created_at', '>=', $fromDate);
            })
            ->when($this->toDate, function ($query) {
                $toDate = Jalalian::fromFormat('Y/m/d', $this->toDate)->toCarbon();
                $query->whereDate('created_at', '<=', $toDate);
            });

        return [
            'total' => (clone $base)->count(),
            'in' => (clone $base)->where('type', 'in')->count(),
            'out' => (clone $base)->where('type', 'out')->count(),
            'reserve' => (clone $base)->where('type', 'reserve')->count(),
        ];
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->type = 'all';
        $this->productId = null;
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
    }

    public function movementTypeLabel($type): string
    {
        return match ($type) {
            'in' => 'ورود',
            'out' => 'خروج',
            'reserve' => 'رزرو',
            'unreserve' => 'لغو رزرو',
            'adjustment' => 'تنظیم دستی',
            default => (string) $type,
        };
    }

    public function movementTypeBadgeClass($type): string
    {
        return match ($type) {
            'in' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
            'out' => 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400',
            'reserve' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            'unreserve' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400',
            'adjustment' => 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2',
            default => 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2',
        };
    }

    public static function jdate($date, $format = 'Y/m/d H:i:s'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.warehouses.movements')
            ->layout('admin-panel::layout');
    }
}
