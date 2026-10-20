<?php

namespace App\Livewire\Admin\Content;

use App\Models\Search;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('لیست جستجوها')]
class SearchIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فیلترها (پورت filters قدیمی — State در URL) ---------------- */

    #[Url(as: 'search_type', except: '')]
    public string $filterType = '';

    #[Url(as: 'keyword', except: '')]
    public string $filterKeyword = '';

    /* ---------------- مودال جزئیات (پورت details قدیمی) ---------------- */

    public bool $showDetails = false;

    public string $detailsKeyword = '';

    public string $detailsType = '';

    public array $detailsRows = [];

    public int $totalSearches = 0;

    /* ---------------- حذف (پورت destroy / multipleDestroy قدیمی) ---------------- */

    public ?string $confirmingDelete = null; // کلید ترکیبی «keyword__type»

    public ?string $confirmingBulkDelete = null;

    public array $selected = [];

    public function mount(): void
    {
        // پورت middleware('can:searches') قدیمی
        auth('adminPanel')->user()?->can('searches') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['filterType', 'filterKeyword', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست گروه‌بندی‌شده (پورت ۱:۱ index قدیمی — قابل‌حمل SQLite/MySQL) ---------------- */

    #[Computed]
    public function rows()
    {
        return Search::query()
            ->select(
                'keyword',
                'search_type',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(searched_at) as last_searched'),
                DB::raw('MIN(searched_at) as first_searched'),
                // پورت AVG(CASE ...) قدیمی — quote ها تک‌کوتیشن تا روی SQLite/MySQL هر دو اجرا شود
                DB::raw("AVG(CASE
                    WHEN search_type = 'products' THEN products_count
                    WHEN search_type = 'posts' THEN posts_count
                    ELSE 0 END) as avg_results")
            )
            ->when($this->filterType, function ($query) {
                $query->where('search_type', $this->filterType);
            })
            ->when($this->filterKeyword !== '', function ($query) {
                $query->where('keyword', 'like', '%' . $this->filterKeyword . '%');
            })
            ->groupBy('keyword', 'search_type')
            ->orderBy('search_count', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- ردیف‌های صفحه جاری (برای ارسال پارامتر امن به اکشن‌ها) ---------------- */

    #[Computed]
    public function pageItems()
    {
        return $this->rows->getCollection()->values();
    }

    public function applyFilters(): void
    {
        $this->reset(['filterType', 'filterKeyword']);
        $this->resetPage();
    }

    /* ---------------- جزئیات (پورت ۱:۱ details قدیمی) ---------------- */
    // فاز ۱۶: نام اکشن از showDetails به openDetails تغییر کرد — چون پراپرتی
    // هم‌نام $showDetails پرچم مودال است و در پروکسی Livewire پراپرتی بر متد
    // اولویت دارد (w.showDetails «not a function») و دکمه جزئیات هیچ کاری نمی‌کرد.
    public function openDetails(int $index): void
    {
        auth('adminPanel')->user()?->can('searches') ?: abort(403);

        $item = $this->pageItems[$index] ?? null;

        if (! $item) {
            return;
        }

        $this->detailsKeyword = $item->keyword;
        $this->detailsType = $item->search_type;

        // پورت: Search::with('user')->where('keyword')->where('search_type')->orderBy('searched_at', 'desc')->get()
        $searches = Search::with('user')
            ->where('keyword', $this->detailsKeyword)
            ->where('search_type', $this->detailsType)
            ->orderBy('searched_at', 'desc')
            ->get();

        $this->detailsRows = $searches->map(fn ($search) => [
            'name'          => $search->user ? ($search->user->fullname ?: ($search->user->username ?? '')) : 'کاربر مهمان',
            'email'         => $search->user?->email ?? '-',
            'ip'            => $search->ip_address,
            'searched_at'   => self::jdate($search->searched_at, 'd F Y | H:i:s'),
            'results'       => $search->search_type === 'products'
                ? 'محصولات: ' . number_format($search->products_count) . ' | دسته‌ها: ' . number_format($search->categories_count) . ' | برندها: ' . number_format($search->brands_count)
                : 'پست‌ها: ' . number_format($search->posts_count),
        ])->all();

        $this->totalSearches = $searches->count();
        $this->showDetails = true;
    }

    /** فاز ۱۹: جزئیات مستقیم با کلیدواژه+نوع (کارت‌های پرجستجوترین‌ها) */
    public function openDetailsByKey(string $keyword, string $type): void
    {
        auth('adminPanel')->user()?->can('searches') ?: abort(403);

        $this->detailsKeyword = $keyword;
        $this->detailsType = $type;

        $searches = Search::with('user')
            ->where('keyword', $keyword)
            ->where('search_type', $type)
            ->orderBy('searched_at', 'desc')
            ->get();

        $this->detailsRows = $searches->map(fn ($search) => [
            'name'          => $search->user ? ($search->user->fullname ?: ($search->user->username ?? '')) : 'کاربر مهمان',
            'email'         => $search->user?->email ?? '-',
            'ip'            => $search->ip_address,
            'searched_at'   => self::jdate($search->searched_at, 'd F Y | H:i:s'),
            'results'       => $search->search_type === 'products'
                ? 'محصولات: ' . number_format($search->products_count) . ' | دسته‌ها: ' . number_format($search->categories_count) . ' | برندها: ' . number_format($search->brands_count)
                : 'پست‌ها: ' . number_format($search->posts_count),
        ])->all();

        $this->totalSearches = $searches->count();
        $this->showDetails = true;
    }

    /* ---------------- حذف یک کلیدواژه (پورت ۱:۱ destroy قدیمی — همه ردیف‌های keyword+type) ---------------- */

    public function confirmDelete(int $index): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        $item = $this->pageItems[$index] ?? null;

        if ($item) {
            $this->confirmingDelete = $item->keyword . '__' . $item->search_type;
        }
    }

    public function destroy(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        if (! $this->confirmingDelete) {
            return;
        }

        list($keyword, $type) = explode('__', $this->confirmingDelete);

        // پورت: حذف تک‌تک ردیف‌ها (رویدادهای مدل فعال می‌مانند مثل قدیمی)
        $query = Search::where('keyword', $keyword);

        if ($type) {
            $query->where('search_type', $type);
        }

        $deletedCount = 0;

        foreach ($query->get() as $search) {
            $search->delete();
            $deletedCount++;
        }

        $this->confirmingDelete = null;
        unset($this->rows, $this->pageItems);

        $this->dispatch('toast', message: "$deletedCount رکورد جستجو حذف شد", type: 'success');
    }

    /* ---------------- حذف گروهی (پورت ۱:۱ multipleDestroy قدیمی) ---------------- */

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        if (count($this->selected)) {
            $this->confirmingBulkDelete = 'open';
        }
    }

    public function multipleDestroy(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        // پورت validate قدیمی: ids => required|array
        $this->validate([
            'selected' => 'required|array',
        ], [], ['selected' => 'شناسه‌ها']);

        $deletedCount = 0;

        foreach ($this->selected as $item) {
            // کلیدهای ترکیبی «keyword__type» (پورت دقیق explode قدیمی)
            list($keyword, $type) = explode('__', $item);

            $search = Search::where('keyword', $keyword)
                ->when($type, function ($q) use ($type) {
                    return $q->where('search_type', $type);
                })
                ->first();

            if ($search) {
                $search->delete();
                $deletedCount++;
            }
        }

        $this->selected = [];
        $this->confirmingBulkDelete = null;
        unset($this->rows, $this->pageItems);

        $this->dispatch('toast', message: "$deletedCount رکورد جستجو حذف شد", type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function toggleSelectAll(): void
    {
        $keys = $this->pageItems
            ->map(fn ($item) => $item->keyword . '__' . $item->search_type)
            ->all();

        if (count(array_diff($keys, $this->selected)) === 0 && count($this->selected) > 0) {
            $this->selected = array_values(array_diff($this->selected, $keys));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $keys)));
        }
    }

    /* ------------------------------------------------------------------
     | فاز ۱۹ — آمار کامل صفحه‌ی جستجوها (کارت‌ها + نمودارها)
     ------------------------------------------------------------------ */

    /** آمار کلی برای کارت‌های بالای صفحه */
    #[Computed]
    public function stats(): array
    {
        $base = Search::query();

        return [
            'total'    => (clone $base)->count(),
            'keywords' => (clone $base)->distinct()->count('keyword'),
            'products' => (clone $base)->where('search_type', 'products')->count(),
            'posts'    => (clone $base)->where('search_type', 'posts')->count(),
            'visitors' => (clone $base)->whereNotNull('ip_address')->where('ip_address', '!=', '')->distinct()->count('ip_address'),
            'today'    => (clone $base)->whereDate('searched_at', today())->count(),
            'week'     => (clone $base)->where('searched_at', '>=', now()->subDays(7)->startOfDay())->count(),
        ];
    }

    /** پرجستجوترین کلیدواژه‌ها (برای کارت‌های رتبه‌دار) */
    #[Computed]
    public function topKeywords()
    {
        return Search::query()
            ->select('keyword', 'search_type', DB::raw('COUNT(*) as search_count'))
            ->groupBy('keyword', 'search_type')
            ->orderByDesc('search_count')
            ->limit(6)
            ->get();
    }

    /** سری روزانه (۱۴ روز اخیر) برای نمودار روند */
    #[Computed]
    public function dailySeries(): array
    {
        $rows = Search::query()
            ->where('searched_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(searched_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $values = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $labels[] = \Morilog\Jalali\Jalalian::fromDateTime($day)->format('m/d');
            $values[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** نمودار میله‌ای افقی — پرجستجوترین کلیدواژه‌ها */
    #[Computed]
    public function keywordsChartOptions(): array
    {
        $top = Search::query()
            ->select('keyword', DB::raw('COUNT(*) as c'))
            ->groupBy('keyword')
            ->orderByDesc('c')
            ->limit(7)
            ->get();

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#10b981'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => ['type' => 'shadow'],
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 8, 'right' => 24, 'bottom' => 8, 'left' => 8, 'containLabel' => true],
            'xAxis' => [
                'type' => 'value',
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'yAxis' => [
                'type' => 'category',
                'inverse' => true,
                'data' => $top->pluck('keyword')->all(),
                'axisLine' => ['show' => false],
                'axisTick' => ['show' => false],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 11, 'width' => 90, 'overflow' => 'truncate'],
            ],
            'series' => [[
                'type' => 'bar',
                'barWidth' => 14,
                'itemStyle' => ['borderRadius' => [0, 8, 8, 0]],
                'label' => ['show' => true, 'position' => 'right', 'color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faSeriesLabel'],
                'data' => $top->pluck('c')->map(fn ($v) => (int) $v)->all(),
            ]],
        ];
    }

    /** نمودار دونات — سهم نوع جستجوها */
    #[Computed]
    public function typeChartOptions(): array
    {
        $products = (int) $this->stats['products'];
        $posts = (int) $this->stats['posts'];

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#10b981', '#0ea5e9'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'item',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
            ],
            'legend' => [
                'bottom' => 0,
                'icon' => 'circle',
                'itemWidth' => 8,
                'itemHeight' => 8,
                'textStyle' => ['color' => '#8b93a1', 'fontSize' => 11],
            ],
            'series' => [[
                'type' => 'pie',
                'radius' => ['52%', '74%'],
                'center' => ['50%', '44%'],
                'avoidLabelOverlap' => true,
                'itemStyle' => ['borderRadius' => 8, 'borderWidth' => 2, 'borderColor' => 'transparent'],
                'label' => ['show' => false],
                'data' => [
                    ['name' => 'محصولات', 'value' => $products],
                    ['name' => 'پست‌ها', 'value' => $posts],
                ],
            ]],
        ];
    }

    /** نمودار روند روزانه */
    #[Computed]
    public function dailyChartOptions(): array
    {
        $series = $this->dailySeries;

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
            'grid' => ['top' => 20, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $series['labels'],
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'interval' => 2],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                'type' => 'value',
                'minInterval' => 1,
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [[
                'type' => 'line',
                'smooth' => true,
                'symbolSize' => 6,
                'showSymbol' => false,
                'lineStyle' => ['width' => 2.5],
                'areaStyle' => [
                    'color' => [
                        'type' => 'linear', 'x' => 0, 'y' => 0, 'x2' => 0, 'y2' => 1,
                        'colorStops' => [
                            ['offset' => 0, 'color' => 'rgba(14,165,233,.28)'],
                            ['offset' => 1, 'color' => 'rgba(14,165,233,0)'],
                        ],
                    ],
                ],
                'data' => $series['values'],
            ]],
        ];
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.content.searches')
            ->layout('admin-panel::layout');
    }
}
