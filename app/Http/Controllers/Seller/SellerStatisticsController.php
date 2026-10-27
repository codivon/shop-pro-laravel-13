<?php

// app/Http/Controllers/Seller/SellerStatisticsController.php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Viewer;
use App\Support\MultiVendorSettings;
use App\Traits\SellerViewStatisticsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SellerStatisticsController extends Controller
{
    use SellerViewStatisticsTrait;

    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ساخت کوئری پایه برای فروشگاه فروشنده (بررسی هر سه فیلد)
     */
    private function baseSellerQuery($sellerSlug)
    {
        return Viewer::where(function ($query) use ($sellerSlug) {
            // 1. فیلد path: /store/aopy
            $query->where('path', 'like', '/store/'.$sellerSlug.'%')
                // 2. فیلد product_path: aopy?s=...
                ->orWhere('product_path', 'like', $sellerSlug.'%')
                // 3. فیلد page_path: store یا shop
                ->orWhere('page_path', 'like', '%/store/%')
                ->orWhere('page_path', 'like', '%/shop%');
        });
    }

    /**
     * صفحه اصلی آمار فروشنده
     */
    public function index()
    {
        $sellerSlug = seller()->slug;

        if (! $sellerSlug) {
            return redirect()->back()->with('error', 'فروشنده یافت نشد');
        }

        // کل بازدیدها (بر اساس هر سه فیلد)
        $totalVisits = $this->baseSellerQuery($sellerSlug)->count();

        // بازدیدکنندگان یکتا
        $uniqueVisitors = $this->baseSellerQuery($sellerSlug)
            ->distinct('ip')
            ->count('ip');

        // بازدید امروز
        $todayVisits = $this->baseSellerQuery($sellerSlug)
            ->whereDate('created_at', today())
            ->count();

        // بازدید دیروز
        $yesterdayVisits = $this->baseSellerQuery($sellerSlug)
            ->whereDate('created_at', today()->subDay())
            ->count();

        // درصد تغییرات
        $changePercent = $yesterdayVisits > 0
            ? round(($todayVisits - $yesterdayVisits) / $yesterdayVisits * 100, 1)
            : ($todayVisits > 0 ? 100 : 0);

        // میانگین روزانه (30 روز اخیر)
        $monthlyVisits = $this->baseSellerQuery($sellerSlug)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $averageVisits = $monthlyVisits > 0 ? round($monthlyVisits / 30) : 0;

        // بازدیدهای 7 روز اخیر
        $dailyVisits = $this->baseSellerQuery($sellerSlug)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // بازدیدهای امروز بر اساس نوع صفحه
        $todayPathVisits = $this->baseSellerQuery($sellerSlug)
            ->whereDate('created_at', today())
            ->whereNotNull('path')
            ->count();

        $todayProductVisits = $this->baseSellerQuery($sellerSlug)
            ->whereDate('created_at', today())
            ->whereNotNull('product_path')
            ->count();

        $todayPageVisits = $this->baseSellerQuery($sellerSlug)
            ->whereDate('created_at', today())
            ->whereNotNull('page_path')
            ->count();

        // فاز ۱۰۶-۲ — گزارش فروش فروشنده (سوییچ seller_analytics_enabled؛ قانون طلایی):
        // درآمد زمانی ۳۰ روز + پرفروش‌ترین‌ها + بازدید→سفارش از order_items.seller_id
        // روی سفارش‌های paid — همان مبنای واحد کارت «ارزش سفارشات موفق» داشبورد (درس فاز ۱۰۶ الف).
        $sales = MultiVendorSettings::analyticsEnabled()
            ? $this->salesReport($sellerSlug)
            : null;

        // فاز ۱۰۶ الف — ویو با namespace تم سرو می‌شود (فرمت واحد بقیهٔ پنل).
        // قبلاً view('seller.statistics.index') بود که هیچ namespace/پوشه‌ای نداشت → 500.
        return view('sellers-old.panel.statistics.index', compact(
            'totalVisits',
            'uniqueVisitors',
            'todayVisits',
            'yesterdayVisits',
            'changePercent',
            'averageVisits',
            'dailyVisits',
            'todayPathVisits',
            'todayProductVisits',
            'todayPageVisits',
            'sales'
        ));
    }

    /**
     * گزارش فروش فروشنده (فاز ۱۰۶-۲) — ۳۰ روز اخیر.
     *
     * مبنای درآمد: SUM(price × quantity) آیتم‌های سفارش‌های paid با
     * order_items.seller_id (همان مبنای کارت داشبورد و دفتر تسویهٔ فاز ۱۰۳).
     *
     * @return array{daily: Collection, chartLabels: array, chartData: array,
     *               revenue: int, orders: int, items: int, avgOrder: int,
     *               bestsellers: Collection,
     *               views: int, conversionRate: float|null}
     */
    private function salesReport(string $sellerSlug): array
    {
        $sellerId = (int) sellerID();
        $since = now()->subDays(30);

        $paidItems = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
            ->where('order_items.created_at', '>=', $since);

        // درآمد روزانه (فقط روزهای دارای فروش — نمودار تقویم جلالی در سرور ساخته می‌شود)
        $daily = (clone $paidItems)
            ->selectRaw('DATE(order_items.created_at) as date, SUM(order_items.price * order_items.quantity) as revenue, SUM(order_items.quantity) as items')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $revenue = (int) ((clone $paidItems)->selectRaw('COALESCE(SUM(order_items.price * order_items.quantity), 0) as total')->value('total'));
        $orders = (clone $paidItems)->distinct('order_id')->count('order_id');
        $items = (int) ((clone $paidItems)->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total')->value('total'));

        // پرفروش‌ترین‌ها (۵ ردیف بر اساس تعداد فروش) — محصول حذف‌شده با nullSafe رد می‌شود
        $bestsellers = (clone $paidItems)
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->with('product:id,title,slug')
            ->get()
            ->filter(fn ($row) => $row->product !== null);

        // بازدید صفحاتِ محصولاتِ این فروشنده در همان بازهٔ ۳۰ روزه — منبع Viewer.
        // محصول فروشنده = products.seller_id (الگوی «جستجو یا درج محصول» پنل فروشنده)؛
        // کوئری پایهٔ بازدید صفحه (baseSellerQuery) مبنای seller-slug دارد و برای
        // «بازدید محصول» معنای دقیقی ندارد — نرخ تبدیل با دامنهٔ محصولات خودِ فروشنده محاسبه می‌شود.
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

    /**
     * آمار بازدیدها (AJAX)
     */
    public function viewCounts(Request $request)
    {
        return $this->sellerViewCounts($request);
    }

    /**
     * آمار بازدیدکنندگان یکتا (AJAX)
     */
    public function viewerCounts(Request $request)
    {
        return $this->sellerViewerCounts($request);
    }

    /**
     * لیست آخرین بازدیدکنندگان
     */
    public function latestVisitors()
    {
        $sellerSlug = seller()->slug;

        $visitors = $this->baseSellerQuery($sellerSlug)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sellers-old.panel.statistics.visitors', compact('visitors'));
    }

    /**
     * آمار تفکیکی بر اساس نوع بازدید
     */
    public function breakdown()
    {
        $sellerSlug = seller()->slug;

        // بازدیدهای 7 روز اخیر بر اساس نوع
        $dailyPathVisits = $this->baseSellerQuery($sellerSlug)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('path')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dailyProductVisits = $this->baseSellerQuery($sellerSlug)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('product_path')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $dailyPageVisits = $this->baseSellerQuery($sellerSlug)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('page_path')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'path_visits' => $dailyPathVisits,
                'product_visits' => $dailyProductVisits,
                'page_visits' => $dailyPageVisits,
            ],
        ]);
    }
}
