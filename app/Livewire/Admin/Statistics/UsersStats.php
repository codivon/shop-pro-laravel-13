<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Rules\CheckeJdate;
use App\Traits\UserStatisticsTrait;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * آمار کاربران — پورت ۱:۱ StatisticsController@users + UserStatisticsTrait.
 * تریت قدیمی مستقیماً use شده و userCounts با همان مسیر کد قدیمی اجرا می‌شود.
 */
#[Title('آمار کاربران')]
class UsersStats extends Component
{
    use UserStatisticsTrait;
    use InteractsWithStatistics;

    /* ---------------- فیلتر بازه (State در URL) ---------------- */

    #[Url(as: 'period', except: 'monthly')]
    public string $period = 'monthly';

    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    /* ---------------- داده‌های نمودار + متریک‌ها ---------------- */

    /** ردیف‌های نمودار: [['chart_category' => ..., 'users' => ...]] */
    public array $userData = [];

    public array $usersMeta = ['total' => '۰', 'avg' => '۰'];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.users') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@users)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_statistics_users',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} صفحه آمار کاربران را مشاهده کرد");

        $this->loadStatistics();
    }

    /* ---------------- اعمال فیلترها ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('statistics.users') ?: abort(403);

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
        auth('adminPanel')->user()?->can('statistics.users') ?: abort(403);

        $this->validate([
            'from_date' => ['nullable', new CheckeJdate()],
            'to_date'   => ['nullable', new CheckeJdate()],
        ]);

        $this->loadStatistics();
        $this->dispatchCharts();
    }

    /* ---------------- محاسبه (پورت userCounts تریت) ---------------- */

    protected function loadStatistics(): void
    {
        $request = $this->statisticsRequest([
            'period'    => $this->period,
            'from_date' => $this->from_date !== '' ? $this->from_date : null,
            'to_date'   => $this->to_date !== '' ? $this->to_date : null,
        ]);

        $payload = json_decode($this->userCounts($request)->getContent(), true);

        $this->userData   = array_values($payload['data'] ?? []);
        $this->usersMeta  = $payload['meta'] ?? ['total' => '۰', 'avg' => '۰'];
    }

    protected function dispatchCharts(): void
    {
        $this->dispatch('chart-update', charts: [
            'stats-users' => $this->usersChartOptions(),
        ]);
    }

    /* ---------------- آپشن نمودار (ECharts) ---------------- */

    protected function usersChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color'     => ['#0ea5e9'],
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
                'data'   => array_column($this->userData, 'chart_category'),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                'type' => 'value',
                'name' => 'کاربر',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [
                [
                    'name'     => 'کاربران ثبت‌نام‌شده',
                    'type'     => 'line',
                    'smooth'   => true,
                    'showSymbol' => false,
                    'areaStyle' => ['opacity' => 0.08],
                    'lineStyle' => ['width' => 2.5],
                    'data'     => array_map(fn ($item) => (int) ($item['users'] ?? 0), $this->userData),
                ],
            ],
        ];
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.users')
            ->layout('admin-panel::layout');
    }
}
