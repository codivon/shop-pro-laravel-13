<?php

namespace App\Traits;

use App\Models\Search;
use Illuminate\Http\Request;

/**
 * FIX FK violation (SQLSTATE 23000 / 1452):
 * auth()->id() مقدار ذخیره‌شده در session را برمی‌گرداند؛ اگر کاربر حذف شده باشد
 * این id در جدول users وجود ندارد و INSERT با خطای کلید خارجی شکست می‌خورد.
 * auth()->user()?->id مدل را واقعاً resolve می‌کند و برای کاربر حذف‌شده null می‌دهد
 * (ستون user_id nullable است و ON DELETE SET NULL دارد).
 */
trait LogsSearch
{
    /** شناسه امن کاربر جاری — فقط وقتی رکورد کاربر واقعاً وجود دارد */
    protected function safeSearchUserId(): ?int
    {
        $user = auth()->user();

        return $user?->exists ? $user->id : null;
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
