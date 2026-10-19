<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Models\Viewer;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * بازدیدکنندگان — پورت ۱:۱ StatisticsController@viewers.
 * یکتاسازی (هر کاربر یک‌بار، مهمان بر اساس IP) روی کالکشن انجام می‌شود
 * و صفحه‌بندی دستی LengthAwarePaginator مثل قدیمی.
 */
#[Title('بازدیدکنندگان')]
class ViewersPage extends Component
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

    public array $stats = ['unique' => 0, 'views' => 0, 'users' => 0, 'guests' => 0];

    public array $chart = [];

    public string $rangeFrom = '';

    public string $rangeTo = '';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.viewers') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@viewers)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_viewers_list',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} لیست بازدیدکنندگان را مشاهده کرد");

        $this->visitors; // محاسبه اولیه آمار و نمودار
    }

    /* ---------------- اعمال فیلترها ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('statistics.viewers') ?: abort(403);

        if (! in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            return;
        }

        $this->period = $period;
        $this->from_date = '';
        $this->to_date = '';
        $this->resetValidation();
        $this->resetPage();

        $this->visitors; // محاسبه مجدد
        $this->dispatchCharts();
    }

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.viewers') ?: abort(403);

        // تاریخ نامعتبر → resolveStatisticsPeriod به‌صورت خودکار به روزانه برمی‌گردد (پورت قدیمی)
        $this->resetPage();

        $this->visitors; // محاسبه مجدد
        $this->dispatchCharts();
    }

    /* ---------------- محاسبه (پورت viewers قدیمی) ---------------- */

    #[Computed]
    public function periodRange(): array
    {
        return $this->resolveStatisticsPeriod($this->period, $this->from_date ?: null, $this->to_date ?: null);
    }

    #[Computed]
    public function visitors(): LengthAwarePaginator
    {
        [$period, $from, $to] = $this->periodRange;

        // ---------- داده‌ها ----------
        $rows = Viewer::query()
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        // یکتاسازی: هر کاربر یک‌بار، مهمان‌ها بر اساس IP (پورت قدیمی)
        $unique = $rows->unique(fn ($v) => $v->user_id ?: 'ip:' . $v->ip)->values();

        // ---------- آمار ----------
        $this->stats = [
            'unique' => $unique->count(),
            'views'  => $rows->count(),
            'users'  => $unique->whereNotNull('user_id')->count(),
            'guests' => $unique->whereNull('user_id')->count(),
        ];

        // ---------- نمودار ----------
        $this->chart = $this->buildViewersChart($rows, $from, $to);

        $this->rangeFrom = substr((string) jdate($from), 0, 10);
        $this->rangeTo   = substr((string) jdate($to), 0, 10);

        // ---------- صفحه‌بندی دستی (چون unique روی کالکشن است) ----------
        $page  = $this->getPage();
        $items = $unique->slice(($page - 1) * $this->perPage, $this->perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $unique->count(),
            $this->perPage,
            $page,
        );
    }

    /* ---------------- آپشن نمودار (ECharts) ---------------- */

    public function viewersChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color'     => ['#6366f1'],
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
                'name' => 'بازدیدکننده',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [
                [
                    'name'     => 'بازدیدکننده',
                    'type'     => 'bar',
                    'barMaxWidth' => 26,
                    'itemStyle' => ['borderRadius' => [6, 6, 0, 0]],
                    'data'     => array_map(fn ($item) => (int) $item['total'], $this->chart),
                ],
            ],
        ];
    }

    protected function dispatchCharts(): void
    {
        $this->dispatch('chart-update', charts: [
            'stats-viewers-page' => $this->viewersChartOptions(),
        ]);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.viewers')
            ->layout('admin-panel::layout');
    }
}
