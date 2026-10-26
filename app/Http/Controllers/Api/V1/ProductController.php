<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreCommentRequest;
use App\Http\Resources\Api\V1\Comment\CommentCollection;
use App\Http\Resources\Api\V1\Product\ProductCollection;
use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?: 20;

        $products = Product::with('category')
            ->published()
            ->orderByStock()
            ->apiFilter()
            ->latest()
            ->paginate($per_page);

        return $this->respondWithResourceCollection(new ProductCollection($products));
    }

    public function show(Product $product)
    {
        if (!$product->isPublished()) {
            abort(404);
        }

        return $this->respondWithResource(new ProductResource($product));
    }

    /**
     * فاز ۹۵ — فرادادهٔ فیلترها (GET /api/v1/products/filters).
     *
     *  • price: کف/سقف قیمت مؤثر محصولات منتشرشده (از جدول prices با گارد موجودی)
     *  • categories: دسته‌های ریشه با تعداد فرزندان
     *  • special_count / discount_count: شمارنده‌های ویترین
     * پاسخ کم‌حجم و پایدار نگه داشته می‌شود.
     */
    public function filters()
    {
        $effectivePrice = "IF(prices.discount_expire_at IS NULL OR DATE(prices.discount_expire_at) > NOW(), COALESCE(prices.discount_price, prices.price), COALESCE(prices.regular_price, prices.price))";

        $bounds = Price::query()
            ->where('stock', '>', 0)
            ->whereHas('product', fn ($q) => $q->published())
            ->selectRaw("MIN({$effectivePrice}) AS min_price")
            ->selectRaw("MAX({$effectivePrice}) AS max_price")
            ->first();

        $categories = Category::query()
            ->published()
            ->whereNull('category_id')
            ->withCount('children')
            ->orderBy('ordering')
            ->get(['id', 'title', 'slug'])
            ->map(fn ($category) => [
                'id'             => $category->id,
                'title'          => $category->title,
                'slug'           => $category->slug,
                'children_count' => $category->children_count,
            ])->values();

        $specialCount = Product::query()->published()->special()->count();

        $discountCount = Product::query()->published()->whereHas('prices', function ($q) {
            $q->where('stock', '>', 0)
                ->where('discount', '>', 0)
                ->where(function ($q2) {
                    $q2->whereNull('discount_expire_at')->orWhere('discount_expire_at', '>', now());
                });
        })->count();

        return $this->apiResponse([
            'success' => true,
            'result'  => [
                'price'          => [
                    'min' => (int) ($bounds->min_price ?? 0),
                    'max' => (int) ($bounds->max_price ?? 0),
                ],
                'categories'     => $categories,
                'special_count'  => $specialCount,
                'discount_count' => $discountCount,
            ],
        ]);
    }

    public function comments(Product $product, Request $request)
    {
        $per_page = $request->per_page ?: 20;

        $comments = $product->comments()
            ->whereNull('comment_id')
            ->accepted()
            ->latest()
            ->paginate($per_page);

        return $this->respondWithResourceCollection(new CommentCollection($comments));
    }

    public function relatedProducts(Product $product, Request $request)
    {
        $per_page = $request->per_page ?: 20;

        $products = $product->relatedProducts()->with('category')
            ->published()
            ->orderByStock()
            ->latest()
            ->paginate($per_page);

        return $this->respondWithResourceCollection(new ProductCollection($products));
    }

    public function storeComment(Product $product, StoreCommentRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        if ($request->user()->isAdmin()) {
            $data['status'] = 'accepted';
        }

        $product->comments()->create($data);

        return $this->respondCreated(['message' => 'دیدگاه با موفقیت ثبت شد']);
    }
}
