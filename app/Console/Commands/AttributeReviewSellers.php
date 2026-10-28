<?php

namespace App\Console\Commands;

use App\Models\Review;
use App\Observers\ReviewObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * فاز ۱۱۸ — backfill انتساب فروشنده به نظرهای قبلی خریدارها
 *
 * نظرهای بدون seller_id را با آخرین سفارشِ پرداخت‌شدهٔ صاحبشان برای همان محصول
 * به فروشگاه خریداری‌شده وصل می‌کند و سپس operation/satisfaction همان فروشنده‌ها
 * را بازمحاسبه می‌کند (همان منطق ReviewObserver).
 *
 * اجرا: php artisan reviews:attribute-sellers
 */
class AttributeReviewSellers extends Command
{
    protected $signature = 'reviews:attribute-sellers {--detach : حذف انتساب‌های غلط؟ (نه — فقط پر کردن جاهای خالی)}';

    protected $description = 'اتصال نظرهای قبلی خریدارها به فروشگاهِ خریداری‌شده و بازمحاسبهٔ امتیاز فروشندگان';

    public function handle(): int
    {
        $reviews = Review::query()
            ->whereNull('seller_id')
            ->whereNotNull('user_id')
            ->whereNotNull('product_id')
            ->orderBy('id')
            ->get(['id', 'user_id', 'product_id']);

        $this->info("{$reviews->count()} نظر بدون فروشنده پیدا شد.");

        $attributed = 0;
        $sellerIds = [];

        foreach ($reviews as $review) {
            $purchase = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.user_id', $review->user_id)
                ->where('orders.status', 'paid')
                ->where('order_items.product_id', $review->product_id)
                ->orderByDesc('order_items.id')
                ->select('order_items.seller_id', 'order_items.price_id')
                ->first();

            if ($purchase && $purchase->seller_id) {
                Review::withoutEvents(function () use ($review, $purchase) {
                    DB::table('reviews')->where('id', $review->id)->update([
                        'seller_id' => $purchase->seller_id,
                        'price_id' => $purchase->price_id,
                    ]);
                });

                $attributed++;
                $sellerIds[] = (int) $purchase->seller_id;
            }
        }

        foreach (array_unique($sellerIds) as $sellerId) {
            ReviewObserver::refreshSellerMetrics($sellerId);
        }

        $this->info("{$attributed} نظر به فروشگاه‌ها متصل شد؛ امتیاز " . count(array_unique($sellerIds)) . ' فروشنده بازمحاسبه شد.');

        return self::SUCCESS;
    }
}
