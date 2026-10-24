<?php

namespace App\Livewire\Admin\Warehouses;

use App\Models\Price;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * بروزرسانی گروهی موجودی — پورت ۱:۱ Back\WarehouseController@bulkStockData + bulkStockUpdate
 *
 * مودال قدیمی (modal-bulk-stock) به صفحه مستقل تبدیل شد؛ عملیات گروهی (تنظیم/افزایش/کاهش/درصدی)
 * مثل قدیمی در همان صفحه محاسبه و سپس stocks برای bulkStockUpdate ارسال می‌شود.
 * گیت: mount با warehouses.show (در قدیمی authorize نداشت — از show در دسترس بود؛ الگوی قرارداد فازها).
 */
#[Title('بروزرسانی گروهی موجودی')]
class WarehouseBulkStock extends Component
{
    use WithPagination;

    public $warehouse; // untyped — Livewire نمی‌تواند مدل تایپ‌شده را hydrate کند (الگوی همه کامپوننت‌های فازها)

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** ردیف‌های انتخاب‌شده (checkbox) */
    public array $selected = [];

    /** موجودی جدید هر تنوع: [price_id => stock] */
    public array $stocks = [];

    /* ---------------- پنل عملیات گروهی (پورت bsm-ops) ---------------- */

    public string $operationType = 'set';
    public $bulkValue = null;
    public string $applyScope = 'selected';
    public string $description = '';

    public function mount(Warehouse $warehouse): void
    {
        auth('adminPanel')->user()?->can('warehouses.show') ?: abort(403);

        $this->warehouse = $warehouse;
    }

    public function updating(string $name, $value): void
    {
        if ($name === 'search') {
            $this->resetPage();
        }
    }

    /* ---------------- لیست تنوع‌ها (پورت bulkStockData) ---------------- */

    #[Computed]
    public function variations()
    {
        $query = $this->warehouse->prices()
            ->with(['product.brand', 'product.category', 'attributes.group'])
            ->select('prices.*');

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('product', function ($pq) {
                    $pq->where('title', 'like', "%{$this->search}%")
                        ->orWhere('id', $this->search);
                })->orWhere('prices.id', $this->search);
            });
        }

        // فروش هر تنوع (پورت withCount orderItems as sold_count)
        $query->withCount(['orderItems as sold_count' => function ($q) {
            $q->whereHas('order', fn ($o) => $o->where('status', 'paid'));
        }]);

        $query->orderBy('product_id')->orderBy('prices.id');

        return $query->paginate(40);
    }

    /* ---------------- عملیات گروهی (محاسبه مقادیر جدید مثل JS قدیمی) ---------------- */

    public function applyBulkOperation(): void
    {
        $value = (float) ($this->bulkValue ?? 0);

        // بر اساس scope: انتخاب‌شده‌ها / محصولاتِ انتخاب‌شده‌ها / همه ردیف‌های همین صفحه
        $pageRows = $this->variations->getCollection()->keyBy('id');

        $targets = collect();

        if ($this->applyScope === 'selected') {
            $targets = $pageRows->only($this->selected);
        } elseif ($this->applyScope === 'product') {
            $productIds = $pageRows->only($this->selected)->pluck('product_id')->unique();
            $targets = $pageRows->filter(fn ($row) => $productIds->contains($row->product_id));
        } else {
            $targets = $pageRows;
        }

        if ($targets->isEmpty()) {
            $this->dispatch('toast', message: 'هیچ تنوعی برای اعمال انتخاب نشده است.', type: 'warning');

            return;
        }

        foreach ($targets as $price) {
            $stock = (int) $price->stock;

            $newStock = match ($this->operationType) {
                'set' => (int) $value,
                'add' => $stock + (int) $value,
                'subtract' => max(0, $stock - (int) $value),
                'percentage_add' => (int) round($stock + ($stock * $value / 100)),
                'percentage_subtract' => (int) round($stock - ($stock * $value / 100)),
                default => $stock,
            };

            $this->stocks[$price->id] = max(0, $newStock);
        }

        $this->dispatch('toast', message: 'مقادیر جدید روی ردیف‌ها اعمال شد. برای ثبت، «ذخیره تغییرات» را بزنید.', type: 'success');
    }

    /* ---------------- ثبت (پورت bulkStockUpdate) ---------------- */

    public function saveBulkUpdate(): void
    {
        $warehouse = $this->warehouse;

        $this->validate([
            'stocks' => 'required|array',
            'stocks.*' => 'required|integer|min:0',
        ], [], [
            'stocks' => 'موجودی‌ها',
        ]);

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";
        $startTime = microtime(true);

        try {
            $updatedCount = 0;
            $changesList = [];

            DB::transaction(function () use (&$updatedCount, &$changesList) {
                foreach ($this->stocks as $priceId => $itemStock) {
                    $price = Price::where('id', $priceId)
                        ->where('warehouse_id', $this->warehouse->id)
                        ->with('product:id,title', 'attributes:id,name')
                        ->first();

                    if ($price && $price->stock != $itemStock) {
                        $oldStock = $price->stock;
                        $newStock = (int) $itemStock;
                        $difference = $newStock - $oldStock;

                        $productTitle = $price->product->title ?? 'نامشخص';
                        $attributes = $price->attributes->pluck('name')->implode(' - ');
                        $key = $productTitle.($attributes ? " ({$attributes})" : '');

                        $changesList[$key] = [
                            'old' => $oldStock,
                            'new' => $newStock,
                            'difference' => $difference,
                        ];

                        app(StockMovementService::class)->adjustment(
                            $price,
                            $newStock,
                            $this->description ?: "بروزرسانی گروهی موجودی از {$oldStock} به {$newStock}"
                        );

                        $updatedCount++;
                    }
                }
            });

            $duration = round(microtime(true) - $startTime, 2);

            // ===== ثبت لاگ با جزئیات کامل (پورت دقیق) =====
            $oldData = [];
            $newData = [];

            foreach ($changesList as $key => $change) {
                $oldData[$key] = $change['old'];
                $newData[$key] = $change['new'];
            }

            $logDetails = [
                'action' => 'بروزرسانی گروهی موجودی انبار را انجام داد',
                'warehouse_name' => $warehouseName,
                'warehouse_id' => $warehouse->id,
                'total_items_processed' => count($this->stocks),
                'updated_count' => $updatedCount,
                'duration_seconds' => $duration,
                'ip' => request()->ip(),
            ];

            if (! empty($oldData)) {
                $logDetails['old'] = $oldData;
                $logDetails['attributes'] = $newData;
            }

            if ($this->description) {
                $logDetails['description'] = $this->description;
            }

            $logMessage = "مدیر {$adminName} بروزرسانی گروهی موجودی انبار «{$warehouseName}» را انجام داد";
            if ($updatedCount > 0) {
                $logMessage .= " و موجودی {$updatedCount} آیتم را تغییر داد";
            } else {
                $logMessage .= ' (تغییری اعمال نشد)';
            }

            activity()
                ->performedOn($warehouse)
                ->event('updated')
                ->causedBy(auth('adminPanel')->user())
                ->withProperties($logDetails)
                ->log($logMessage);

            $this->stocks = [];

            // پیام دقیق قدیمی
            $this->dispatch('toast', message: "بروزرسانی گروهی با موفقیت انجام شد. {$updatedCount} آیتم بروزرسانی شد.", type: 'success');
        } catch (\Exception $e) {
            \Log::error('Bulk stock update failed: '.$e->getMessage());

            activity()
                ->performedOn($warehouse)
                ->causedBy(auth('adminPanel')->user())
                ->event('updated')
                ->withProperties([
                    'action' => 'bulk_stock_update_failed',
                    'warehouse_name' => $warehouseName,
                    'warehouse_id' => $warehouse->id,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} بروزرسانی گروهی موجودی انبار «{$warehouseName}» با خطا مواجه شد");

            $this->dispatch('toast', message: 'خطا در بروزرسانی: '.$e->getMessage(), type: 'error');
        }
    }

    public function toggleSelectAll(): void
    {
        $pageIds = $this->variations->getCollection()->pluck('id')->all();

        // اگر همه انتخاب شده باشند → پاک‌کردن انتخاب صفحه
        if (count(array_intersect($pageIds, $this->selected)) === count($pageIds) && count($pageIds) > 0) {
            $this->selected = array_values(array_diff($this->selected, $pageIds));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $pageIds)));
        }
    }

    public function resetForm(): void
    {
        $this->stocks = [];
        $this->selected = [];
        $this->description = '';
        $this->bulkValue = null;
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
        return view('admin-panel::livewire.admin.warehouses.bulk-stock')
            ->layout('admin-panel::layout');
    }
}
