<?php

namespace App\Livewire\Admin\Statistics;

use App\Livewire\Admin\Statistics\Concerns\InteractsWithStatistics;
use App\Rules\CheckeJdate;
use App\Traits\OrderStatisticsTrait;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * آمار سفارش‌ها — پورت ۱:۱ StatisticsController@orders + OrderStatisticsTrait.
 * چهار تب ارزش/تعداد/کاربران/محصولات — مثل قدیمی هر تب داده‌ی خودش را جداگانه می‌گیرد.
 * تریت قدیمی مستقیماً use شده و متدها با همان مسیر کد قدیمی اجرا می‌شوند.
 */
#[Title('آمار سفارش‌ها')]
class OrdersPage extends Component
{
    use OrderStatisticsTrait;
    use InteractsWithStatistics;

    /* ---------------- فیلتر بازه (State در URL) ---------------- */

    #[Url(as: 'period', except: 'monthly')]
    public string $period = 'monthly';

    #[Url(as: 'from_date', except: '')]
    public string $from_date = '';

    #[Url(as: 'to_date', except: '')]
    public string $to_date = '';

    /* ---------------- تب فعال + داده‌های نمودار ---------------- */

    /** values | counts | users | products */
    public string $tab = 'values';

    public array $charts = [
        'values'   => [],
        'counts'   => [],
        'users'    => [],
        'products' => [],
    ];

    public array $metas = [
        'values'   => ['total' => '۰', 'avg' => '۰', 'total_success' => '۰', 'total_fail' => '۰'],
        'counts'   => ['total' => '۰', 'avg' => '۰', 'total_success' => '۰', 'total_fail' => '۰'],
        'users'    => ['total' => '۰', 'avg' => '۰', 'total_success' => '۰', 'total_fail' => '۰'],
        'products' => ['total' => '۰', 'avg' => '۰', 'total_success' => '۰', 'total_fail' => '۰'],
    ];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.orders') ?: abort(403);

        // پورت لاگ مشاهده صفحه (StatisticsController@orders)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action' => 'view_statistics_orders',
                'ip'     => request()->ip(),
            ])
            ->log("مدیر {$adminName} صفحه آمار سفارش‌ها را مشاهده کرد");

        $this->loadTab($this->tab);
    }

    /* ---------------- اعمال فیلترها ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('statistics.orders') ?: abort(403);

        if (! in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            return;
        }

        $this->period = $period;
        $this->from_date = '';
        $this->to_date = '';
        $this->resetValidation();

        $this->loadTab($this->tab);
    }

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.orders') ?: abort(403);

        $this->validate([
            'from_date' => ['nullable', new CheckeJdate()],
            'to_date'   => ['nullable', new CheckeJdate()],
        ]);

        $this->loadTab($this->tab);
    }

    public function loadTab(string $tab): void
    {
        auth('adminPanel')->user()?->can('statistics.orders') ?: abort(403);

        if (! in_array($tab, ['values', 'counts', 'users', 'products'])) {
            return;
        }

        $this->tab = $tab;

        $request = $this->statisticsRequest([
            'period'    => $this->period,
            'from_date' => $this->from_date !== '' ? $this->from_date : null,
            'to_date'   => $this->to_date !== '' ? $this->to_date : null,
        ]);

        // پورت دقیق متدهای تریت (هر نوع آمار همان متد قدیمی)
        $payload = match ($tab) {
            'counts'   => json_decode($this->orderCounts($request)->getContent(), true),
            'users'    => json_decode($this->orderUsers($request)->getContent(), true),
            'products' => json_decode($this->orderProducts($request)->getContent(), true),
            default    => json_decode($this->orderValues($request)->getContent(), true),
        };

        $this->charts[$tab] = array_values($payload['data'] ?? []);
        $this->metas[$tab]  = $payload['meta'] ?? $this->metas[$tab];

        $this->dispatch('chart-update', charts: [
            'stats-orders-' . $tab => $this->chartOptions($tab),
        ]);
    }

    /* ---------------- آپشن نمودار (ECharts) — سری موفق/ناموفق مثل تریت ---------------- */

    public function chartOptions(string $tab): array
    {
        $rows = $this->charts[$tab] ?? [];

        $money = $tab === 'values';

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color'     => ['#10b981', '#f43f5e'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger'        => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth'    => 0,
                'textStyle'      => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => [
                'data'          => ['موفق', 'ناموفق'],
                'top'           => 0,
                'textStyle'     => ['color' => '#8b93a1', 'fontSize' => 11],
                'itemWidth'     => 12,
                'itemHeight'    => 12,
            ],
            'grid' => ['top' => 44, 'right' => 20, 'bottom' => 30, 'left' => 16, 'containLabel' => true],
            'xAxis' => [
                'type'   => 'category',
                'data'   => array_column($rows, 'chart_category'),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                'type' => 'value',
                'name' => $money ? 'تومان' : 'تعداد',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'series' => [
                [
                    'name'     => 'موفق',
                    'type'     => 'bar',
                    'stack'    => 'orders',
                    'barMaxWidth' => 26,
                    'itemStyle' => ['borderRadius' => [0, 0, 0, 0]],
                    'data'     => array_map(fn ($item) => (int) ($item['success_orders'] ?? 0), $rows),
                ],
                [
                    'name'     => 'ناموفق',
                    'type'     => 'bar',
                    'stack'    => 'orders',
                    'barMaxWidth' => 26,
                    'itemStyle' => ['borderRadius' => [6, 6, 0, 0]],
                    'data'     => array_map(fn ($item) => (int) ($item['fail_orders'] ?? 0), $rows),
                ],
            ],
        ];
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.orders')
            ->layout('admin-panel::layout');
    }
}
