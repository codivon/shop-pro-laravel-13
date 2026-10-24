<?php
// app/Http/Controllers/Seller/SellerStatisticsController.php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Viewer;
use App\Traits\SellerViewStatisticsTrait;
use Illuminate\Http\Request;

class SellerStatisticsController extends Controller
{
    use SellerViewStatisticsTrait;

    public function __construct()
    {
        $this->middleware('auth:seller');
    }

    /**
     * ساخت کوئری پایه برای فروشگاه فروشنده (بررسی هر سه فیلد)
     * سانیار فاز J: دو بند آخر WeblakShop («%/store/%» و «%/shop%») فروشگاهِ «هر» فروشنده‌ای را
     * match می‌کرد و با چند فروشنده آمار را متورم می‌کرد؛ هر سه بند به slug همین فروشنده قید شد.
     */
    private function baseSellerQuery($sellerSlug)
    {
        return Viewer::where(function ($query) use ($sellerSlug) {
            $query->where('path', 'like', '/store/' . $sellerSlug . '%')
                ->orWhere('product_path', 'like', $sellerSlug . '%')
                ->orWhere('page_path', 'like', '%' . $sellerSlug . '%');
        });
    }

    /**
     * صفحه اصلی آمار فروشنده
     * سانیار فاز J: نما به فضای‌نام front:: منتقل شد (نمای seller.statistics در تم سانیار وجود ندارد
     * و باعث خطای ۵۰۰ می‌شد). متدهای مرده latestVisitors()/breakdown() حذف شدند (روتی به آن‌ها نیست).
     */
    public function index()
    {
        $sellerSlug = seller()->slug;

        if (!$sellerSlug) {
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

        return view('front::sellers.panel.statistics.index', compact(
            'totalVisits',
            'uniqueVisitors',
            'todayVisits',
            'yesterdayVisits',
            'changePercent',
            'averageVisits',
            'dailyVisits',
            'todayPathVisits',
            'todayProductVisits',
            'todayPageVisits'
        ));
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
}
