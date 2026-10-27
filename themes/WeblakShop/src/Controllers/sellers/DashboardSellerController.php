<?php

namespace Themes\WeblakShop\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerDeposit;
use App\Models\SellerVariant;
use App\Models\Viewer;
use App\Models\WalletHistory;
use App\Support\MultiVendorSettings;

class DashboardSellerController extends Controller
{
    public function index()
    {

        $seller_variants = SellerVariant::where('seller_id', sellerID())->get();
        $seller_product_id = SellerVariant::where('seller_id', sellerID())->get()->pluck('product_id')->toArray();

        $seller_competitor = SellerVariant::whereIn('product_id', $seller_product_id)->where('seller_id', '!=', sellerID())->get()->pluck('product_id');

        $seller_no_competitor = SellerVariant::where('seller_id', sellerID())->whereNotIn('product_id', $seller_competitor)->get();
        $products = Product::where('seller_id', seller_info()->seller_id)->get();

        // ========== آمار بازدید فروشگاه ==========
        $sellerSlug = seller()->slug;
        // ========== آمار بازدید فروشگاه ==========
        $totalVisits = Viewer::getSellerStoreVisitsCount($sellerSlug);
        $uniqueVisitors = Viewer::getUniqueSellerStoreVisitors($sellerSlug);
        $todayVisits = Viewer::getTodaySellerStoreVisits($sellerSlug);
        $changePercent = Viewer::getChangePercent($sellerSlug);
        $averageVisits = Viewer::getAverageDailyVisits($sellerSlug);
        $dailyVisits = Viewer::getDailySellerStoreVisits($sellerSlug, 7);

        // ========== آمار تسویه (فاز ۱۰۳ — گیت از تنظیمات چندفروشندگی) ==========
        $vendorStats = null;

        if (MultiVendorSettings::enabled() && MultiVendorSettings::dashboardStatsEnabled()) {
            $sellerId = sellerID();
            $wallet = auth('seller')->user()->getWallet();

            $vendorStats = [
                // موجودی قابل برداشت (از دفتر کیف پول)
                'balance' => (int) $wallet->balance(),

                // فروش موفق: جمع آیتم‌های سفارش‌های پرداخت‌شدهٔ این فروشنده
                'total_sales' => (int) OrderItem::where('seller_id', $sellerId)
                    ->whereHas('order', fn ($q) => $q->where('status', 'paid'))
                    ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
                    ->value('total'),

                // کمیسیون پرداخت‌شده (سهم سایت) از دفتر واریزی‌ها
                'total_commission' => (int) SellerDeposit::where('seller_id', $sellerId)
                    ->where('status', 'success')
                    ->sum('amount'),

                // درخواست‌های برداشت در انتظار پرداخت
                'waiting_withdraw_count' => WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
                    ->where(['withdraw' => 1, 'type' => 'withdraw'])
                    ->where('status_pay', 'waiting')
                    ->count(),

                'waiting_withdraw_amount' => (int) WalletHistory::whereHas('wallet', fn ($q) => $q->where('seller_id', $sellerId))
                    ->where(['withdraw' => 1, 'type' => 'withdraw'])
                    ->where('status_pay', 'waiting')
                    ->sum('amount'),
            ];
        }

        return view('front::sellers.panel.index', compact([
            'seller_variants', 'products', 'seller_competitor', 'seller_no_competitor',
            'totalVisits',
            'uniqueVisitors',
            'todayVisits',
            'changePercent',
            'averageVisits',
            'dailyVisits',
            'vendorStats',
        ]));
    }
}
