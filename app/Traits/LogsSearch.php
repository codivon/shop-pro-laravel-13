<?php

namespace App\Traits;

use App\Models\Search;
use Illuminate\Http\Request;

/**
 * FIX FK violation (SQLSTATE 23000 / 1452):
 * فاز ۲۱ — لاگ جستجو فقط با گارد «web» کار می‌کند.
 * قبلاً auth()->user() از گارد پیش‌فرض می‌خواند؛ اگر گارد پیش‌فرض میزبان چیز دیگری
 * باشد (مثل adminPanel) شناسهٔ ادمین در ستون user_id می‌نشیند و چون FK به جدول
 * users وصل است، INSERT با خطای کلید خارجی شکست می‌خورد. حالا صریحاً گارد web
 * بررسی می‌شود + مدل باید واقعاً App\Models\User باشد و در دیتابیس وجود داشته باشد.
 */
trait LogsSearch
{
    /** شناسه امن کاربر جاری — فقط وقتی کاربرِ فروشگاه (گارد web) واقعاً وجود دارد */
    protected function safeSearchUserId(): ?int
    {
        $user = auth('web')->user();

        if (! $user instanceof \App\Models\User || ! $user->exists) {
            return null;
        }

        return $user->id;
    }

    protected function logProductSearch(Request $request, $products, $categories, $brand, $brand_categories)
    {
       $keyword= $request->q ? $request->q : $request->s;
       $keyword = $keyword ? $keyword : $categories->title;
        return Search::create([
            'user_id' => $this->safeSearchUserId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'keyword' => $keyword,
            'search_type' => 'products',
            'products_count' => $products->count(),
            'categories_count' => $categories->count(),
            'brands_count' => $brand ? 1 : 0,
            'has_brand' => $brand ? true : false,
            'is_ajax' => $request->ajax(),
            'searched_at' => now(),
            'filters' => null,
            'result_ids' => [
                'products' => $products->pluck('id')->toArray(),
                'categories' => $categories->pluck('id')->toArray(),
                'brand' => $brand ? $brand->id : null,
                'brand_categories' => $brand_categories ? $brand_categories->pluck('id')->toArray() : [],
            ],
        ]);
    }

    protected function logPostSearch(Request $request, $posts)
    {
        return Search::create([
            'user_id' => $this->safeSearchUserId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'keyword' => $request->key,
            'search_type' => 'posts',
            'posts_count' => $posts->total(), // تعداد کل پست‌ها
            'is_ajax' => false,
            'searched_at' => now(),
            'filters' => null,
            'result_ids' => [
                'posts' => $posts->pluck('id')->toArray(),
            ],
        ]);
    }
}
