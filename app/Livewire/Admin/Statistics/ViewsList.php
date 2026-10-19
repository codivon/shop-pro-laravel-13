<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Models\Viewer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لیست بازدیدها — پورت ۱:۱ StatisticsController@viewsList.
 * شرط سطح دسترسی non-creator روی کوئری داخل closure مثل قدیمی حفظ شده است.
 */
#[Title('لیست بازدیدها')]
class ViewsList extends Component
{
    use WithPagination;
    use InteractsWithStatistics;

    /* ---------------- فیلتر بازه (State در URL) ---------------- */

    #[Url(as: 'period', except: 'daily')]
    public string $period = 'daily';

    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    public int $perPage = 20;

    /* ---------------- داده‌ها ---------------- */

    public array $stats = ['views' => 0, 'unique' => 0, 'users' => 0, 'guests' => 0];

    public array $chart = [];

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.viewsList') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@viewsList)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_views_list',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} لیست بازدیدهای صفحه را مشاهده کرد");

        $this->views; // محاسبه اولیه آمار و نمودار
    }

    /* ---------------- اعمال فیلترها ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('statistics.viewsList') ?: abort(403);

        if (! in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'custom'])) {
            return;
        }

        $this->period = $period;
        if ($period !== 'custom') {
            $this->from_date = '';
            $this->to_date = '';
        }
        $this->resetValidation();
        $this->resetPage();

        $this->views; // محاسبه مجدد
        $this->dispatchCharts();
    }

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.viewsList') ?: abort(403);

        // بازه نامعتبر → resolveStatisticsPeriod به‌صورت خودکار به روزانه برمی‌گردد (پورت قدیمی)
        $this->resetPage();

        $this->views; // محاسبه مجدد
        $this->dispatchCharts();
    }

    /* ---------------- محاسبه (پورت viewsList قدیمی) ---------------- */

    #[Computed]
    public function views()
    {
        [$period, $from, $to] = $this->resolveStatisticsPeriod($this->period, $this->from_date ?: null, $this->to_date ?: null);

        // ---------- کوئری اصلی ----------
        $query = Viewer::query()->whereBetween('created_at', [$from, $to]);

        // شرط سطح دسترسی — حتماً داخل closure (مهم!)
        if (auth('adminPanel')->user()->level != 'creator') {
            $query->where(function ($q) {
                $q->whereNull('user_id')->orWhere(function ($q1) {
                    $q1->whereHas('user', fn ($q2) => $q2->where('level', '!=', 'creator'));
                });
            });
        }

        // ---------- آمار (یک کوئری) ----------
        $isSqlite = $query->getQuery()->getConnection()->getDriverName() === 'sqlite';

        // CONCAT روی SQLite وجود ندارد؛ معادل || استفاده می‌شود (همان semantics)
        $uniqueExpr = $isSqlite
            ? "COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN ('u:' || user_id) ELSE ('ip:' || ip) END)"
            : "COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN CONCAT('u:', user_id) ELSE CONCAT('ip:', ip) END)";

        $statsRow = (clone $query)->selectRaw(
            "COUNT(*) AS total_views,
            {$uniqueExpr} AS unique_visitors,
            COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id END) AS members,
            COUNT(DISTINCT CASE WHEN user_id IS NULL THEN ip END) AS guests"
        )->first();

        $this->stats = [
            'views'  => (int) ($statsRow->total_views ?? 0),
            'unique' => (int) ($statsRow->unique_visitors ?? 0),
            'users'  => (int) ($statsRow->members ?? 0),
            'guests' => (int) ($statsRow->guests ?? 0),
        ];

        // ---------- نمودار (گروه‌بندی سمت دیتابیس — پورت buildViewsChart) ----------
        $this->chart = $this->buildViewsChart($query, $from, $to);

        $this->rangeFrom = substr((string) jdate($from), 0, 10);
        $this->rangeTo   = substr((string) jdate($to), 0, 10);

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- آپشن نمودار (ECharts) ---------------- */

    public function viewsChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color'     => ['#10b981'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger'        => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth'    => 0,
                'textStyle'      => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 24, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type'   => 'category',
                'data'   => array_column($this->chart, 'label'),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                'type' => 'value',
                'name' => 'بازدید',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [
                [
                    'name'     => 'بازدید',
                    'type'     => 'line',
                    'smooth'   => true,
                    'showSymbol' => false,
                    'areaStyle' => ['opacity' => 0.08],
                    'lineStyle' => ['width' => 2.5],
                    'data'     => array_map(fn ($item) => (int) $item['total'], $this->chart),
                ],
            ],
        ];
    }

    protected function dispatchCharts(): void
    {
        $this->dispatch('chart-update', charts: [
            'stats-views-list' => $this->viewsChartOptions(),
        ]);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.views-list')
            ->layout('admin-panel::layout');
    }
}
