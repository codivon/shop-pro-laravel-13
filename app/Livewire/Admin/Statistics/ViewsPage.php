<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Rules\CheckeJdate;
use App\Traits\ViewStatisticsTrait;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * آمار بازدیدها — پورت ۱:۱ StatisticsController@views + ViewStatisticsTrait.
 * تریت قدیمی مستقیماً use شده و متدهای viewCounts/viewerCounts با همان
 * مسیر کد قدیمی (validateDates + getPeriodData) اجرا می‌شوند.
 */
#[Title('آمار بازدیدها')]
class ViewsPage extends Component
{
    use ViewStatisticsTrait;
    use InteractsWithStatistics;

    /* ---------------- فیلتر بازه (State در URL) ---------------- */

    #[Url(as: 'period', except: 'monthly')]
    public string $period = 'monthly';

    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    /* ---------------- داده‌های نمودار + متریک‌ها ---------------- */

    /** ردیف‌های نمودار بازدیدها: [['chart_category' => ..., 'total' => ...]] */
    public array $viewData = [];

    /** ردیف‌های نمودار بازدیدکنندگان */
    public array $viewerData = [];

    public array $viewsMeta = ['total' => '۰', 'avg' => '۰'];

    public array $viewersMeta = ['total' => '۰', 'avg' => '۰'];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.views') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@views)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_statistics_views',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} صفحه آمار بازدیدها را مشاهده کرد");

        $this->loadStatistics();
    }

    /* ---------------- اعمال فیلترها ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('statistics.views') ?: abort(403);

        if (! in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            return;
        }

        $this->period = $period;
        $this->from_date = '';
        $this->to_date = '';
        $this->resetValidation();

        $this->loadStatistics();
        $this->dispatchCharts();
    }

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.views') ?: abort(403);

        $this->validate([
            'from_date' => ['nullable', new CheckeJdate()],
            'to_date'   => ['nullable', new CheckeJdate()],
        ]);

        $this->loadStatistics();
        $this->dispatchCharts();
    }

    /* ---------------- محاسبه (پورت viewCounts/viewerCounts تریت) ---------------- */

    protected function loadStatistics(): void
    {
        $request = $this->statisticsRequest([
            'period'    => $this->period,
            'from_date' => $this->from_date !== '' ? $this->from_date : null,
            'to_date'   => $this->to_date !== '' ? $this->to_date : null,
        ]);

        $viewsPayload   = json_decode($this->viewCounts($request)->getContent(), true);
        $viewersPayload = json_decode($this->viewerCounts($request)->getContent(), true);

        $this->viewData    = array_values($viewsPayload['data'] ?? []);
        $this->viewsMeta   = $viewsPayload['meta'] ?? ['total' => '۰', 'avg' => '۰'];
        $this->viewerData  = array_values($viewersPayload['data'] ?? []);
        $this->viewersMeta = $viewersPayload['meta'] ?? ['total' => '۰', 'avg' => '۰'];
    }

    protected function dispatchCharts(): void
    {
        $this->dispatch('chart-update', charts: [
            'stats-views'  => $this->viewsChartOptions(),
            'stats-viewers' => $this->viewersChartOptions(),
        ]);
    }

    /* ---------------- آپشن‌های نمودار (ECharts) ---------------- */

    protected function viewsChartOptions(): array
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
                'boundaryGap' => false,
                'data'   => array_column($this->viewData, 'chart_category'),
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
                    'name'     => 'تعداد بازدید',
                    'type'     => 'line',
                    'smooth'   => true,
                    'showSymbol' => false,
                    'areaStyle' => ['opacity' => 0.08],
                    'lineStyle' => ['width' => 2.5],
                    'data'     => array_map(fn ($item) => (int) ($item['total'] ?? 0), $this->viewData),
                ],
            ],
        ];
    }

    protected function viewersChartOptions(): array
    {
        $options = $this->viewsChartOptions();
        $options['color'] = ['#6366f1'];
        $options['series'][0]['name'] = 'تعداد بازدیدکننده';
        $options['series'][0]['data'] = array_map(fn ($item) => (int) ($item['total'] ?? 0), $this->viewerData);
        $options['xAxis']['data'] = array_column($this->viewerData, 'chart_category');
        $options['yAxis']['name'] = 'بازدیدکننده';

        return $options;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.views')
            ->layout('admin-panel::layout');
    }
}
