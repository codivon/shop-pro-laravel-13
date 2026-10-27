<?php

namespace Themes\WeblakShop\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\StockNotify;
use App\Support\MultiVendorSettings;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * فاز ۱۰۶-۳ — سیگنال‌های تقاضا از نگاه فروشنده.
 *
 * داده (StockNotify فاز ۷۸ + PriceAlert فاز ۸۳) از قبل در دیتابیس است ولی
 * فقط ادمین می‌دید؛ این صفحه همان تقاضای واقعی را به صاحبِ محصول نشان می‌دهد:
 * «X مشتری منتظر موجودی» و «X منتظر کاهش قیمت» — ردیف‌به‌ردیف هر محصول.
 *
 * گیت: seller_demand_signals (تب چندفروشندگی تنظیمات برنامه‌نویس — قانون طلایی).
 * حریم خصوصی: موبایل مشتری برای فروشنده ماسک می‌شود (ادمین نسخهٔ کامل را می‌بیند)
 * و شمارش‌ها فقط-خواندنی است؛ علامت‌گذاری seen هنوز کارِ جریان ادمین است.
 */
class SellerDemandController extends Controller
{
    public function index()
    {
        if (! MultiVendorSettings::demandSignalsEnabled()) {
            session()->put('toast-error', 'سیگنال‌های تقاضا از تنظیمات غیرفعال شده است.');

            return redirect()->route('seller.dashboard');
        }

        $sellerId = (int) sellerID();

        // شمارش تقاضا به‌ازای هر محصول — چهار گروه‌بندی سبک (بدون N+1 — درس فاز ۱۰۶-۲)
        $sellerProducts = fn ($q) => $q->where('seller_id', $sellerId);

        $stockByProduct = StockNotify::query()
            ->selectRaw('product_id, count(*) as aggregate')
            ->whereHas('product', $sellerProducts)
            ->groupBy('product_id')
            ->pluck('aggregate', 'product_id');

        $alertsByProduct = PriceAlert::query()
            ->active()
            ->selectRaw('product_id, count(*) as aggregate')
            ->whereHas('product', $sellerProducts)
            ->groupBy('product_id')
            ->pluck('aggregate', 'product_id');

        $lastStockAt = StockNotify::query()
            ->selectRaw('product_id, max(created_at) as latest')
            ->whereHas('product', $sellerProducts)
            ->groupBy('product_id')
            ->pluck('latest', 'product_id');

        $lastAlertAt = PriceAlert::query()
            ->selectRaw('product_id, max(created_at) as latest')
            ->whereHas('product', $sellerProducts)
            ->groupBy('product_id')
            ->pluck('latest', 'product_id');

        $productIds = $stockByProduct->keys()->merge($alertsByProduct->keys())->unique()->values();

        // ردیف‌به‌ردیف محصولات دارای تقاضا — مرتب بر اساس کل تقاضا
        // (تعداد محصولاتِ دارای تقاضا برای یک فروشنده کوچک است؛ مرتب‌سازی درون‌حافظه‌ای کافی است)
        $rows = Product::query()
            ->whereIn('id', $productIds)
            ->where('seller_id', $sellerId)
            ->get(['id', 'title', 'slug', 'in_stock'])
            ->map(function (Product $product) use ($stockByProduct, $alertsByProduct, $lastStockAt, $lastAlertAt) {
                $stock = (int) ($stockByProduct[$product->id] ?? 0);
                $alerts = (int) ($alertsByProduct[$product->id] ?? 0);

                $product->demand_stock = $stock;
                $product->demand_alerts = $alerts;
                $product->demand_total = $stock + $alerts;
                $product->demand_last_at = collect([
                    $lastStockAt[$product->id] ?? null,
                    $lastAlertAt[$product->id] ?? null,
                ])->filter()->max();

                return $product;
            })
            ->sortByDesc('demand_total')
            ->values();

        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $products = new LengthAwarePaginator(
            $rows->forPage($page, $perPage),
            $rows->count(),
            $perPage,
            $page,
            ['path' => route('seller.demand.signals')]
        );

        $stats = [
            // کل مشتریان منتظر موجودشدن محصولات فروشنده
            'waiting_stock' => (int) $stockByProduct->sum(),
            // اشتراک‌های فعالِ هشدار کاهش قیمت
            'waiting_price' => (int) $alertsByProduct->sum(),
            // محصولات دارای حداقل یک سیگنال
            'products_with_demand' => $productIds->count(),
            // سیگنال‌های ۷ روز اخیر (هر دو نوع — نبض روزهای گذشته)
            'recent_7days' => StockNotify::whereHas('product', $sellerProducts)
                ->where('created_at', '>=', now()->subDays(7))
                ->count()
                + PriceAlert::whereHas('product', $sellerProducts)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
        ];

        // خوراک آخرین سیگنال‌ها (هر دو نوع ادغام و مرتب زمانی) — با موبایل ماسک‌شده
        $recentStock = StockNotify::query()
            ->whereHas('product', $sellerProducts)
            ->with('product:id,title,slug')
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'mobile', 'seen', 'product_id', 'created_at'])
            ->map(fn (StockNotify $notify) => (object) [
                'type' => 'stock',
                'name' => $notify->name,
                'mobile' => $this->maskMobile($notify->mobile),
                'product' => $notify->product,
                'seen' => (bool) $notify->seen,
                'created_at' => $notify->created_at,
            ]);

        $recentAlerts = PriceAlert::query()
            ->active()
            ->whereHas('product', $sellerProducts)
            ->with('product:id,title,slug')
            ->latest()
            ->limit(10)
            ->get(['id', 'name', 'mobile', 'target_price', 'product_id', 'created_at'])
            ->map(fn (PriceAlert $alert) => (object) [
                'type' => 'price',
                'name' => $alert->name,
                'mobile' => $this->maskMobile($alert->mobile),
                'product' => $alert->product,
                'seen' => true,
                'target_price' => $alert->target_price === null ? null : (int) $alert->target_price,
                'created_at' => $alert->created_at,
            ]);

        $recent = $recentStock->merge($recentAlerts)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return view('front::sellers.panel.demand.index', compact('products', 'stats', 'recent'));
    }

    /**
     * حریم خصوصی: موبایل مشتری برای فروشنده ماسک می‌شود
     * (0913***456) — شمارهٔ کامل فقط در پنل ادمین دیده می‌شود.
     */
    private function maskMobile(?string $mobile): string
    {
        $mobile = (string) $mobile;

        if (strlen($mobile) >= 7) {
            return substr($mobile, 0, 4).'***'.substr($mobile, -3);
        }

        return '***';
    }
}
