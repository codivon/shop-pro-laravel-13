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
 * سرشماری انبار — پورت ۱:۱ Back\WarehouseController@stockTakeData + stockTake
 *
 * در قدیمی این دو متد JSON/AJAX مودال show بودند؛ در پنل جدید صفحه مستقل شدند
 * (مطابق پیشنهاد اسپک) — منطق و پیام‌ها و activity-log دقیقاً همان است.
 * گیت: در قدیمی روی این متدها authorize نبود (صفحه show پشت warehouses.show بود) —
 * اینجا mount با warehouses.show محافظت می‌شود (الگوی قرارداد فازها).
 */
#[Title('سرشماری انبار')]
class WarehouseStockTake extends Component
{
    use WithPagination;

    public $warehouse; // untyped — Livewire نمی‌تواند مدل تایپ‌شده را hydrate کند (الگوی همه کامپوننت‌های فازها)

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** 'empty' | 'low' | '' — پورت stock_filter قدیمی */
    #[Url(as: 'filter', except: '')]
    public string $stockFilter = '';

    /** موجودی واقعی واردشده برای هر تنوع: [price_id => actual_stock] */
    public array $stocks = [];

    public string $description = '';

    /** نتیجه آخرین سرشماری برای نمایش ردیف مغایرت‌ها */
    public ?array $lastResult = null;

    public function mount(Warehouse $warehouse): void
    {
        auth('adminPanel')->user()?->can('warehouses.show') ?: abort(403);

        $this->warehouse = $warehouse;
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'stockFilter'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست تنوع‌ها (پورت stockTakeData) ---------------- */

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

        if ($this->stockFilter === 'empty') {
            $query->where('stock', 0);
        } elseif ($this->stockFilter === 'low') {
            $query->where('stock', '>', 0)->where('stock', '<=', 5);
        }

        // مرتب‌سازی قدیمی: اتمام موجودی، کم‌موجود، بقیه (CASE عیناً — بین‌دیتابیسی)
        $query->orderByRaw('CASE WHEN stock = 0 THEN 0 WHEN stock <= 5 THEN 1 ELSE 2 END')
            ->orderBy('product_id')
            ->orderBy('prices.id');

        return $query->paginate(50);
    }

    /** آمار کل انبار — مستقل از فیلتر جستجو (پورت دقیق) */
    #[Computed]
    public function stats(): array
    {
        $all = $this->warehouse->prices();
        $total = (clone $all)->count();
        $empty = (clone $all)->where('stock', 0)->count();
        $low = (clone $all)->where('stock', '>', 0)->where('stock', '<=', 5)->count();

        return [
            'total' => $total,
            'empty' => $empty,
            'low' => $low,
            'ok' => $total - $empty - $low,
        ];
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->stockFilter = '';
        $this->resetPage();
    }

    /* ---------------- ثبت سرشماری (پورت stockTake) ---------------- */

    public function submitStockTake(): void
    {
        $warehouse = $this->warehouse;

        // پورت validation قدیمی (stocks.*.price_id / actual_stock در ساختار map به کلید/مقدار تبدیل شده)
        $this->validate([
            'stocks' => 'nullable|array',
            'stocks.*' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ], [], [
            'stocks' => 'موجودی واقعی',
            'description' => 'توضیحات',
        ]);

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";
        $startTime = microtime(true);

        try {
            $result = DB::transaction(function () {
                $updatedCount = 0;
                $totalDifference = 0;
                $differencesList = [];
                $allProcessed = [];
                $notFoundList = [];

                if (empty($this->stocks)) {
                    $allPrices = Price::where('warehouse_id', $this->warehouse->id)->get();
                    $confirmedCount = $allPrices->count();
                    foreach ($allPrices as $price) {
                        app(StockMovementService::class)->adjustment(
                            $price,
                            $price->stock,
                            $this->description ? $this->description.' (سرشماری دوره‌ای - بدون تغییر)' : 'سرشماری دوره‌ای انبار - تأیید موجودی فعلی'
                        );
                    }

                    return [
                        'updated_count' => 0,
                        'total_difference' => 0,
                        'differences_list' => [],
                        'not_found_count' => 0,
                        'confirmed_count' => $confirmedCount,
                        'message' => 'سرشماری انجام شد. هیچ مغایرتی یافت نشد.',
                        'is_confirmation_only' => true,
                    ];
                }

                foreach ($this->stocks as $priceId => $actualStock) {
                    $priceId = (int) $priceId;
                    $newStock = (int) $actualStock;

                    $price = Price::where('id', $priceId)
                        ->where('warehouse_id', $this->warehouse->id)
                        ->with('product:id,title', 'attributes:id,name')
                        ->first();

                    if (! $price) {
                        $notFoundList[] = $priceId;
                        continue;
                    }

                    $oldStock = (int) $price->stock;
                    $difference = $newStock - $oldStock;
                    $totalDifference += $difference;
                    $allProcessed[] = $priceId;

                    $descriptionText = $this->description ? $this->description.' (سرشماری' : 'سرشماری دوره‌ای انبار';

                    if ($oldStock !== $newStock) {
                        $updatedCount++;
                        $descriptionText .= " - اصلاح موجودی از {$oldStock} به {$newStock})";

                        $attributesName = $price->attributes->pluck('name')->implode(' - ');
                        $differencesList[] = [
                            'product_title' => $price->product->title ?? '—',
                            'attributes' => $attributesName ?: 'بدون ویژگی',
                            'old_stock' => $oldStock,
                            'new_stock' => $newStock,
                            'difference' => $difference,
                        ];
                    } else {
                        $descriptionText .= " - تأیید موجودی {$oldStock})";
                    }

                    app(StockMovementService::class)->adjustment($price, $newStock, $descriptionText);
                }

                $allWarehousePrices = Price::where('warehouse_id', $this->warehouse->id)
                    ->whereNotIn('id', $allProcessed)
                    ->get();

                foreach ($allWarehousePrices as $price) {
                    app(StockMovementService::class)->adjustment(
                        $price,
                        $price->stock,
                        $this->description
                            ? $this->description." (سرشماری - تأیید موجودی فعلی {$price->stock})"
                            : "سرشماری دوره‌ای انبار - تأیید موجودی فعلی {$price->stock}"
                    );
                }

                return [
                    'updated_count' => $updatedCount,
                    'total_difference' => $totalDifference,
                    'differences_list' => $differencesList,
                    'not_found_count' => count($notFoundList),
                    'confirmed_count' => $allWarehousePrices->count(),
                    'is_confirmation_only' => false,
                ];
            });

            $duration = round(microtime(true) - $startTime, 2);

            // ===== ثبت لاگ با جزئیات کامل (پورت دقیق) =====
            $oldData = [];
            $newData = [];

            if (! empty($result['differences_list'])) {
                foreach ($result['differences_list'] as $change) {
                    $key = $change['product_title'].($change['attributes'] ? " ({$change['attributes']})" : '');
                    $oldData[$key] = $change['old_stock'];
                    $newData[$key] = $change['new_stock'];
                }
            }

            if ($result['confirmed_count'] > 0 && empty($result['differences_list'])) {
                $oldData['وضعیت'] = 'قبل از سرشماری';
                $newData['وضعیت'] = 'تأیید موجودی (بدون تغییر)';
            }

            $logDetails = [
                'action' => 'سرشماری انبار را انجام داد',
                'warehouse_name' => $warehouseName,
                'warehouse_id' => $warehouse->id,
                'submitted_items' => count($this->stocks),
                'updated_count' => $result['updated_count'],
                'confirmed_count' => $result['confirmed_count'] ?? 0,
                'not_found_count' => $result['not_found_count'],
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

            $logMessage = "مدیر {$adminName} سرشماری انبار «{$warehouseName}» را انجام داد";

            if ($result['updated_count'] > 0) {
                $logMessage .= " و موجودی {$result['updated_count']} تنوع را تصحیح کرد";
            } elseif ($result['is_confirmation_only'] ?? false) {
                $logMessage .= ' (تأیید موجودی - بدون تغییر)';
            }

            activity()
                ->performedOn($warehouse)
                ->event('updated')
                ->causedBy(auth('adminPanel')->user())
                ->withProperties($logDetails)
                ->log($logMessage);

            // پیام دقیق قدیمی
            $parts = ['سرشماری انبار با موفقیت انجام شد.'];
            if ($result['updated_count'] > 0) {
                $parts[] = "{$result['updated_count']} تنوع بروزرسانی شد.";
            } elseif (empty($this->stocks)) {
                $parts[] = 'هیچ تغییری یافت نشد. تمام موجودی‌ها تأیید شدند.';
            } else {
                $parts[] = 'هیچ مغایرتی یافت نشد.';
            }

            if ($result['total_difference'] !== 0) {
                $sign = $result['total_difference'] > 0 ? '+' : '';
                $parts[] = 'مغایرت کل: '.$sign.number_format($result['total_difference']).' عدد';
            }

            $this->lastResult = $result;
            $this->stocks = [];

            $this->dispatch('toast', message: implode(' ', $parts), type: 'success');
        } catch (\Exception $e) {
            \Log::error('Stock take failed', ['warehouse_id' => $warehouse->id, 'error' => $e->getMessage()]);

            activity()
                ->performedOn($warehouse)
                ->causedBy(auth('adminPanel')->user())
                ->event('updated')
                ->withProperties([
                    'action' => 'stock_take_failed',
                    'warehouse_name' => $warehouseName,
                    'warehouse_id' => $warehouse->id,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ])
                ->log("مدیر {$adminName} سرشماری انبار «{$warehouseName}» با خطا مواجه شد");

            $this->dispatch('toast', message: 'خطا در ثبت سرشماری: '.$e->getMessage(), type: 'error');
        }
    }

    /** پر کردن همه فیلدهای صفحه با موجودی فعلی (ابزار درون‌صفحه‌ای — معادل «تأیید همه» قدیمی بدون ارسال فرم) */
    public function fillWithCurrent(): void
    {
        $this->stocks = $this->warehouse->prices()->pluck('stock', 'id')->toArray();
    }

    public function resetStocks(): void
    {
        $this->stocks = [];
        $this->description = '';
        $this->lastResult = null;
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
        return view('admin-panel::livewire.admin.warehouses.stock-take')
            ->layout('admin-panel::layout');
    }
}
