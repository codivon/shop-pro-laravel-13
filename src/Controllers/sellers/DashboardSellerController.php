<?php

namespace Themes\SaniarTheme\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SellerVariant;
use App\Models\Viewer;

class DashboardSellerController extends Controller
{
    public function index()
    {

        $seller_variants=SellerVariant::where('seller_id',sellerID())->get();
        $seller_product_id=SellerVariant::where('seller_id',sellerID())->get()->pluck('product_id')->toArray();

        $seller_competitor=SellerVariant::whereIn('product_id',$seller_product_id)->where('seller_id','!=',sellerID())->get()->pluck('product_id');

        $seller_no_competitor=SellerVariant::where('seller_id',sellerID())->whereNotIn('product_id',$seller_competitor)->get();
        $products = Product::where('seller_id',seller_info()->seller_id)->get();


        // ========== آمار بازدید فروشگاه ==========
        $sellerSlug=seller()->slug;
        // ========== آمار بازدید فروشگاه ==========
        $totalVisits = Viewer::getSellerStoreVisitsCount($sellerSlug);
        $uniqueVisitors = Viewer::getUniqueSellerStoreVisitors($sellerSlug);
        $todayVisits = Viewer::getTodaySellerStoreVisits($sellerSlug);
        $changePercent = Viewer::getChangePercent($sellerSlug);
        $averageVisits = Viewer::getAverageDailyVisits($sellerSlug);
        $dailyVisits = Viewer::getDailySellerStoreVisits($sellerSlug, 7);



        return view('front::sellers.panel.index',compact([
            'seller_variants','products','seller_competitor','seller_no_competitor',
            'totalVisits',
            'uniqueVisitors',
            'todayVisits',
            'changePercent',
            'averageVisits',
            'dailyVisits'
        ]));
    }

}
