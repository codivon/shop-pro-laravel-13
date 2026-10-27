<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\OrderItem;
use App\Models\Seller;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * فاز ۱۰۶-۷ — لیدربورد مقایسهٔ فروشندگان (پنل جدید — ECharts).
 *
 * رتبه‌بندی عملکرد فروشندگان با فیلتر دوره (۳۰/۹۰ روز/همهٔ زمان‌ها):
 * درآمد، سفارش‌های موفق، اقلام فروخته‌شده و میانگین هر سفارش.
 *
 * مبنای واحد درآمد: SUM(price × quantity) آیتم‌های سفارش‌های paid با
 * order_items.seller_id — همان مبنای کارت داشبورد فروشنده (درس فاز ۱۰۶ الف)
 * و گزارش فروش (فاز ۱۰۶-۲) تا همهٔ جاها یک عدد بگویند.
 *
 * گیت: multi_vendor_leaderboard (تب چندفروشندگی تنظیمات برنامه‌نویس — قانون طلایی)
 * + دسترسی sellers.index. خاموش → ریدایرکت لیست فروشندگان + toast.
 */
#[Title('مقایسهٔ فروشندگان')]
class SellerLeaderboard extends Component
{
    /* ---------------- فیلتر دوره (State در URL) ---------------- */

    #[Url(as: 'period', except: '30')]
    public string $period = '30';

    public function mount(): void
    {
        if (! MultiVendorSettings::leaderboardEnabled()) {
            session()->put('toast-error', 'مقایسهٔ فروشندگان از تنظیمات چندفروشندگی غیرفعال شده است.');
            $this->redirectRoute('np.sellers.index', absolute: false);

            return;
        }

        auth('adminPanel')->user()?->can('sellers.index') ?: abort(403);
    }

    /* ---------------- اعمال فیلتر ---------------- */

    public function applyPeriod(string $period): void
    {
        auth('adminPanel')->user()?->can('sellers.index') ?: abort(403);

        if (! in_array($period, ['30', '90', 'all'], true)) {
            return;
        }

        $this->period = $period;
    }

    /* ---------------- داده‌ها ---------------- */

    /** ردیف‌های رتبه‌بندی — مرتب بر درآمد نزولی */
    #[Computed]
    public function rows(): Collection
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->whereNotNull('order_items.seller_id');

        if ($this->period !== 'all') {
            $query->where('orders.created_at', '>=', now()->subDays((int) $this->period));
        }

        $aggregates = $query
            ->selectRaw('order_items.seller_id as seller_id, SUM(order_items.price * order_items.quantity) as revenue, COUNT(DISTINCT order_items.order_id) as orders_count, SUM(order_items.quantity) as items_count')
            ->groupBy('order_items.seller_id')
            ->orderByDesc('revenue')
            ->get();

        $sellers = Seller::query()
            ->whereIn('id', $aggregates->pluck('seller_id'))
            ->with('seller_info:id,seller_id,first_name,last_name,business_name')
            ->get()
            ->keyBy('id');

        return $aggregates->map(function ($row) use ($sellers) {
            $seller = $sellers->get($row->seller_id);
            $info = $seller?->seller_info;
            $name = trim((string) ($info?->business_name ?? '')) ?: trim(((string) $info?->first_name).' '.((string) $info?->last_name));

            return (object) [
                'seller_id' => (int) $row->seller_id,
                'name' => $name !== '' ? $name : ('فروشنده #'.$row->seller_id),
                'slug' => $seller?->slug,
                'revenue' => (int) $row->revenue,
                'orders_count' => (int) $row->orders_count,
                'items_count' => (int) $row->items_count,
                'avg_order' => $row->orders_count > 0 ? (int) round($row->revenue / $row->orders_count) : 0,
            ];
        })->values();
    }

    /** جمع‌های کل دوره */
    #[Computed]
    public function stats(): array
    {
        $rows = $this->rows;
        $totalRevenue = $rows->sum('revenue');

        return [
            'sellers' => $rows->count(),
            'revenue' => $totalRevenue,
            'orders' => $rows->sum('orders_count'),
            'avg_revenue' => $rows->isNotEmpty() ? (int) round($totalRevenue / $rows->count()) : 0,
        ];
    }

    /* ---------------- آپشن نمودار (ECharts — افقی، ۱۰ نفر اول) ---------------- */

    public function chartOptions(): array
    {
        $top = $this->rows->take(10);

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
            'grid' => ['top' => 16, 'right' => 28, 'bottom' => 24, 'left' => 8, 'containLabel' => true],
            'xAxis' => [
                'type' => 'value',
                'name' => 'درآمد (تومان)',
                'nameTextStyle' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10, 'formatter' => 'fn:faNumCompact'],
                'splitLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.15)']],
            ],
            'yAxis' => [
                'type' => 'category',
                'inverse' => true,
                'data' => $top->map(fn ($row) => Str::limit($row->name, 18))->all(),
                'axisLine' => ['lineStyle' => ['color' => 'rgba(148,163,184,.25)']],
                'axisLabel' => ['color' => '#8b93a1', 'fontSize' => 10],
                'axisTick' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'درآمد',
                    'type' => 'bar',
                    'barMaxWidth' => 22,
                    'itemStyle' => ['borderRadius' => [0, 6, 6, 0]],
                    'data' => $top->map(fn ($row) => $row->revenue)->all(),
                ],
            ],
        ];
    }

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.leaderboard')
            ->layout('admin-panel::layout');
    }
}
