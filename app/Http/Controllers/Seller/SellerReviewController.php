<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\MultiVendorSettings;

/**
 * فاز ۱۰۶-۴ — نظرات محصولات از نگاه فروشنده.
 *
 * داده (Review فاز قدیمی هسته) از قبل در دیتابیس است ولی فروشنده فقط
 * Q&A (Comment) می‌دید؛ این صفحه امتیاز/توصیه/آمار رضایتِ محصولاتش را
 * نشان می‌دهد — همان نظری که مشتری‌ها در صفحهٔ محصول می‌خوانند.
 *
 * گیت: seller_reviews_access (تب چندفروشندگی تنظیمات برنامه‌نویس — قانون طلایی).
 * فقط-خواندنی: تأیید/رد نظر هنوز کارِ جریان ادمین است؛ فروشنده فقط می‌بیند.
 */
class SellerReviewController extends Controller
{
    public function index()
    {
        if (! MultiVendorSettings::reviewsAccessEnabled()) {
            session()->put('toast-error', 'دسترسی به نظرات محصولات از تنظیمات غیرفعال شده است.');

            return redirect()->route('seller.dashboard');
        }

        $sellerId = (int) sellerID();

        // مبنای واحد: نظراتِ محصولاتِ این فروشنده (products.seller_id)
        $base = fn () => Review::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $sellerId));

        $accepted = (clone $base)()->where('status', 'accepted');

        // آمار رضایت از نظرات تأییدشده (همان چیزی که در صفحهٔ محصول دیده می‌شود)
        $suggestYes = (clone $accepted)->where('suggest', 'yes')->count();
        $suggestTotal = (clone $accepted)->whereIn('suggest', ['yes', 'no', 'not_sure'])->count();

        $stats = [
            // کل نظرات ثبت‌شده (هر وضعیتی)
            'total' => (clone $base)()->count(),
            // در انتظار تأیید مدیر — فروشنده می‌فهمد چه چیزی هنوز عمومی نشده
            'pending' => (clone $base)()->where('status', 'pending')->count(),
            // میانگین امتیاز نظرات تأییدشده (۰ تا ۵) — null یعنی هنوز نظری تأیید نشده
            'avg_rating' => (clone $accepted)->avg('rating'),
            // درصد توصیه به دیگران — null یعنی هنوز توصیه‌ای ثبت نشده
            'recommend_percent' => $suggestTotal > 0 ? round($suggestYes * 100 / $suggestTotal, 1) : null,
        ];

        $reviews = (clone $base)()
            ->with(['product:id,title,slug', 'user:id,first_name,last_name,username'])
            ->latest()
            ->paginate(20);

        return view('sellers-old.panel.reviews.index', compact('reviews', 'stats'));
    }
}
