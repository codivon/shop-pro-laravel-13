<?php

namespace App\Livewire\Admin\Warehouses;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * پرونده انبار — پورت ۱:۱ Back\WarehouseController
 * (show + getChartData + products + export-فیلدها + movements آخر + stockHistory با مودال)
 *
 * خروجی اکسل محصولات و CSV تاریخچه مثل الگوی فاز ۲ با رویداد open-url به روت‌های قدیمی
 * (admin.warehouses.export / admin.warehouses.product.stock-history) می‌رود تا کل منطق
 * WarehouseExport و activity-log قدیمی عیناً اجرا شود.
 */
#[Title('پرونده انبار')]
class WarehouseShow extends Component
{
    use WithPagination;

    public $warehouse; // untyped — Livewire نمی‌تواند مدل تایپ‌شده را hydrate کند (الگوی همه کامپوننت‌های فازها)

    /* ---------------- فیلترهای محصولات (پورت products قدیمی — state در URL) ---------------- */

    #[Url(as: 'search', except: '')]
    public string $searchProduct = '';

    #[Url(as: 'stock_status', except: 'all')]
    public string $stockStatus = 'all';

    #[Url(as: 'sort_by', except: '')]
    public string $sortBy = '';

    /* ---------------- مودال خروجی اکسل (پورت modal-export-excel) ---------------- */

    public string $exportStockStatus = 'all';
    public $exportCategoryId = null;
    public $exportBrandId = null;

    /* ---------------- مودال تاریخچه موجودی محصول (پورت stockHistory) ---------------- */

    public ?int $historyProductId = null;
    public string $historyProductName = '';
    public string $historyType = 'all';
    public $historyVariation = null;
    public string $historyFrom = '';
    public string $historyTo = '';

    public function mount(Warehouse $warehouse): void
    {
        auth('adminPanel')->user()?->can('warehouses.show') ?: abort(403);

        $this->warehouse = $warehouse;
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['searchProduct', 'stockStatus', 'sortBy'])) {
            $this->resetPage();
        }
    }

    /* ================= آمار (پورت show — کوئری‌ها عیناً) ================= */

    /** همه تنوع‌های انبار + فروش هر تنوع (۱ کوئری تجمعی مثل بهینه‌سازی قدیمی) */
    #[Computed]
    public function currentVariations()
    {
        $variations = Price::where('warehouse_id', $this->warehouse->id)
            ->with(['product', 'attributes'])
            ->get();

        $soldCounts = OrderItem::whereIn('price_id', $variations->pluck('id'))
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->selectRaw('price_id, SUM(quantity) as total_sold')
            ->groupBy('price_id')
            ->pluck('total_sold', 'price_id');

        $variations->each(fn ($price) => $price->sold_count = $soldCounts[$price->id] ?? 0);

        return $variations;
    }

    #[Computed]
    public function products()
    {
        // پورت دقیق متد products قدیمی
        $query = Product::whereHas('prices', function ($q) {
            $q->where('warehouse_id', $this->warehouse->id);
        })
            ->with(['prices' => function ($q) {
                $q->where('warehouse_id', $this->warehouse->id);
            }, 'category', 'brand']);

        if ($this->searchProduct !== '') {
            $query->where('title', 'like', '%'.$this->searchProduct.'%');
        }

        if ($this->stockStatus && $this->stockStatus !== 'all') {
            switch ($this->stockStatus) {
                case 'in_stock':
                    $query->whereHas('prices', function ($q) {
                        $q->where('warehouse_id', $this->warehouse->id)->where('stock', '>', 0);
                    });
                    break;
                case 'low_stock':
                    $query->whereHas('prices', function ($q) {
                        $q->where('warehouse_id', $this->warehouse->id)->where('stock', '<=', 5)->where('stock', '>', 0);
                    });
                    break;
                case 'critical_stock':
                    $query->whereHas('prices', function ($q) {
                        $q->where('warehouse_id', $this->warehouse->id)->where('stock', '<=', 2)->where('stock', '>', 0);
                    });
                    break;
                case 'out_of_stock':
                    $query->whereHas('prices', function ($q) {
                        $q->where('warehouse_id', $this->warehouse->id)->where('stock', 0);
                    });
                    break;
            }
        }

        // مرتب‌سازی — گزینه‌های sold_desc/price_* در قدیمی به switch نمی‌رسیدند (default)
        switch ($this->sortBy) {
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'stock_desc':
                $query->withMax(['prices' => function ($q) {
                    $q->where('warehouse_id', $this->warehouse->id);
                }], 'stock')
                    ->orderBy('prices_max_stock', 'desc');
                break;
            case 'stock_asc':
                $query->withMin(['prices' => function ($q) {
                    $q->where('warehouse_id', $this->warehouse->id);
                }], 'stock')
                    ->orderBy('prices_min_stock', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function stats(): array
    {
        $variations = $this->currentVariations;
        $products = $this->products;

        $totalSoldCurrent = $variations->sum('sold_count');
        $bestSeller = $variations->sortByDesc('sold_count')->first();

        return [
            'total_products' => $products->total(),
            'current_count' => $variations->count(),
            'total_stock_current' => $variations->sum('stock'),
            'total_sold' => $totalSoldCurrent,
            'total_value' => $variations->sum(fn ($p) => $p->stock * ($p->cost_price ?? $p->price)),
            'low_stock' => $variations->where('stock', '<=', 5)->where('stock', '>', 0)->count(),
            'out_of_stock' => $variations->where('stock', 0)->count(),
            'critical_stock' => $variations->where('stock', '<=', 2)->where('stock', '>', 0)->count(),
            'expiring_discounts' => $variations->where('discount_expire_at', '<=', now()->addDays(3))->where('discount_expire_at', '>=', now())->count(),
            'best_seller_count' => $bestSeller?->sold_count ?? 0,
            'best_seller_attributes' => $bestSeller?->attributes?->pluck('name')->implode(' - ') ?? 'ندارد',
            'avg_sold_per_variation' => $variations->count() > 0 ? round($totalSoldCurrent / $variations->count()) : 0,
            'total_variations' => $variations->count(),
        ];
    }

    /* ================= آخرین حرکات (پورت show) ================= */

    #[Computed]
    public function recentMovements()
    {
        return StockMovement::where('warehouse_id', $this->warehouse->id)
            ->with(['price.product', 'price.attributes'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($movement) {
                $movement->type_label = $this->movementTypeLabel($movement->type);
                $movement->type_badge_class = $this->movementTypeBadgeClass($movement->type);

                return $movement;
            });
    }

    /* ================= داده‌های نمودار (پورت getChartData) ================= */

    #[Computed]
    public function chartData(): array
    {
        // 10 محصول پرفروش (بر اساس ستون sold_count روی prices مثل قدیمی)
        $topProducts = Price::where('warehouse_id', $this->warehouse->id)
            ->whereHas('product')
            ->with('product')
            ->selectRaw('product_id, SUM(sold_count) as total_sold')
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->take(10)
            ->get()
            ->filter(fn ($price) => $price->product !== null)
            ->map(fn ($price) => [
                'name' => $price->product->title,
                'sold' => (int) $price->total_sold,
            ])->values();

        // فروش ماهانه ۶ ماه اخیر — عبارت ماه بین‌دیتابیسی (DATE_FORMAT مال MySQL است؛
        // در SQLite معادل strftime — خروجی هر دو یکسان «YYYY-MM»)
        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpression = $isSqlite
            ? DB::raw("strftime('%Y-%m', created_at) as month")
            : DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month');

        $monthlySales = OrderItem::whereHas('price', function ($q) {
            $q->where('warehouse_id', $this->warehouse->id);
        })
            ->whereHas('order', function ($q) {
                $q->where('status', 'paid');
            })
            ->select($monthExpression, DB::raw('SUM(quantity) as total_sold'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return [
            'top_products' => $topProducts,
            'monthly_sales' => $monthlySales,
        ];
    }

    protected function topProductsChartOptions(): array
    {
        $top = $this->chartData['top_products'];

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#10b981'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => ['show' => false],
            'grid' => ['top' => 20, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type' => 'category',
                'data' => $top->pluck('name')->all(),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 9, 'interval' => 0, 'rotate' => 30],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'فروش',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                    'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
                ],
            ],
            'series' => [
                [
                    'name' => 'تعداد فروش',
                    'type' => 'bar',
                    'barMaxWidth' => 34,
                    'itemStyle' => ['borderRadius' => [6, 6, 0, 0]],
                    'data' => $top->pluck('sold')->all(),
                    'label' => ['show' => true, 'position' => 'top', 'color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                ],
            ],
        ];
    }

    protected function monthlySalesChartOptions(): array
    {
        $sales = $this->chartData['monthly_sales'];

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#0ea5e9'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => ['show' => false],
            'grid' => ['top' => 20, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $sales->pluck('month')->all(),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'تعداد',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                    'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
                ],
            ],
            'series' => [
                [
                    'name' => 'فروش ماهانه',
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'circle',
                    'symbolSize' => 7,
                    'lineStyle' => ['width' => 2.5],
                    'areaStyle' => ['opacity' => 0.12],
                    'data' => $sales->pluck('total_sold')->all(),
                ],
            ],
        ];
    }

    /* ================= خروجی اکسل محصولات (پورت فرم modal-export-excel) ================= */

    public function exportWarehouse(): void
    {
        auth('adminPanel')->user()?->can('warehouses.export') ?: abort(403);

        if (! RouteFacade::has('admin.warehouses.export')) {
            $this->dispatch('toast', message: 'روت خروجی اکسل (پنل قدیمی) ثبت نشده است.', type: 'error');

            return;
        }

        $query = [
            'format' => 'excel',
            'stock_status' => $this->exportStockStatus,
            'category_id' => $this->exportCategoryId,
            'brand_id' => $this->exportBrandId,
        ];

        $this->dispatch('open-url', url: route('admin.warehouses.export', ['warehouse' => $this->warehouse->id]).'?'.http_build_query(array_filter($query, fn ($v) => $v !== null && $v !== '' && $v !== 'all')));
    }

    #[Computed]
    public function exportCategories()
    {
        return Category::whereHas('products', fn ($q) => $q->whereHas('prices', fn ($qq) => $qq->where('warehouse_id', $this->warehouse->id)))->get();
    }

    #[Computed]
    public function exportBrands()
    {
        return Brand::whereHas('products', fn ($q) => $q->whereHas('prices', fn ($qq) => $qq->where('warehouse_id', $this->warehouse->id)))->get();
    }

    /* ================= مودال تاریخچه موجودی محصول (پورت stockHistory) ================= */

    public function openStockHistory(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $this->historyProductId = $product->id;
        $this->historyProductName = $product->title;
        $this->reset('historyType', 'historyVariation', 'historyFrom', 'historyTo');
        $this->historyType = 'all';
        $this->resetPage('page');
    }

    public function closeStockHistory(): void
    {
        $this->reset('historyProductId', 'historyProductName', 'historyType', 'historyVariation', 'historyFrom', 'historyTo');
    }

    #[Computed]
    public function historyPrices()
    {
        if (! $this->historyProductId) {
            return collect();
        }

        return Price::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->historyProductId)
            ->with('attributes')
            ->get();
    }

    #[Computed]
    public function historyItems()
    {
        if (! $this->historyProductId) {
            return null;
        }

        $prices = $this->historyPrices;

        $query = StockMovement::where('warehouse_id', $this->warehouse->id)
            ->whereIn('price_id', $prices->pluck('id'))
            ->with(['price.product', 'price.attributes'])
            ->orderBy('created_at', 'desc');

        if ($this->historyType && $this->historyType !== 'all') {
            $query->where('type', $this->historyType);
        }

        if ($this->historyVariation && $this->historyVariation !== 'all') {
            $query->where('price_id', $this->historyVariation);
        }

        // پورت فیلتر تاریخ قدیمی: convertPersianToEnglish + Jalalian::fromFormat('Y-m-d')
        if ($this->historyFrom) {
            $dateFrom = convertPersianToEnglish($this->historyFrom);
            $dateFrom = Jalalian::fromFormat('Y-m-d', $dateFrom)->toCarbon()->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($this->historyTo) {
            $dateTo = convertPersianToEnglish($this->historyTo);
            $dateTo = Jalalian::fromFormat('Y-m-d', $dateTo)->toCarbon()->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->paginate(50);
    }

    #[Computed]
    public function historyStats(): array
    {
        $prices = $this->historyPrices;

        return [
            'total_stock' => $prices->sum('stock'),
            'total_reserved' => $prices->sum('reserved_stock'),
            'total_sold' => $prices->sum('sold_count'),
            'variations_count' => $prices->count(),
        ];
    }

    /** خروجی CSV تاریخچه — مثل قدیمی به روت legacy با export=excel می‌رود */
    public function exportHistory(): void
    {
        if (! $this->historyProductId) {
            return;
        }

        if (! RouteFacade::has('admin.warehouses.product.stock-history')) {
            $this->dispatch('toast', message: 'روت خروجی تاریخچه (پنل قدیمی) ثبت نشده است.', type: 'error');

            return;
        }

        $query = array_filter([
            'export' => 'excel',
            'movement_type' => $this->historyType,
            'variation_id' => $this->historyVariation,
            'date_from' => $this->historyFrom,
            'date_to' => $this->historyTo,
        ], fn ($v) => $v !== null && $v !== '' && $v !== 'all');

        $this->dispatch('open-url', url: route('admin.warehouses.product.stock-history', [
            'warehouse' => $this->warehouse->id,
            'product' => $this->historyProductId,
        ]).'?'.http_build_query($query));
    }

    public function applyHistoryFilters(): void
    {
        $this->resetPage();
    }

    /* ================= برچسب‌های نوع حرکت (پورت private های کنترلر) ================= */

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
        // کلاس‌های بج قدیمی به معادل دیزاین‌سیستم پنل نگاشت شدند
        return match ($type) {
            'in' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
            'out' => 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400',
            'reserve' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
            'unreserve' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400',
            'adjustment' => 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2',
            default => 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2',
        };
    }

    /* ================= ابزار ================= */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.warehouses.show')
            ->layout('admin-panel::layout');
    }
}
