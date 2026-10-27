<?php

namespace App\Livewire\Seller\Statistics;

use App\Models\OrderItem;
use App\Models\Viewer;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — صفحهٔ آمار پنل فروشندگان مدرن (پورت SellerStatisticsController@index).
 * بازدید فروشگاه با همان کوئری پایهٔ سه‌فیلدی + گزارش فروش فاز ۱۰۶-۲
 * (گیت seller_analytics_enabled — قانون طلایی).
 */
#[Title('آمار')]
#[Layout('sellers.layouts.panel')]
class Index extends Component
{
    public int $totalVisits = 0;

    public int $uniqueVisitors = 0;

    public int $todayVisits = 0;

    public int $yesterdayVisits = 0;

    public float $changePercent = 0;

    public int $averageVisits = 0;

    /** بازدیدهای ۷ روز اخیر [['date' => ..., 'count' => ...]] */
    public array $dailyVisits = [];

    public int $todayPathVisits = 0;

    public int $todayProductVisits = 0;

    public int $todayPageVisits = 0;

    /** گزارش فروش (فاز ۱۰۶-۲) — null یعنی سوییچ خاموش است */
    public ?array $sales = null;

    public function mount(): void
    {
        $sellerSlug = seller()->slug;

        if (! $sellerSlug) {
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        $base = fn () => $this->baseSellerQuery($sellerSlug);

        $this->totalVisits = (int) $base()->count();
        $this->uniqueVisitors = (int) $base()->distinct('ip')->count('ip');
        $this->todayVisits = (int) $base()->whereDate('created_at', today())->count();
        $this->yesterdayVisits = (int) $base()->whereDate('created_at', today()->subDay())->count();
        $this->changePercent = $this->yesterdayVisits > 0
            ? round(($this->todayVisits - $this->yesterdayVisits) / $this->yesterdayVisits * 100, 1)
            : ($this->todayVisits > 0 ? 100 : 0);

        $monthlyVisits = (int) $base()->where('created_at', '>=', now()->subDays(30))->count();
        $this->averageVisits = $monthlyVisits > 0 ? (int) round($monthlyVisits / 30) : 0;

        $this->dailyVisits = $base()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->values()
            ->all();

        $this->todayPathVisits = (int) $base()->whereDate('created_at', today())->whereNotNull('path')->count();
        $this->todayProductVisits = (int) $base()->whereDate('created_at', today())->whereNotNull('product_path')->count();
        $this->todayPageVisits = (int) $base()->whereDate('created_at', today())->whereNotNull('page_path')->count();

        $this->sales = MultiVendorSettings::analyticsEnabled()
            ? $this->salesReport($sellerSlug)
            : null;
    }

    /** کوئری پایهٔ بازدید فروشگاه (بررسی هر سه فیلد — عین کنترلر قدیمی) */
    private function baseSellerQuery(string $sellerSlug)
    {
        return Viewer::where(function ($query) use ($sellerSlug) {
            $query->where('path', 'like', '/store/'.$sellerSlug.'%')
                ->orWhere('product_path', 'like', $sellerSlug.'%')
                ->orWhere('page_path', 'like', '%/store/%')
                ->orWhere('page_path', 'like', '%/shop%');
        });
    }

    /** گزارش فروش ۳۰ روزه (فاز ۱۰۶-۲) — پورت salesReport کنترلر قدیمی */
    private function salesReport(string $sellerSlug): array
    {
        $sellerId = (int) sellerID();
        $since = now()->subDays(30);

        $paidItems = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->where('order_items.created_at', '>=', $since);

        $daily = (clone $paidItems)
            ->selectRaw('DATE(order_items.created_at) as date, SUM(order_items.price * order_items.quantity) as revenue, SUM(order_items.quantity) as items')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $revenue = (int) ((clone $paidItems)->selectRaw('COALESCE(SUM(order_items.price * order_items.quantity), 0) as total')->value('total'));
        $orders = (clone $paidItems)->distinct('order_id')->count('order_id');
        $items = (int) ((clone $paidItems)->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total')->value('total'));

        $bestsellers = (clone $paidItems)
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->with('product:id,title,slug')
            ->get()
            ->filter(fn ($row) => $row->product !== null);

        $views = Viewer::where('created_at', '>=', $since)
            ->whereIn('product_path', function ($q) use ($sellerId) {
                $q->select('slug')->from('products')->where('seller_id', $sellerId);
            })
            ->count();

        $conversionRate = $views > 0 ? round($orders / $views * 100, 1) : null;

        return [
            'daily' => $daily,
            'chartLabels' => $daily->map(fn ($row) => jdate(Carbon::parse($row->date))->format('m/d'))->values()->all(),
            'chartData' => $daily->map(fn ($row) => (int) $row->revenue)->values()->all(),
            'revenue' => $revenue,
            'orders' => $orders,
            'items' => $items,
            'avgOrder' => $orders > 0 ? (int) round($revenue / $orders) : 0,
            'bestsellers' => $bestsellers,
            'views' => $views,
            'conversionRate' => $conversionRate,
        ];
    }

    public function render()
    {
        return view('sellers.panel.statistics.index');
    }
}
