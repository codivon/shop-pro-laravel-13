<?php

namespace App\Livewire\Seller;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\StockNotify;
use App\Support\MultiVendorSettings;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — سیگنال‌های تقاضا از نگاه فروشنده (پورت SellerDemandController@index).
 * داده: «X مشتری منتظر موجودی» (StockNotify فاز ۷۸) و «X منتظر کاهش قیمت»
 * (PriceAlert فاز ۸۳) ردیف‌به‌ردیف هر محصول. گیت: seller_demand_signals (قانون طلایی).
 * حریم خصوصی: موبایل مشتری ماسک می‌شود (0913***456) — نسخهٔ کامل فقط ادمین.
 * paginator/خوراک به‌صورت غیرعمومی ساخته می‌شوند (Livewire نمی‌تواند serialize کند).
 */
#[Title('سیگنال‌های تقاضا')]
#[Layout('sellers.layouts.panel')]
class Demand extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        if (! MultiVendorSettings::demandSignalsEnabled()) {
            session()->put('toast-error', 'سیگنال‌های تقاضا از تنظیمات غیرفعال شده است.');
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        $sellerProducts = fn ($q) => $q->where('seller_id', (int) sellerID());

        // شمارش تقاضا به‌ازای هر محصول — چهار گروه‌بندی سبک (بدون N+1 — درس فاز ۱۰۶-۲)
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

        $this->stats = [
            // کل مشتریان منتظر موجودشدن محصولات فروشنده
            'waiting_stock' => (int) $stockByProduct->sum(),
            // اشتراک‌های فعالِ هشدار کاهش قیمت
            'waiting_price' => (int) $alertsByProduct->sum(),
            // محصولات دارای حداقل یک سیگنال
            'products_with_demand' => $stockByProduct->keys()->merge($alertsByProduct->keys())->unique()->count(),
            // سیگنال‌های ۷ روز اخیر (هر دو نوع — نبض روزهای گذشته)
            'recent_7days' => StockNotify::whereHas('product', $sellerProducts)
                ->where('created_at', '>=', now()->subDays(7))
                ->count()
                + PriceAlert::whereHas('product', $sellerProducts)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
        ];
    }

    public function render()
    {
        $sellerId = (int) sellerID();
        $sellerProducts = fn ($q) => $q->where('seller_id', $sellerId);

        // ========== ردیف‌به‌ردیف محصولات دارای تقاضا ==========
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

        // مرتب بر اساس کل تقاضا (تعداد محصولاتِ دارای تقاضا برای یک فروشنده کوچک است)
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

        // ========== خوراک آخرین سیگنال‌ها (هر دو نوع ادغام و مرتب زمانی) — با موبایل ماسک‌شده ==========
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
                'target_price' => null,
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

        return view('sellers.panel.demand.index', compact('products', 'recent'));
    }

    /** حریم خصوصی: موبایل مشتری برای فروشنده ماسک می‌شود (0913***456) — عین کنترلر قدیمی */
    private function maskMobile(?string $mobile): string
    {
        $mobile = (string) $mobile;

        if (strlen($mobile) >= 7) {
            return substr($mobile, 0, 4).'***'.substr($mobile, -3);
        }

        return '***';
    }
}
