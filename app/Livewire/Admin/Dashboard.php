<?php

namespace App\Livewire\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerInfo;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('داشبورد')]
class Dashboard extends Component
{
    public array $stats = [];

    public string $todayJalali = '';

    public array $recentOrders = [];

    public array $salesChart = [];

    public array $statusChart = [];

    public array $topProducts = [];

    public function mount()
    {
        $this->todayJalali = Jalalian::now()->format('l، j F Y');

        // آمار پایه با کش ۶۰ ثانیه — سبک و سریع
        $this->stats = Cache::remember('np-dashboard-stats', 60, function () {
            return [
                'users' => User::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'sellers' => SellerInfo::count(),
                'ordersToday' => Order::whereDate('created_at', today())->count(),
                'usersToday' => User::whereDate('created_at', today())->count(),
                'paidOrders' => Order::where('status', 'paid')->count(),
                'admins' => Admin::count(),
            ];
        });

        $this->recentOrders = Order::query()
            ->latest('id')
            ->take(7)
            ->get(['id', 'user_id', 'name', 'mobile', 'status', 'price', 'created_at'])
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'status' => $o->status,
                'statusLabel' => $this->orderStatusLabel($o->status),
                'price' => $o->price,
                'date' => Jalalian::fromDateTime($o->created_at)->format('Y/m/d'),
            ])
            ->toArray();

        // داده‌های نمودارها با کش ۵ دقیقه
        $this->salesChart = $this->buildSalesChart();
        $this->statusChart = $this->buildStatusChart();
        $this->topProducts = $this->buildTopProducts();
    }

    /**
     * نمودار فروش ۳۰ روز اخیر (تعداد سفارش + مبلغ پرداخت‌شده)
     */
    protected function buildSalesChart(): array
    {
        return Cache::remember('np-dashboard-sales-chart', 300, function () {
            $days = 30;

            $orders = Order::query()
                ->where('created_at', '>=', now()->subDays($days)->startOfDay())
                ->get(['id', 'price', 'status', 'created_at']);

            $labels = [];
            $counts = [];
            $revenues = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $labels[] = Jalalian::fromDateTime($date)->format('m/d');

                $dayOrders = $orders->filter(fn ($o) => $o->created_at->isSameDay($date));
                $counts[] = $dayOrders->count();
                $revenues[] = (int) $dayOrders->where('status', 'paid')->sum('price');
            }

            return [
                'labels' => $labels,
                'orders' => $counts,
                'revenues' => $revenues,
            ];
        });
    }

    /**
     * نمودار دایره‌ای وضعیت سفارشات
     */
    protected function buildStatusChart(): array
    {
        return Cache::remember('np-dashboard-status-chart', 300, function () {
            $rows = Order::query()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $labels = [];
            $values = [];

            foreach ($rows as $status => $count) {
                $labels[] = $this->orderStatusLabel($status);
                $values[] = (int) $count;
            }

            return [
                'labels' => $labels,
                'values' => $values,
            ];
        });
    }

    /**
     * پرفروش‌ترین محصولات (۳۰ روز اخیر)
     */
    protected function buildTopProducts(): array
    {
        return Cache::remember('np-dashboard-top-products', 300, function () {
            $rows = \App\Models\OrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
                ->selectRaw('product_id, sum(quantity) as sold')
                ->whereNotNull('product_id')
                ->with('product:id,title')
                ->groupBy('product_id')
                ->orderByDesc('sold')
                ->limit(5)
                ->get();

            return $rows->map(fn ($row) => [
                'title' => $row->product?->title ?? 'محصول حذف‌شده',
                'sold' => (int) $row->sold,
            ])->values()->toArray();
        });
    }

    protected function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'canceled' => 'لغو شده',
            'pending_down_payment' => 'پیش‌پرداخت',
            'active' => 'فعال',
            default => $status,
        };
    }

    /* ---------- فرمترهای نمایش (اعداد فارسی) ---------- */

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public static function number($value): string
    {
        return self::fa(number_format((float) $value));
    }

    public static function toman($value): string
    {
        return self::number($value) . ' تومان';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.dashboard', [
            'salesOptions' => $this->salesChartOptions(),
            'statusOptions' => $this->statusChartOptions(),
            'topProductsOptions' => $this->topProductsChartOptions(),
        ])->layout('admin-panel::layout');
    }

    /**
     * آپشن‌های نمودار فروش (ECharts) — «fn:faNum» در سمت JS به فرمت‌کننده ارقام فارسی تبدیل می‌شود
     */
    protected function salesChartOptions(): array
    {
        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#10b981', '#0ea5e9'],
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
                'data' => $this->salesChart['labels'] ?? [],
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'interval' => 4],
                'axisTick' => ['show' => false],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'تومان',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                    'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
                ],
                [
                    'type' => 'value',
                    'name' => 'سفارش',
                    'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                    'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                    'splitLine' => ['show' => false],
                ],
            ],
            'series' => [
                [
                    'name' => 'فروش',
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'none',
                    'lineStyle' => ['width' => 2.5],
                    'areaStyle' => ['opacity' => 0.12],
                    'data' => $this->salesChart['revenues'] ?? [],
                    'yAxisIndex' => 0,
                ],
                [
                    'name' => 'سفارش',
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'none',
                    'lineStyle' => ['width' => 2, 'type' => 'dashed'],
                    'data' => $this->salesChart['orders'] ?? [],
                    'yAxisIndex' => 1,
                ],
            ],
        ];
    }

    protected function statusChartOptions(): array
    {
        $palette = ['#10b981', '#f59e0b', '#f43f5e', '#0ea5e9', '#8b5cf6', '#64748b'];

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => $palette,
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'item',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'legend' => [
                'bottom' => 6,
                'textStyle' => ['color' => '#8b93a1', 'fontSize' => 11],
                'itemWidth' => 10,
                'itemHeight' => 10,
                'icon' => 'circle',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'radius' => ['52%', '78%'],
                    'center' => ['50%', '44%'],
                    'avoidLabelOverlap' => true,
                    'itemStyle' => ['borderRadius' => 8, 'borderWidth' => 2, 'borderColor' => 'transparent'],
                    'label' => ['show' => false],
                    'emphasis' => [
                        'scale' => true,
                        'itemStyle' => ['shadowBlur' => 12, 'shadowColor' => 'rgba(16,185,129,.35)'],
                    ],
                    'data' => collect($this->statusChart['labels'] ?? [])
                        ->map(fn ($label, $i) => [
                            'name' => $label,
                            'value' => $this->statusChart['values'][$i] ?? 0,
                        ])->values()->toArray(),
                ],
            ],
        ];
    }

    protected function topProductsChartOptions(): array
    {
        $rows = $this->topProducts;

        $labels = collect($rows)->map(fn ($r) => \Illuminate\Support\Str::limit($r['title'], 18, '…'))->values()->toArray();
        $values = collect($rows)->pluck('sold')->values()->toArray();

        return [
            'textStyle' => ['fontFamily' => 'Vazirmatn, Tahoma, sans-serif'],
            'color' => ['#f59e0b'],
            'animationDuration' => 700,
            'tooltip' => [
                'trigger' => 'axis',
                'backgroundColor' => 'rgba(23, 30, 42, 0.92)',
                'borderWidth' => 0,
                'textStyle' => ['color' => '#fff', 'fontSize' => 12],
                'valueFormatter' => 'fn:faNum',
            ],
            'grid' => ['top' => 10, 'right' => 24, 'bottom' => 10, 'left' => 8, 'containLabel' => true],
            'xAxis' => [
                'type' => 'value',
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'yAxis' => [
                'type' => 'category',
                'inverse' => true,
                'data' => $labels,
                'axisLine' => ['show' => false],
                'axisTick' => ['show' => false],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 11],
            ],
            'series' => [
                [
                    'type' => 'bar',
                    'barWidth' => 14,
                    'itemStyle' => ['borderRadius' => [0, 7, 7, 0]],
                    'label' => ['show' => true, 'position' => 'right', 'color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNum'],
                    'data' => $values,
                ],
            ],
        ];
    }
}
