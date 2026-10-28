<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۱۹ — «موجودی و تنوع‌ها» پنل مدیریت (درخواست کاربر):
 * نمای کلی موجودی کل هر محصول + ریز تک‌تک تنوع‌ها با تفکیک
 * «فروش خودِ سایت» و «تنوع‌های فروشندگان» (چندفروشندگی).
 *
 * فاز ۱۲۰ — (درخواست کاربر) ارتقای کامل صفحه:
 *  • فیلترهای بیشتر: مالکیت تنوع (سایت/فروشنده) + فروشندهٔ خاص + وضعیت موجودی
 *    (موجود/کم‌موجود/ناموجود) + وضعیت نمایش + انبار — همه در URL
 *  • «فیلتر تنوع مشتری»: تفکیک تنوع‌های مالکیت فروشنده (اعضای بازار) از فروش خودِ سایت
 *  • آمارِ زندهٔ وابسته به فیلترها + ۴ نمودار ECharts (مالکیت، وضعیت موجودی،
 *    پر موجودی‌ترین محصولات، فروشندگان با بیشترین تنوع)
 *  • فیلتر مدرن دسته‌بندی (پیکر درختی) هم‌تجربه با پنل فروشنده
 *
 * جای قرارگیری: گروه «عملیات محصولات → محصولات → موجودی و تنوع‌ها»
 * (products.stock) — پرمیشن: products.index (بدون پرمیشن جدید).
 */
#[Title('موجودی و تنوع‌ها')]
class StockIndex extends Component
{
    use WithPagination;

    /** آستانهٔ «کم‌موجود» — ۱ تا ۵ عدد */
    public const LOW_STOCK = 5;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'title', except: '')]
    public string $title = '';

    #[Url(as: 'category_id', except: [])]
    public array $categoryId = [];

    /** فیلتر تنوع مشتری — مالکیت تنوع: all | site (فروش خود سایت) | seller (تنوع فروشنده/مشتری بازار) */
    #[Url(as: 'owner', except: 'all')]
    public string $owner = 'all';

    #[Url(as: 'seller_id', except: 0)]
    public int $sellerId = 0;

    /** وضعیت موجودی: all | in | low | out */
    #[Url(as: 'stock', except: 'all')]
    public string $stockStatus = 'all';

    /** وضعیت نمایش تنوع: all | yes | no */
    #[Url(as: 'published', except: 'all')]
    public string $published = 'all';

    #[Url(as: 'warehouse_id', except: 0)]
    public int $warehouseId = 0;

    public int $perPage = 10;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('products.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['title', 'categoryId', 'perPage', 'owner', 'sellerId', 'stockStatus', 'published', 'warehouseId'], true)) {
            $this->resetPage();
        }
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['title', 'categoryId', 'perPage', 'owner', 'sellerId', 'stockStatus', 'published', 'warehouseId'], true)) {
            $this->refreshCharts();
        }
    }

    /* ---------------- تغییرات فیلتر از روش کلیک/دکمه ---------------- */

    /** سوییچ کارت‌های آماری — کلیک دوباره = حذف فیلتر */
    public function toggleOwner(string $value): void
    {
        $this->owner = $this->owner === $value ? 'all' : $value;
        $this->afterFilterChange();
    }

    public function toggleStock(string $value): void
    {
        $this->stockStatus = $this->stockStatus === $value ? 'all' : $value;
        $this->afterFilterChange();
    }

    public function removeCategory(int $id): void
    {
        $this->categoryId = array_values(
            array_filter($this->categoryId, fn ($catId) => (int) $catId !== $id)
        );

        $this->afterFilterChange();
    }

    public function resetFilters(): void
    {
        $this->title = '';
        $this->categoryId = [];
        $this->owner = 'all';
        $this->sellerId = 0;
        $this->stockStatus = 'all';
        $this->published = 'all';
        $this->warehouseId = 0;

        $this->afterFilterChange();
    }

    private function afterFilterChange(): void
    {
        $this->resetPage();
        $this->refreshCharts();
    }

    /** به‌روزرسانی نمودارها پس از هر تغییر فیلتر (dispatch به اسکریپت صفحه) */
    private function refreshCharts(): void
    {
        $this->dispatch('chart-update', charts: $this->chartPayload());
    }

    /* ---------------- کوئری‌های پایه ---------------- */

    /** آیا فیلتری در «سطح تنوع» فعال است؟ */
    public function hasVariantFilters(): bool
    {
        return $this->owner !== 'all'
            || $this->sellerId > 0
            || $this->stockStatus !== 'all'
            || $this->published !== 'all'
            || $this->warehouseId > 0;
    }

    /** اعمال فیلترهای سطح تنوع روی هر کوئری Price (پایهٔ آمار/نمودار/لیست) */
    private function applyVariantFilters($query)
    {
        match ($this->owner) {
            'site' => $query->whereNull('seller_id'),
            'seller' => $query->whereNotNull('seller_id'),
            default => null,
        };

        if ($this->sellerId > 0) {
            $query->where('seller_id', $this->sellerId);
        }

        if ($this->published !== 'all') {
            $query->where('published', $this->published === 'yes' ? 1 : 0);
        }

        if ($this->warehouseId > 0) {
            $query->where('warehouse_id', $this->warehouseId);
        }

        match ($this->stockStatus) {
            'in' => $query->where('stock', '>', self::LOW_STOCK),
            'low' => $query->whereBetween('stock', [1, self::LOW_STOCK]),
            'out' => $query->where('stock', '<=', 0),
            default => null,
        };

        return $query;
    }

    /** کوئری تنوع‌های منطبق (همراه فیلترهای سطح محصول: عنوان/دسته) */
    private function variantQuery()
    {
        $query = Price::query();

        if ($this->title !== '' || $this->categoryId) {
            $query->whereHas('product', function ($q) {
                if ($this->title !== '') {
                    $q->where('title', 'like', '%'.$this->title.'%');
                }

                if ($this->categoryId) {
                    $q->whereIn('category_id', $this->expandedCategoryIds());
                }
            });
        }

        return $this->applyVariantFilters($query);
    }

    /** انطباق یک تنوع با فیلترهای فعلی (سمت کالکشن برای جدول هر محصول) */
    public function variantMatches(Price $price): bool
    {
        if ($this->owner === 'site' && $price->seller_id) {
            return false;
        }

        if ($this->owner === 'seller' && ! $price->seller_id) {
            return false;
        }

        if ($this->sellerId > 0 && (int) $price->seller_id !== $this->sellerId) {
            return false;
        }

        if ($this->published !== 'all' && (int) (bool) $price->published !== ($this->published === 'yes' ? 1 : 0)) {
            return false;
        }

        if ($this->warehouseId > 0 && (int) $price->warehouse_id !== $this->warehouseId) {
            return false;
        }

        $stock = (int) $price->stock;

        return match ($this->stockStatus) {
            'in' => $stock > self::LOW_STOCK,
            'low' => $stock > 0 && $stock <= self::LOW_STOCK,
            'out' => $stock <= 0,
            default => true,
        };
    }

    private function expandedCategoryIds(): array
    {
        $allcats = $this->categoryId;

        foreach ($this->categoryId as $category) {
            $category = Category::find($category);

            if ($category) {
                $allcats = array_merge($category->allChildCategories(), $allcats);
            }
        }

        return $allcats;
    }

    /* ---------------- لیست محصولات با تنوع‌ها ---------------- */

    #[Computed]
    public function products()
    {
        $query = Product::query()->detectLang();

        if ($this->title !== '') {
            $query->where('title', 'like', '%'.$this->title.'%');
        }

        if ($this->categoryId) {
            $query->whereIn('category_id', $this->expandedCategoryIds());
        }

        // فیلتر سطح تنوع فعال است → فقط محصولاتی که حداقل یک تنوعِ منطبق دارند
        if ($this->hasVariantFilters()) {
            $query->whereHas('prices', fn ($q) => $this->applyVariantFilters($q));
        }

        return $query->with([
            'category',
            'prices' => fn ($q) => $q->orderBy('id'),
            'prices.get_attributes.group',
            'prices.seller.seller_info',
            'prices.warehouse',
        ])
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- آمار زنده (وابسته به فیلترها) ---------------- */

    #[Computed]
    public function stats(): array
    {
        $base = $this->variantQuery();

        return [
            'total_stock' => (int) (clone $base)->sum('stock'),
            'total_variants' => (int) (clone $base)->count(),
            'site_variants' => (int) (clone $base)->whereNull('seller_id')->count(),
            'seller_variants' => (int) (clone $base)->whereNotNull('seller_id')->count(),
            'in_stock' => (int) (clone $base)->where('stock', '>', self::LOW_STOCK)->count(),
            'low_stock' => (int) (clone $base)->whereBetween('stock', [1, self::LOW_STOCK])->count(),
            'out_of_stock' => (int) (clone $base)->where('stock', '<=', 0)->count(),
            'products_count' => $this->products->total(),
        ];
    }

    /* ---------------- دادهٔ نمودارها ---------------- */

    #[Computed]
    public function chartData(): array
    {
        $base = $this->variantQuery();

        $topProducts = (clone $base)
            ->selectRaw('product_id, SUM(stock) as stock_sum')
            ->groupBy('product_id')
            ->orderByDesc('stock_sum')
            ->limit(8)
            ->get();

        $productTitles = Product::query()
            ->whereIn('id', $topProducts->pluck('product_id'))
            ->pluck('title', 'id');

        $topSellers = collect();

        if ($this->owner !== 'site') {
            $topSellers = (clone $base)->whereNotNull('seller_id')
                ->selectRaw('seller_id, COUNT(*) as variants_count')
                ->groupBy('seller_id')
                ->orderByDesc('variants_count')
                ->limit(8)
                ->get();
        }

        $sellerNames = Seller::query()->with('seller_info')
            ->whereIn('id', $topSellers->pluck('seller_id'))
            ->get()
            ->mapWithKeys(fn ($seller) => [
                $seller->id => $seller->seller_info?->business_name
                    ?: trim(($seller->seller_info?->first_name ?? '').' '.($seller->seller_info?->last_name ?? ''))
                    ?: ('فروشنده #'.$seller->id),
            ]);

        return [
            'owner' => [
                ['name' => 'فروش خودِ سایت', 'value' => $this->stats['site_variants']],
                ['name' => 'تنوع فروشندگان', 'value' => $this->stats['seller_variants']],
            ],
            'stock' => [
                ['name' => 'موجود', 'value' => $this->stats['in_stock']],
                ['name' => 'کم‌موجود', 'value' => $this->stats['low_stock']],
                ['name' => 'ناموجود', 'value' => $this->stats['out_of_stock']],
            ],
            'topProducts' => $topProducts
                ->map(fn ($row) => [
                    'name' => Str::limit((string) ($productTitles[$row->product_id] ?? ('#'.$row->product_id)), 24),
                    'value' => (int) $row->stock_sum,
                ])
                ->values()
                ->all(),
            'topSellers' => $topSellers
                ->map(fn ($row) => [
                    'name' => Str::limit((string) ($sellerNames[$row->seller_id] ?? ('فروشنده #'.$row->seller_id)), 24),
                    'value' => (int) $row->variants_count,
                ])
                ->values()
                ->all(),
        ];
    }

    /* ---------------- گزینه‌های ECharts هر نمودار ---------------- */

    public function chartOption(string $key): array
    {
        $data = $this->chartData;

        return match ($key) {
            'stock-owner-chart' => $this->donutOptions($data['owner'], ['#059669', '#0ea5e9']),
            'stock-status-chart' => $this->donutOptions($data['stock'], ['#059669', '#f59e0b', '#f43f5e']),
            'stock-top-products-chart' => $this->hBarOptions($data['topProducts'], '#059669', 'موجودی'),
            'stock-top-sellers-chart' => $this->hBarOptions($data['topSellers'], '#0ea5e9', 'تنوع'),
            default => [],
        };
    }

    private function chartPayload(): array
    {
        return [
            'stock-owner-chart' => $this->chartOption('stock-owner-chart'),
            'stock-status-chart' => $this->chartOption('stock-status-chart'),
            'stock-top-products-chart' => $this->chartOption('stock-top-products-chart'),
            'stock-top-sellers-chart' => $this->chartOption('stock-top-sellers-chart'),
        ];
    }

    private function donutOptions(array $data, array $colors): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => $colors,
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'item',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => [
                'bottom' => 0,
                'icon' => 'circle',
                'itemWidth' => 9,
                'itemHeight' => 9,
                'itemGap' => 14,
                'textStyle' => ['color' => '#8b93a1', 'fontSize' => 11],
            ],
            'series' => [[
                'type' => 'pie',
                'radius' => ['52%', '76%'],
                'center' => ['50%', '42%'],
                'avoidLabelOverlap' => true,
                'itemStyle' => ['borderRadius' => 7],
                'label' => ['show' => false],
                'emphasis' => ['label' => ['show' => false], 'scaleSize' => 4],
                'data' => $data,
            ]],
        ];
    }

    private function hBarOptions(array $rows, string $color, string $unit): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => [$color],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'item',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 6, 'right' => 34, 'bottom' => 6, 'left' => 6, 'containLabel' => true],
            'xAxis' => [
                'type' => 'value',
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'yAxis' => [
                'type' => 'category',
                'data' => array_column($rows, 'name'),
                'inverse' => true,
                'axisLine' => ['show' => false],
                'axisTick' => ['show' => false],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'width' => 130, 'overflow' => 'truncate'],
            ],
            'series' => [[
                'type' => 'bar',
                'barMaxWidth' => 13,
                'itemStyle' => ['borderRadius' => [0, 7, 7, 0]],
                'label' => [
                    'show' => true,
                    'position' => 'right',
                    'color' => '#8b93a1',
                    'fontSize' => 10,
                    'formatter' => 'fn:faSeriesLabel',
                ],
                'data' => array_column($rows, 'value'),
            ]],
        ];
    }

    /* ---------------- داده‌های فیلترها ---------------- */

    #[Computed]
    public function sellers()
    {
        return Seller::query()->with('seller_info')->orderByDesc('id')->get()
            ->map(fn ($seller) => (object) [
                'id' => $seller->id,
                'name' => $seller->seller_info?->business_name
                    ?: trim(($seller->seller_info?->first_name ?? '').' '.($seller->seller_info?->last_name ?? ''))
                    ?: ('فروشنده #'.$seller->id),
            ]);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('id')->get();
    }

    /* ---------------- داده‌های فیلتر مدرن دسته‌بندی ---------------- */

    #[Computed]
    public function categoryFilter(): array
    {
        $categories = Category::query()->detectLang()
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get();

        $byParent = $categories->groupBy(fn (Category $category) => $category->category_id);

        $buildTree = function (?int $parentId) use (&$buildTree, $byParent) {
            return ($byParent->get($parentId) ?? collect())
                ->map(function (Category $category) use ($buildTree) {
                    $category->childrenTree = $buildTree($category->id);

                    return $category;
                })
                ->values();
        };

        $flatJs = $categories->map(function (Category $category) {
            $path = $category->parents()
                ->reverse()
                ->pluck('title')
                ->push($category->title)
                ->implode(' / ');

            return [
                'id' => (int) $category->id,
                'title' => $category->title,
                'path' => $path,
            ];
        })->values()->all();

        $titles = $categories
            ->whereIn('id', array_map('intval', $this->categoryId))
            ->sortBy(fn (Category $category) => array_search($category->id, array_map('intval', $this->categoryId)))
            ->pluck('title', 'id')
            ->all();

        return [
            'tree' => $buildTree(null),
            'flatJs' => $flatJs,
            'titles' => $titles,
        ];
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.products.stock')
            ->layout('admin-panel::layout');
    }
}
