<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Product\ProductCollection;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * فاز ۹۵ — جست‌وجوی سراسری API v1 (GET /api/v1/search).
 *
 *  • q (الزامی، حداقل ۲ نویسه) — products با apiFilter (پارامتر search)
 *  • خروجی یکپارچه: محصولات صفحه‌بندی‌شده + دسته‌بندی‌ها + مقالات + شمارنده‌ها
 *  • ثبت لاگ جست‌وجو عمداً در محدودهٔ این فاز نیست.
 */
class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'q.required' => 'عبارت جست‌وجو الزامی است.',
            'q.min' => 'عبارت جست‌وجو باید حداقل ۲ نویسه باشد.',
        ]);

        $q = trim($request->input('q'));
        $perPage = (int) ($request->input('per_page') ?: 20);

        // apiFilter پارامتر search را می‌خواند → q به search ترجمه می‌شود
        $request->merge(['search' => $q]);

        $products = Product::with('category')
            ->published()
            ->orderByStock()
            ->apiFilter()
            ->latest()
            ->paginate($perPage);

        $categories = Category::query()
            ->published()
            ->where('title', 'like', '%' . $q . '%')
            ->orderBy('ordering')
            ->take(5)
            ->get(['id', 'title', 'slug', 'category_id']);

        $posts = Post::query()
            ->published()
            ->where('title', 'like', '%' . $q . '%')
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'slug']);

        return $this->apiResponse([
            'success' => true,
            'message' => 'نتایج جست‌وجو',
            'result'  => [
                'query'            => $q,
                'products'         => (new ProductCollection($products))->response()->getData(),
                'products_count'   => $products->total(),
                'categories'       => $categories,
                'categories_count' => $categories->count(),
                'posts'            => $posts,
                'posts_count'      => $posts->count(),
            ],
        ]);
    }
}
