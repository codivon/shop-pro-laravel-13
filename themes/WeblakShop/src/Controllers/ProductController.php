<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Post;
use App\Models\Product;
use App\Models\Category;
use App\Models\Price;
use App\Models\ProductAbilityScore;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerVariant;
use App\Models\SpecificationGroup;
use App\Notifications\Product\QuestionProductCreated;
use App\Traits\LogsSearch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Morilog\Jalali\Jalalian;

class ProductController extends Controller
{
    use LogsSearch;

    public function index(Request $request)
    {

        if ($request->q) {
            return $this->search($request);
        }
        $categories = Category::published()
            ->whereNull('Category_id')
            ->where('type', 'productcat')
            ->orderBy('ordering')
            ->get();


        return view('front::products.index', compact('categories'));
    }

    public function category(Category $category)
    {
        $this->isShowable($category);

        if ($category->childrenCategories()->published()->count()) {
            return view('front::products.category', compact('category'));
        }

        return redirect()->route('front.products.category-products', ['category' => $category]);
    }

    public function categoryProducts(Request $request, Category $category)
    {
        $this->isShowable($category);

        // FIX مقیاس: به‌جای pluck همه idهای محصول (لیست غول‌پیکر در میلیون رکورد)،
        // از زیردرخت دسته‌ها + whereHas ایندکس‌خور استفاده می‌شود
        $treeIds = front_category_subtree_ids($category);

        $products = Product::orderByStock()
            ->frontFilter($category)
            ->published()
            ->whereHas('categories', function ($query) use ($treeIds) {
                $query->whereIn('categories.id', $treeIds)->published();
            })
            ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
            ->latest()
            ->paginate(20);

        $priceScope = function ($query) use ($treeIds) {
            $query->select('category_product.product_id')
                ->from('category_product')
                ->whereIn('category_product.category_id', $treeIds);
        };

        $min_price = Price::where('stock', '>', 0)->whereIn('product_id', $priceScope)->min('price');
        $max_price = Price::where('stock', '>', 0)->whereIn('product_id', $priceScope)->max('price');

        // ثبت جستجو در دیتابیس
        $this->logProductSearch($request, $products, collect([$category]), '', '');

        return view('front::products.category-products', compact('products', 'category', 'min_price', 'max_price'));
    }

    public function categorySpecials(Category $category)
    {
        $this->isShowable($category);

        $products = Product::detectLang()
            ->published()
            ->special()
            ->whereIn('category_id', $category->allChildCategories())
            ->latest()
            ->paginate(20);

        return view('front::products.category-products', compact(
            'products',
            'category'
        ));
    }

    public function search(Request $request)
    {
        $q = trim($request->q);

        if (strlen($q) < 2) {
            return redirect()->back()->with('error', 'حداقل 2 کاراکتر وارد کنید');
        }

        // ========== محصولات ==========
        // FIX مقیاس: جستجو با ایندکس FULLTEXT (به‌جای LIKE ٪…٪ روی description که full-scan است)
        // FIX: select های قبلی ستون‌های price/discount را می‌خواند که سال‌هاست حذف شده‌اند
        // (قبلاً اتفاقی کار می‌کرد چون orderByStock قدیمی با select('*') لیست را بازنویسی می‌کرد)
        $products = Product::published()
            ->select('id', 'title', 'title_en', 'slug', 'image', 'type', 'sell', 'view', 'special', 'special_end_date', 'published', 'created_at')
            ->where(function ($query) use ($q) {
                front_search_match($query, $q);
            })
            ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
            ->orderByStock()
            ->latest()
            ->paginate(20);

        // ========== دسته‌بندی‌ها ==========
        $categories = Category::published()
            ->select('id', 'title', 'slug', 'image')
            ->where('title', 'like', '%' . $q . '%')
            ->limit(10)
            ->get();

        // ========== برندها ==========
        $brands = Brand::select('id', 'name', 'slug', 'image')
            ->where('name', 'like', '%' . $q . '%')
            ->orWhere('name_en', 'like', '%' . $q . '%')
            ->limit(10)
            ->get();

        // ========== مقالات ==========
        $posts = Post::published()
            ->select('id', 'title', 'slug', 'image', 'view', 'publish_date', 'admin_id')
            ->where('title', 'like', '%' . $q . '%')
            ->orWhere('summary', 'like', '%' . $q . '%')
            ->limit(10)
            ->get();

        // جمع‌آوری آمار
        $stats = [
            'products_count' => $products->total(),
            'categories_count' => $categories->count(),
            'brands_count' => $brands->count(),
            'posts_count' => $posts->count(),
        ];

        return view('front::products.search', compact('q', 'products', 'categories', 'brands', 'posts', 'stats'));
    }

    public function specials()
    {
        $products = Product::detectLang()
            ->published()
            ->special()
            ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
            ->latest()
            ->paginate(20);

        return view('front::products.specials', compact('products'));
    }

    public function moment()
    {
        $products = Product::published()
            ->where('moment', true)
            ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
            ->latest()
            ->paginate(20);
        return view('front::products.specials', compact('products'));
    }

    public function discount()
    {
        $products = Product::published()
            ->available()
            ->discount()
            ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
            ->latest()
            ->paginate(20);

        return view('front::products.discounts', compact('products'));
    }

    public function ajax_search(Request $request)
    {
        $searchTerm = trim($request->q);

        if (strlen($searchTerm) < 2) {
            return response()->json(['html' => '']);
        }

        // جستجوی محصولات با اولویت
        // FIX مقیاس: WHERE با ایندکس FULLTEXT — فقط ردیف‌های منطبق CASE و مرتب‌سازی می‌شوند
        $products = DB::table('products')
            ->select('id', 'title', 'slug', 'image', 'sell')
            ->where('published', 1)
            ->where(function ($q) use ($searchTerm) {
                front_search_match($q, $searchTerm);
            })
            ->orderByRaw("CASE
            WHEN title LIKE ? THEN 1
            WHEN title LIKE ? THEN 2
            ELSE 3
        END", [$searchTerm . '%', '%' . $searchTerm . '%'])
            ->orderBy('sell', 'desc')
            ->limit(12)
            ->get();

        // دسته‌بندی‌ها
        $categories = DB::table('categories')
            ->select('id', 'title', 'slug')
            ->where('published', 1)
            ->where('title', 'like', '%' . $searchTerm . '%')
            ->limit(7)
            ->get();

        // برندها
        $brand = DB::table('brands')
            ->select('id', 'name', 'slug', 'image')
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('name_en', 'like', '%' . $searchTerm . '%');
            })
            ->first();

        // دسته‌بندی‌های برند
        $brand_categories = [];
        if ($brand) {
            $brand_categories = DB::table('categories')
                ->select('categories.id', 'categories.title', 'categories.slug')
                ->join('category_product', 'categories.id', '=', 'category_product.category_id')
                ->join('products', 'category_product.product_id', '=', 'products.id')
                ->where('products.brand_id', $brand->id)
                ->distinct()
                ->limit(7)
                ->get();
        }
        // ثبت جستجو در دیتابیس
        $this->logProductSearch($request, $products, $categories, $brand, $brand_categories);

        return response()->json([
            'html' => view('front::partials.search-result', compact('searchTerm', 'products', 'categories', 'brand', 'brand_categories', 'searchTerm'))->render()
        ]);
    }

    public function show(Product $product)
    {
        if (!$product->isShowable()) {
            abort(404);
        }
        if ($product->seller_id) {
            if (Seller::find($product->seller_id)->status_documents != "Accept") {
                abort(404);
            }
        }

        if ($product->category) {
            $related_products = Product::published()
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category->id)
                ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
                ->latest()
                ->take(6)
                ->get();
        } else {
            $related_products = Product::published()
                ->where('id', '!=', $product->id)
                ->whereNull('category_id')
                ->with(['lowestPrice', 'category:id,title,slug', 'labels'])
                ->latest()
                ->take(6)
                ->get();
        }

        $questions = Comment::whereNull('comment_id')->latest()->where(['status' => 'accepted', 'commentable_id' => $product->id, 'commentable_type' => 'App\Models\Product'])->paginate(20);

        $selected_price = $product->getPrices()->first();

        // FIX مقیاس: گروه‌های ویژگی تقریباً ثابت‌اند — ۱۰ دقیقه کش می‌شوند
        $attributeGroups = Cache::remember('front.attributeGroups', 600, function () {
            return AttributeGroup::orderBy('ordering')->get();
        });

        $similar_products_count = Product::whereNotIn('id', [$product->id])
            ->whereNotNull('spec_type_id')
            ->where('spec_type_id', $product->spec_type_id)
            ->published()
            ->count();

        $reviews = $product->reviews()->accepted()->latest()->paginate(20);

        $show_prices_chart = option('dt_show_price_change_chart', 'yes') == 'yes';

        $product->increaseViewCount();
        $favorite = [];
        if (auth()->check()) {
            $favorite = Favorite::where('user_id', auth()->user()->id)->where('product_id', $product->id)->first();
        }
        $ProductAbilityScores = ProductAbilityScore::where('product_id', $product->id)->orderBy('ordering')->get();
        $Buyerscomments = "no";

        $seller_variants = SellerVariant::where('product_id', $product->id)->get();

        // محاسبه تعداد اولیه فروشندگان برای رنگ پیش‌فرض
        $initialCountStores = 0;
        $firstColorId = null;

        // پیدا کردن اولین رنگ انتخاب شده
        $firstCheckedColor = $product->getPrices()->first()?->get_attributes()->where('attribute_group_id', $attributeGroups->where('type', 'color')->first()?->id)->first();
        if ($firstCheckedColor) {
            $firstColorId = $firstCheckedColor->id;
            $priceIds = $product->prices()->pluck('id');
            $initialCountStores = DB::table('attribute_price')
                ->where('attribute_id', $firstColorId)
                ->whereIn('price_id', $priceIds)
                ->count();
        }

        $request = new Request();

        $get_stores=[];

        if ($selected_price){
            $request->merge([
                'product_id' => $product->id,
                'color_id' => @$selected_price->attributes()->first()->id
            ]);
            if ($request->color_id){
                $get_stores=$this->get_stores_helper($request);
            }


        }

        return view('front::products.show', compact(
            'product',
            'related_products',
            'attributeGroups',
            'similar_products_count',
            'selected_price',
            'show_prices_chart',
            'ProductAbilityScores',
            'reviews',
            'Buyerscomments',
            'questions',
            'favorite',
            'seller_variants',
            'initialCountStores',
            'firstColorId',
            'get_stores'
        ));
    }

    public function download(Price $price, Request $request)
    {
        if (!$price->product->isDownload()) {
            abort(404);
        }

        if (!$price->isDownloadable()) {
            abort(403);
        }

        $mac = $request->mac;
        $time = $request->time;
        $expired = Carbon::now()->addHours(5)->getTimestamp() < $time;
        $hash = config('app.key') . $time . $price->id;

        $check = Hash::check($hash, $mac);
        $file = $price->file;

        if (!$file || !Storage::disk($file->disk)->exists('product-files/' . $file->file)) {
            return view('front::errors.errors')->with('message', 'فایل یافت نشد');
        }

        if ($check && !$expired) {
            return Storage::disk($file->disk)->download('product-files/' . $file->file);
        }

        return view('front::errors.errors')->with('message', 'متاسفانه لینک دانلود شما از کار افتاده است');
    }

    public function comments(Product $product, Request $request)
    {
        // فاز ۴۴ — حفاظت ربات‌گیر/کپچا/قفل تلاش برای دیدگاه‌ها (قابل مدیریت از تنظیمات امنیتی)
        \App\Services\CommentProtection::validateSubmission($request, 'product:'.$product->id);

        $this->validate($request, [
            'body' => 'required|string|max:1000',
            'comment_id' => [
                'nullable',
                Rule::exists('comments', 'id')->where(function ($query) {
                    $query->where('comment_id', null);
                }),
            ],
        ]);

        $isReply = !is_null($request->comment_id);
        $parentComment = null;
        $parentCommentText = '';

        if ($isReply) {
            $parentComment = Comment::find($request->comment_id);
            $parentCommentText = mb_substr($parentComment->body ?? '', 0, 50);
        }

        $comment = $product->comments()->create([
            'body' => $request->body,
            'comment_id' => $request->comment_id,
            'user_id' => auth()->user()->id
        ]);

        $isAdmin = false;

        if (auth('adminPanel')->user()) {
            $comment->update([
                'status' => 'accepted'
            ]);
            $isAdmin = true;
        }

        $admins = Admin::all();
        Notification::send($admins, new QuestionProductCreated($comment));

        // ثبت لاگ
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $product->title ?? "#{$product->id}";
        $commentExcerpt = mb_substr($comment->body, 0, 50);

        $action = $isReply ? 'reply_question' : 'ask_question';

        $properties = [
            'action' => $action,
            'product_title' => $productTitle,
            'product_id' => $product->id,
            'comment_id' => $comment->id,
            'comment_excerpt' => $commentExcerpt,
            'status' => $isAdmin ? 'accepted' : 'pending',
            'ip' => request()->ip()
        ];

        if ($isReply && $parentComment) {
            $properties['parent_comment_id'] = $parentComment->id;
            $properties['parent_comment_excerpt'] = $parentCommentText;
        }

        $logMessage = $isReply
            ? "{$userName} به سوال «{$parentCommentText}» برای محصول «{$productTitle}» پاسخ داد"
            : "{$userName} سوال جدیدی برای محصول «{$productTitle}» مطرح کرد";

        activity()
            ->performedOn($product)
            ->causedBy(auth()->user())
            ->event($isReply ? 'replied' : 'asked')
            ->withProperties($properties)
            ->log($logMessage);

        return response([
            'status' => 'success'
        ]);
    }

    public function like(Comment $comment)
    {
        $review = $comment;
        $review->likes()->updateOrCreate(
            [
                'user_id' => auth()->user()->id
            ],
            [
                'type' => 'like'
            ],
        );

        $review->refreshLikesCount();

        // ثبت لاگ لایک
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $comment->commentable->title ?? "#{$comment->commentable_id}";
        $commentExcerpt = mb_substr($comment->body, 0, 50);
        $isReply = !is_null($comment->comment_id);

        $logMessage = $isReply
            ? "{$userName} به پاسخ «{$commentExcerpt}» برای محصول «{$productTitle}» لایک داد"
            : "{$userName} به سوال «{$commentExcerpt}» برای محصول «{$productTitle}» لایک داد";

        activity()
            ->performedOn($comment)
            ->causedBy(auth()->user())
            ->event('liked')
            ->withProperties([
                'action' => 'like_comment',
                'product_title' => $productTitle,
                'product_id' => $comment->commentable_id,
                'comment_id' => $comment->id,
                'comment_excerpt' => $commentExcerpt,
                'is_reply' => $isReply,
                'ip' => request()->ip()
            ])
            ->log($logMessage);

        return response()->json(['review' => $review]);
    }

    public function dislike(Comment $comment)
    {
        $review = $comment;
        $review->likes()->updateOrCreate(
            [
                'user_id' => auth()->user()->id
            ],
            [
                'type' => 'dislike'
            ],
        );

        $review->refreshLikesCount();

        // ثبت لاگ دیسلایک
        $userName = auth()->user()->full_name ?? auth()->user()->name ?? 'کاربر';
        $productTitle = $comment->commentable->title ?? "#{$comment->commentable_id}";
        $commentExcerpt = mb_substr($comment->body, 0, 50);
        $isReply = !is_null($comment->comment_id);

        $logMessage = $isReply
            ? "{$userName} به پاسخ «{$commentExcerpt}» برای محصول «{$productTitle}» دیسلایک داد"
            : "{$userName} به سوال «{$commentExcerpt}» برای محصول «{$productTitle}» دیسلایک داد";

        activity()
            ->performedOn($comment)
            ->causedBy(auth()->user())
            ->event('disliked')
            ->withProperties([
                'action' => 'dislike_comment',
                'product_title' => $productTitle,
                'product_id' => $comment->commentable_id,
                'comment_id' => $comment->id,
                'comment_excerpt' => $commentExcerpt,
                'is_reply' => $isReply,
                'ip' => request()->ip()
            ])
            ->log($logMessage);

        return response()->json(['review' => $review]);
    }


    public function prices(Product $product, Request $request)
    {
        $request->validate([
            'groups' => 'required|array',
            'groups.*' => 'required|exists:attributes,id'
        ]);

        $query = $product->getPrices();
        $count = 0;
        $attributeGroups = AttributeGroup::orderBy('ordering')->get();

        do {
            $query = $query->whereHas('get_attributes', function ($q) use ($request, $count) {
                $q->where('attribute_id', $request->groups[$count]);
            });

            $query2 = clone $query;

            if (isset($request->groups[$count + 1])) {
                $query2 = $query2->whereHas('get_attributes', function ($q) use ($request, $count) {
                    $q->where('attribute_id', $request->groups[$count + 1]);
                })->first();
            }

            if (!$query2 || !isset($request->groups[$count + 1])) {
                break;
            }

            $count++;
        } while (true);

        $selected_price = $query->first();

        $request = new Request();
        $request->merge([
            'product_id' => $product->id,
            'color_id' => $selected_price->attributes()->first()->id
        ]);

        $get_stores=$this->get_stores_helper($request);

        return view('front::products.partials.product-info', compact(
            'product',
            'selected_price',
            'attributeGroups',
            'get_stores',
        ));
    }

    public function compare($product1, $product2 = null, $product3 = null)
    {
        $product1 = Product::whereHas('specType')->findOrFail($product1);
        $products[] = $product1;
        $products_id[] = $product1->id;

        $groups_id = $product1->specificationGroups()->pluck('specification_groups.id')->unique()->toArray();

        if ($product2) {
            $product2 = Product::whereNotIn('id', $products_id)->where('spec_type_id', $product1->spec_type_id)->findOrFail($product2);
            $products[] = $product2;
            $products_id[] = $product2->id;

            $groups_id = array_merge($groups_id, $product2->specificationGroups()->pluck('specification_groups.id')->unique()->toArray());
        }

        if ($product3) {
            $product3 = Product::whereNotIn('id', $products_id)->where('spec_type_id', $product1->spec_type_id)->findOrFail($product3);
            $products[] = $product3;

            $groups_id = array_merge($groups_id, $product3->specificationGroups()->pluck('specification_groups.id')->unique()->toArray());
        }

        $groups_id = array_unique($groups_id);
        $products = collect($products);

        $groups = SpecificationGroup::whereIn('id', $groups_id)->orderByRaw('FIELD(id,' . implode(',', $groups_id) . ')')->get();

        return view('front::products.compare', compact('groups', 'products'));
    }

    public function similarCompare(Request $request)
    {
        $request->validate([
            'search' => 'required|string',
            'products' => 'required|array',
            'products.*' => 'required|exists:products,id',
            'current_url' => 'required|string',
        ]);

        $current_url = $request->current_url;
        $products_id = $request->products;
        $spec_type = Product::find(reset($products_id))->spec_type_id;

        $similar_products = Product::whereNotIn('id', $products_id)
            ->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%')->orWhere('title', 'like', '%' . $request->search . '%');
            })
            ->where('spec_type_id', $spec_type)
            ->orderByStock()
            ->take(20)
            ->get();

        $products = Product::whereIn('id', $products_id)->get();

        return view('front::products.partials.compare-modal', compact('similar_products', 'products', 'current_url'));
    }

    public function priceChart(Price $price)
    {
        $data = $this->getPriceData($price);

        return response()->json(['data' => $data]);
    }

    private function getPriceData($price)
    {
        $data = [];

        for ($i = 1; $i <= 30; $i++) {
            $date = Jalalian::now()->subDays($i)->format('%d %B');
            $data[$i]['date'] = $date;

            $last_date = $price->changes()
                ->whereDate('price_changes.created_at', '<=', now()->subDays($i))
                ->latest('price_changes.created_at')
                ->first();

            if (!$last_date) {
                $data[$i]['price'] = null;
                $data[$i]['discount_price'] = null;
                $data[$i]['discount'] = null;

                continue;
            }

            $last_min_price = $price->changes()->whereDate('price_changes.created_at', $last_date->created_at)->orderBy(DB::raw("`price` - (`price` * `discount` / 100)"))->first();
            $data[$i]['price'] = $last_min_price->price;
            $data[$i]['discount_price'] = $last_min_price->price - ($last_min_price->price * $last_min_price->discount / 100);
            $data[$i]['discount'] = $last_min_price->discount;
        }

        return $data;
    }

    private function isShowable(Category $category)
    {
        if ($category->type != 'productcat' || !$category->isPublished()) {
            abort(404);
        }
    }

    public function shortLink($id)
    {
        $product = Product::findOrfail($id);

        return redirect()->route('front.products.show', ['product' => $product]);
    }

    public function getCommentsAjax(Request $request)
    {
        $product = Product::find($request->product_id);
        $Buyerscomments = 'no';
        if ($request->sortComment == "new") {
            $reviews = $product->reviews()->accepted()->latest()->paginate(20);
        }
        if ($request->sortComment == "moreLike") {
            $reviews = $product->reviews()->accepted()->orderby('likes_count', 'desc')->paginate(20);
        }
        if ($request->sortComment == "Buyerscomments") {
            $Buyerscomments = "yes";
            $reviews = $product->reviews()->accepted()->latest()->paginate(20);
        }
        return view('front::products.partials.reviews-comments', ['reviews' => $reviews, 'Buyerscomments' => $Buyerscomments]);
    }

    public function getQuestionAjax(Request $request)
    {
        if ($request->sortComment == "new") {
            $questions = Comment::whereNull('comment_id')->latest()->where(['status' => 'accepted', 'commentable_id' => $request->product_id, 'commentable_type' => 'App\Models\Product'])->paginate(20);
        }
        if ($request->sortComment == "moreLike") {
            $questions = Comment::whereNull('comment_id')->orderby('likes_count', 'desc')->where(['status' => 'accepted', 'commentable_id' => $request->product_id, 'commentable_type' => 'App\Models\Product'])->paginate(20);

        }

        return view('front::components.question-answer-data', ['questions' => $questions]);
    }



    private function get_stores_helper(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'color_id' => 'required|integer'
        ]);

        $attributePrices = DB::table('attribute_price')
            ->where([
                'product_id' => $request->product_id,
                'attribute_id' => $request->color_id
            ])
            ->get();

        if ($attributePrices->isEmpty()) {
            return response()->json(['html' => '']);
        }

        // Get all price IDs in one query
        $priceIds = Price::where('product_id', $request->product_id)
            ->pluck('id')
            ->toArray();

        // Get site attribute price
        $attributePricesSite = DB::table('attribute_price')
            ->whereIn('price_id', $priceIds)
            ->whereNull('seller_id')
            ->where('attribute_id', $request->color_id)
            ->first();

        // Eager load all related data
        $productIds = $attributePrices->pluck('product_id')->unique();
        $sellerIds = $attributePrices->pluck('seller_id')->filter()->unique();
        $priceIdsFromAttr = $attributePrices->pluck('price_id')->unique();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $sellers = SellerInfo::whereIn('seller_id', $sellerIds)->get()->keyBy('seller_id');
        $prices = Price::whereIn('id', $priceIdsFromAttr)->get()->keyBy('id');

        // Get all attributes at once
        $allAttributeIds = DB::table('attribute_price')
            ->whereIn('price_id', $priceIdsFromAttr)
            ->whereIn('product_id', $productIds)
            ->pluck('attribute_id')
            ->unique();

        $attributes = Attribute::whereIn('id', $allAttributeIds)
            ->where('attribute_group_id', 2)
            ->get()
            ->keyBy('id');

        $has_stores = true;
        if ($attributePrices->count() == 1 and $attributePrices[0]->seller_id == null) {
            $has_stores = false;
        }

        return [
            'attribute_prices' => $attributePrices,
            'attribute_prices_site' => $attributePricesSite,
            'products' => $products,
            'sellers' => $sellers,
            'prices' => $prices,
            'attributes' => $attributes,
            'has_stores' => $has_stores,

        ];
    }

    public function get_stores(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'color_id' => 'required|integer'
        ]);

        $attributePrices = DB::table('attribute_price')
            ->where([
                'product_id' => $request->product_id,
                'attribute_id' => $request->color_id
            ])
            ->get();

        if ($attributePrices->isEmpty()) {
            return response()->json(['html' => '']);
        }

        // Get all price IDs in one query
        $priceIds = Price::where('product_id', $request->product_id)
            ->pluck('id')
            ->toArray();

        // Get site attribute price
        $attributePricesSite = DB::table('attribute_price')
            ->whereIn('price_id', $priceIds)
            ->whereNull('seller_id')
            ->where('attribute_id', $request->color_id)
            ->first();

        // Eager load all related data
        $productIds = $attributePrices->pluck('product_id')->unique();
        $sellerIds = $attributePrices->pluck('seller_id')->filter()->unique();
        $priceIdsFromAttr = $attributePrices->pluck('price_id')->unique();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $sellers = SellerInfo::whereIn('seller_id', $sellerIds)->get()->keyBy('seller_id');
        $prices = Price::whereIn('id', $priceIdsFromAttr)->get()->keyBy('id');

        // Get all attributes at once
        $allAttributeIds = DB::table('attribute_price')
            ->whereIn('price_id', $priceIdsFromAttr)
            ->whereIn('product_id', $productIds)
            ->pluck('attribute_id')
            ->unique();

        $attributes = Attribute::whereIn('id', $allAttributeIds)
            ->where('attribute_group_id', 2)
            ->get()
            ->keyBy('id');

        $has_stores = true;
        if ($attributePrices->count() == 1 and $attributePrices[0]->seller_id == null) {
            $has_stores = false;
        }


        return response()->json([
            'html' => view('front::products.partials.stores-template', [
                'attribute_prices' => $attributePrices,
                'attribute_prices_site' => $attributePricesSite,
                'products' => $products,
                'sellers' => $sellers,
                'prices' => $prices,
                'attributes' => $attributes
            ])->render(),
            'has_stores' => $has_stores,

        ]);
    }
}
