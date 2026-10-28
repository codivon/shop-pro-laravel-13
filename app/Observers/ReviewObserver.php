<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\SellerInfo;
use Illuminate\Support\Facades\DB;

/**
 * فاز ۱۱۸ — ثبت واقعی «عملکرد فروشنده» و «رضایت کالا» از نظر خریدارها
 *
 * هر نظری که به یک فروشنده منتسب است (reviews.seller_id — ست‌شده هنگام ثبتِ
 * نظرِ خریدارِ واقعی از ReviewController) در محاسبهٔ دو شاخصِ همان فروشنده در
 * sellers_info لحاظ می‌شود:
 *
 *  • operation     ← میانگین rating نظرهای acceptedِ خریدارها (مقیاس 0.5 تا 5)
 *  • satisfaction  ← درصد رأی «پیشنهاد می‌کنم» (yes / (yes+no))؛ اگر رأی suggest
 *                    نداشتیم، میانگین امتیاز به درصد تبدیل می‌شود (rating/5×100)
 *
 * اگر برای فروشنده هیچ نظر acceptedِ منتسبی نباشد، مقدار دستیِ ادمین دست‌نخورده می‌ماند.
 * رویداد saved به‌جای updated است تا همِ ایجاد و همِ تغییر وضعیت (تأیید ادمین)
 * و همِ تغییر seller_id (جابه‌جایی انتساب) باعث بازمحاسبهٔ دو فروشنده شود.
 */
class ReviewObserver
{
    public function saved(Review $review): void
    {
        // seller_id ممکن است تغییر کرده باشد؛ مقادیر خامِ دیتابیس را می‌گیریم
        $sellerIds = array_filter(array_unique([
            (int) ($review->getOriginal('seller_id') ?? 0),
            (int) ($review->seller_id ?? 0),
        ]));

        foreach ($sellerIds as $sellerId) {
            $this->refreshSellerMetrics((int) $sellerId);
        }
    }

    public function deleted(Review $review): void
    {
        if ($sellerId = (int) ($review->seller_id ?? 0)) {
            $this->refreshSellerMetrics($sellerId);
        }
    }

    /**
     * بازمحاسبهٔ operation و satisfaction فروشنده از نظرهای acceptedِ منتسب به او
     */
    public static function refreshSellerMetrics(int $sellerId): void
    {
        if ($sellerId <= 0) {
            return;
        }

        $info = SellerInfo::query()->where('seller_id', $sellerId)->first();

        if (! $info) {
            return;
        }

        $stats = Review::query()
            ->where('seller_id', $sellerId)
            ->accepted()
            ->whereNotNull('user_id')
            ->selectRaw('COUNT(*) AS total, AVG(rating) AS avg_rating')
            ->selectRaw("SUM(suggest = 'yes') AS yes_votes, SUM(suggest = 'no') AS no_votes")
            ->first();

        if (! $stats || (int) $stats->total === 0) {
            return; // هیچ نظر واقعیِ تأییدشده‌ای نیست → مقدار دستی ادمین حفظ می‌شود
        }

        $avgRating = (float) $stats->avg_rating;
        $yes = (int) ($stats->yes_votes ?? 0);
        $no = (int) ($stats->no_votes ?? 0);

        // رضایت: اولویت با رأی صریحِ «پیشنهاد می‌کنم / نمی‌کنم»، در نبودش میانگین امتیاز
        $satisfaction = ($yes + $no) > 0
            ? round(($yes * 100) / ($yes + $no))
            : (int) round(($avgRating / 5) * 100);

        // عملکرد: میانگین امتیاز در بازهٔ مجاز 0.5 تا 5
        $operation = min(5, max(0.5, round($avgRating, 1)));

        DB::table('sellers_info')
            ->where('id', $info->id)
            ->update([
                'operation' => $operation,
                'satisfaction' => $satisfaction,
                'updated_at' => now(),
            ]);
    }
}
