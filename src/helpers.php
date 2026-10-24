<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Widget;
use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\WidgetRegistry;
use App\Models\Tag;

/**
 * FIX مقیاس: middleware throttle فقط در production — در تست/سندباکس محدودیتی نیست.
 */
function production_throttle(string $limiter): string
{
    return app()->environment('production') ? 'throttle:' . $limiter : '';
}

/**
 * شناسه‌های دسته‌های فیلتر ویجت — همهٔ دسته‌ها در «یک کوئری» + زیردرخت از مپ کش‌شده
 * (جایگزین Category::find + allChildCategories بازگشتی به‌ازای هر دسته = N+1)
 */
function front_widget_category_ids($category_filter, bool $withSubCategories = true): array
{
    if (! $category_filter || ! $category_filter->hasCategory()) {
        return [];
    }

    $ids = $category_filter->categories()->pluck('categories.id');

    $categoriesById = Category::whereIn('id', $ids)->get()->keyBy('id');

    $categories = [];

    foreach ($ids as $id) {
        $category = $categoriesById->get($id);

        if (! $category) {
            continue;
        }

        if ($withSubCategories) {
            $categories = array_merge($categories, front_category_subtree_ids($category));
        } else {
            $categories[] = $category->id;
        }
    }

    return array_values(array_unique($categories));
}

/**
 * فرزندان منتشرشدهٔ یک دسته — اگر relation ازقبل لود شده باشد (منو/ویجت)
 * بدون کوئری پاسخ می‌دهد؛ وگرنه یک کوئری (رفتار قدیم getCategories).
 */
function front_category_children($category)
{
    if (! $category) {
        return collect();
    }

    if ($category->relationLoaded('categories')) {
        return $category->categories->where('published', true)->values();
    }

    return $category->categories()->published()->get();
}

function get_widget($widget)
{
    if (WidgetRegistry::has($widget->key)) {
        return WidgetRegistry::handle($widget->key, $widget);
    }

    // FIX کارایی مقیاس: خروجی ویجت‌های صفحه اصلی تا ۵ دقیقه کش می‌شود.
    // کلید شامل updated_at ویجت است تا ویرایش ویجت در پنل، بلافاصله کش را باطل کند.
    $cacheKey = 'front.widget.' . $widget->id . '.' . ($widget->updated_at ? $widget->updated_at->timestamp : 0);

    return Cache::remember($cacheKey, 300, function () use ($widget) {
        return get_widget_uncached($widget);
    });
}

function get_widget_uncached($widget)
{
    $variables = [];

    switch ($widget->key) {

        case 'fullscreen-slider': {
            // سانیار — فاز B: اسلایدرهای بدون زبان (lang=NULL) هم نمایش داده می‌شوند؛
            // detectLang سخت‌گیر میزبان آن‌ها را حذف می‌کند (همان کلاس باگ فاز ۳۰ پنل).
            $variables['fullscreen_slider'] = Slider::query()
                ->where(function ($query) {
                    $query->whereNull('lang')->orWhere('lang', app()->getLocale());
                })
                ->whereJsonContains('groups', 'fullscreen_slider')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            $variables['mobile_sliders'] = Slider::query()
                ->where(function ($query) {
                    $query->whereNull('lang')->orWhere('lang', app()->getLocale());
                })
                ->whereJsonContains('groups', 'mobile_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            break;
        }

        case 'main-slider': {
            $variables['main_sliders'] = Slider::whereJsonContains('groups', 'main_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            $variables['mobile_sliders'] = Slider::whereJsonContains('groups', 'mobile_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            $variables['index_slider_banners'] = Banner::whereJsonContains('groups', 'index_slider_banners')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take(2)
                ->get();

            // سانیار: اسلایدر عمودی «پیشنهاد شگفت‌انگیز» کنار اسلایدر اصلی
            $variables['moment_products'] = Product::query()
                ->with('lowestPrice')
                ->select('id', 'title', 'title_en', 'type', 'category_id', 'slug', 'image', 'special', 'image_alt')
                ->published()
                ->discount()
                ->orderByStock()
                ->latest()
                ->take($widget->option('moment_number', 8))
                ->get();

            break;
        }

        case 'middle-banners': {
            $variables['index_middle_banners'] = Banner::whereJsonContains('groups', 'index_middle_banners')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->get();

            break;
        }

        case 'middle-banners-2': {
            $variables['index_middle_2_banners'] = Banner::whereJsonContains('groups', 'index_middle_2_banners')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->get();

            break;
        }

        case 'middle-banners-4': {
            $variables['index_middle_4_banners'] = Banner::whereJsonContains('groups', 'index_middle_4_banners')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->get();

            break;
        }

        case 'coworker-sliders': {
            $variables['coworker_sliders'] = Slider::whereJsonContains('groups', 'coworker_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering')
                ->take($widget->option('number', 2))
                ->get();

            break;
        }

        case 'sevices-sliders': {
            $variables['sevices_sliders'] = Slider::whereJsonContains('groups', 'sevices_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering')
                ->take($widget->option('number', 2))
                ->get();

            break;
        }

        case 'categories': {
            $ids             = [];
            $category_filter = $widget->options->where('key', 'categories')->first();

            if ($category_filter && $category_filter->hasCategory()) {
                $ids = $category_filter->categories()->pluck('categories.id');
            }

            $variables['categories'] = Category::published()
                ->orderBy('ordering')
                ->whereIn('id', $ids)
                ->get();

            break;
        }

        case 'post-categories': {
            $ids             = [];
            $category_filter = $widget->options->where('key', 'post_categories')->first();

            if ($category_filter && $category_filter->hasCategory()) {
                $ids = $category_filter->categories()->pluck('categories.id');
            }
            $variables['categories'] = Category::published()
                ->orderBy('ordering')
                ->whereIn('id', $ids)
                ->get();

            break;
        }

        case 'posts': {
            $posts = Post::query();

            $category_filter = $widget->options->where('key', 'categories')->first();
            if ($ids = front_widget_category_ids($category_filter)) {
                $posts->whereIn('category_id', $ids);
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'latest': {
                    $posts->latest();
                    break;
                }
                case 'view': {
                    $posts->orderBy('view', 'desc');
                    break;
                }
            }

            $posts->published()->latest()->take($widget->option('number', 10));

            $variables['posts'] = $posts->get();

            break;
        }

        case 'products-default-block':
        case 'products-moment-block':
        case 'products-colorful-block': {
            // سانیار — فاز B: اسلاید «پیشنهاد شگفت‌انگیز» علاوه بر قیمت، به توضیح کوتاه و
            // مشخصات ویژه (special) محصول هم نیاز دارد؛ بقیه بلوک‌ها مثل قبل سبک می‌مانند.
            $isMomentBlock = ($widget->key === 'products-moment-block');

            $products = Product::query()
                ->with('lowestPrice', 'category:id,title,slug,type');

            if ($isMomentBlock) {
                $products->with([
                    'specifications' => function ($query) {
                        $query->wherePivot('special', true);
                    },
                ]);
            }

            $columns = ['id', 'title', 'title_en', 'type', 'category_id', 'slug', 'image', 'special', 'image_alt'];
            if ($isMomentBlock) {
                $columns[] = 'description';
            }

            $products->select(...$columns);

            $category_filter = $widget->options->where('key', 'categories')->first();
            if ($ids = front_widget_category_ids($category_filter, $widget->option('sub_category_products', 'yes') == 'yes')) {
                $products->whereIn('category_id', $ids);
            }

            switch ($widget->option('order_by_stock', 'yes')) {
                case 'yes': {
                    $products->orderByStock();
                    break;
                }
            }

            switch ($widget->option('products_type', 'all')) {
                case 'discount': {
                    $products->discount();
                    break;
                }
                case 'special': {
                    $products->special();
                    break;
                }
                case 'moment': {
                    $products->special();
                    break;
                }
            }

            switch ($widget->option('inventory_status', 'all')) {
                case 'available': {
                    $products->available();
                    break;
                }
                case 'unavailable': {
                    $products->unavailable();
                    break;
                }
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'latest': {
                    $products->latest();
                    break;
                }
                case 'sell': {
                    $products->orderBy('sell', 'desc');
                    break;
                }
                case 'view': {
                    $products->orderBy('view', 'desc');
                    break;
                }
                case 'cheapest': {
                    $products->orderByPrice('asc');
                    break;
                }
                case 'expensivest': {
                    $products->orderByPrice('desc');
                    break;
                }
            }

            $products->published()->latest()->take($widget->option('number', 10));

            $variables['products'] = $products->get();

            break;
        }

        case 'posts-default-block': {
            $posts = Post::query()
                ->with('category:id,title,slug');

            $category_filter = $widget->options->where('key', 'post_categories')->first();
            if ($category_filter && $category_filter->hasCategory()) {
                if ($ids = front_widget_category_ids($category_filter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $posts->whereIn('category_id', $ids);
                }
            }

            switch ($widget->option('posts_type', 'all')) {
                case 'is_editor_pick': {
                    $posts->where('is_editor_pick', true);
                    break;
                }
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'latest': {
                    $posts->latest();
                    break;
                }
                case 'oldest': {
                    $posts->oldest();
                    break;
                }
                case 'view': {
                    $posts->orderBy('view', 'desc');
                    break;
                }
                case 'random': {
                    $posts->inRandomOrder();
                    break;
                }
            }

            $posts->published()->take($widget->option('number', 10));

            $variables['posts'] = $posts->get();

            break;
        }

        case 'posts-three-box-block': {
            $posts = Post::query()
                ->with('category:id,title,slug');

            $category_filter = $widget->options->where('key', 'post_categories')->first();
            if ($category_filter && $category_filter->hasCategory()) {
                if ($ids = front_widget_category_ids($category_filter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $posts->whereIn('category_id', $ids);
                }
            }

            switch ($widget->option('posts_type', 'all')) {
                case 'is_editor_pick': {
                    $posts->where('is_editor_pick', true);
                    break;
                }
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'latest': {
                    $posts->latest();
                    break;
                }
                case 'oldest': {
                    $posts->oldest();
                    break;
                }
                case 'view': {
                    $posts->orderBy('view', 'desc');
                    break;
                }
                case 'random': {
                    $posts->inRandomOrder();
                    break;
                }
            }

            $posts->published()->take(3);

            $variables['posts'] = $posts->get();

            break;
        }

        case 'posts-big-box-block': {
            $posts = Post::query()
                ->with('category:id,title,slug');

            $category_filter = $widget->options->where('key', 'post_categories')->first();
            if ($category_filter && $category_filter->hasCategory()) {
                if ($ids = front_widget_category_ids($category_filter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $posts->whereIn('category_id', $ids);
                }
            }

            switch ($widget->option('posts_type', 'all')) {
                case 'is_editor_pick': {
                    $posts->where('is_editor_pick', true);
                    break;
                }
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'latest': {
                    $posts->latest();
                    break;
                }
                case 'oldest': {
                    $posts->oldest();
                    break;
                }
                case 'view': {
                    $posts->orderBy('view', 'desc');
                    break;
                }
                case 'random': {
                    $posts->inRandomOrder();
                    break;
                }
            }

            $posts->published()->take(6);

            $variables['posts'] = $posts->get();

            break;
        }

        case 'posts-tags': {
            $tags = Tag::query()
                ->select('tags.*')
                ->whereHas('posts', function ($query) {
                    $query->published();
                })
                ->withCount(['posts' => function ($query) {
                    $query->published();
                }]);

            switch ($widget->option('sort_type', 'most_used')) {
                case 'most_used': {
                    $tags->orderBy('posts_count', 'desc');
                    break;
                }
                case 'latest': {
                    $tags->latest();
                    break;
                }
                case 'oldest': {
                    $tags->oldest();
                    break;
                }
                case 'view': {
                    $tags->orderBy('view_count', 'desc');
                    break;
                }
                case 'random': {
                    $tags->inRandomOrder();
                    break;
                }
            }

            $ordering = $widget->option('ordering', 'asc');
            if ($widget->option('sort_type', 'most_used') != 'random') {
                $tags->orderBy('name', $ordering);
            }

            $tags->take($widget->option('number', 10));

            $variables['tags'] = $tags->get();

            break;
        }

        case 'faqs':
            $variables['faqs'] = \App\Models\Faq::where('published', true)
                ->take($widget->option('number', 10))
                ->orderBy('order', 'asc')
                ->get();
            break;
    }

    return $variables;
}

/**
 * مپ id => parent_id برای «همه» دسته‌ها — کش دائمی.
 * زیردرخت هر دسته در حافظه از روی این مپ محاسبه می‌شود (بدون کوئری بازگشتی).
 * با تغییر دسته‌ها (CategoryObserver) ابطال می‌شود.
 */
function front_category_map(): array
{
    return Cache::rememberForever('front.category.map', function () {
        return Category::query()
            ->select('id', 'category_id')
            ->get()
            ->mapWithKeys(fn ($category) => [$category->id => $category->category_id === null ? null : (int) $category->category_id])
            ->all();
    });
}

/**
 * شناسه‌های زیردرخت یک دسته (شامل خودش) — O(n) در حافظه، بدون کوئری بازگشتی N+1.
 */
function front_category_subtree_ids(Category $category): array
{
    $children = [];

    foreach (front_category_map() as $id => $parentId) {
        if ($parentId !== null && $parentId !== 0) {
            $children[$parentId][] = (int) $id;
        }
    }

    $rootId = (int) $category->id;
    $ids = [$rootId];
    $stack = [$rootId];

    while ($stack !== []) {
        $current = array_pop($stack);

        foreach ($children[$current] ?? [] as $childId) {
            $ids[] = $childId;
            $stack[] = $childId;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * شمارش محصولات منتشرشدهٔ هر دسته (به‌همراه زیردسته‌ها) در «یک کوئری» + کش.
 * جایگزین N+1 خط‌به‌خطِ $category->allPublishedProducts()->count() در ویجت دسته‌ها.
 */
function front_category_published_counts($categories): array
{
    $categories = $categories instanceof \Illuminate\Support\Collection ? $categories : collect($categories);
    $rootIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

    if ($rootIds === []) {
        return [];
    }

    $version = Cache::get('front.categories.version', 0);
    $cacheKey = 'front.category.counts.' . md5(json_encode($rootIds)) . '.' . $version;

    return Cache::remember($cacheKey, 600, function () use ($rootIds) {
        $children = [];

        foreach (front_category_map() as $id => $parentId) {
            if ($parentId !== null && $parentId !== 0) {
                $children[$parentId][] = (int) $id;
            }
        }

        // همهٔ گره‌های زیر ریشه‌های خواسته‌شده
        $scopeIds = [];
        $stack = $rootIds;

        while ($stack !== []) {
            $current = array_pop($stack);
            $scopeIds[] = $current;

            foreach ($children[$current] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        $counts = DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->join('categories as c', 'c.id', '=', 'cp.category_id')
            ->where('p.published', 1)
            ->where('c.published', 1)
            ->whereIn('cp.category_id', $scopeIds)
            ->groupBy('cp.category_id')
            ->selectRaw('cp.category_id, COUNT(DISTINCT cp.product_id) as total')
            ->pluck('total', 'cp.category_id');

        $result = [];

        foreach ($rootIds as $rootId) {
            $sum = 0;
            $stack = [$rootId];
            $seen = [];

            while ($stack !== []) {
                $current = array_pop($stack);

                if (isset($seen[$current])) {
                    continue;
                }
                $seen[$current] = true;
                $sum += (int) ($counts[$current] ?? 0);

                foreach ($children[$current] ?? [] as $childId) {
                    $stack[] = $childId;
                }
            }

            $result[$rootId] = $sum;
        }

        return $result;
    });
}

/**
 * مقادیر فیلتر مشخصات (Specification) سایدبار دسته — با زیرکوئری ایندکس‌خور
 * به‌جای لیست غول‌پیکر id محصولات + کش ۵ دقیقه‌ای.
 */
function front_filter_spec_values(Category $category, $filter): array
{
    $treeIds = front_category_subtree_ids($category);
    $cacheKey = 'front.filter.spec.' . $filter->id . '.' . md5(json_encode($treeIds));

    return Cache::remember($cacheKey, 300, function () use ($treeIds, $filter) {
        $values = DB::table('product_specification')
            ->whereIn('product_id', function ($query) use ($treeIds) {
                $query->select('category_product.product_id')
                    ->from('category_product')
                    ->whereIn('category_product.category_id', $treeIds);
            })
            ->where('specification_id', $filter->filterable_id)
            ->pluck('value')
            ->unique()
            ->values()
            ->toArray();

        return get_separated_values($values, $filter->separator);
    });
}

/**
 * مقادیر فیلتر ویژگی‌ها (AttributeGroup) سایدبار دسته — زیرکوئری + کش ۵ دقیقه‌ای.
 */
function front_filter_attribute_values(Category $category, $filter)
{
    $treeIds = front_category_subtree_ids($category);
    $cacheKey = 'front.filter.attributes.' . $filter->id . '.' . md5(json_encode($treeIds));

    return Cache::remember($cacheKey, 300, function () use ($treeIds, $filter) {
        $attributeIds = DB::table('attribute_price')
            ->whereIn('price_id', function ($query) use ($treeIds) {
                $query->select('prices.id')
                    ->from('prices')
                    ->whereIn('prices.product_id', function ($query2) use ($treeIds) {
                        $query2->select('category_product.product_id')
                            ->from('category_product')
                            ->whereIn('category_product.category_id', $treeIds);
                    });
            })
            ->pluck('attribute_id');

        return \App\Models\Attribute::query()
            ->with('group')
            ->where('attribute_group_id', $filter->filterable_id)
            ->whereIn('id', $attributeIds)
            ->get();
    });
}

/**
 * شرط WHERE جستجوی محصولات با استفاده از ایندکس FULLTEXT (در صورت وجود)
 * و فالبک LIKE فقط روی title برای توکن‌های خیلی کوتاه.
 * هرگز description را LIKE نمی‌زند (full-scan در میلیون رکورد).
 */
function front_search_match($query, string $term): void
{
    $term = trim($term);
    $like = '%' . $term . '%';

    // نکتهٔ مهم: ایندکس FULLTEXT ایندباکس فقط هنگام COMMIT به‌روز می‌شود؛
    // در محیط تست (تراکنش باز) ردیف‌های تازه درج‌شده با MATCH دیده نمی‌شوند —
    // پس در تست از LIKE استفاده می‌کنیم؛ در production مسیر ایندکس FULLTEXT فعال است.
    $useFulltext = ! app()->environment('testing');

    if ($useFulltext) {
        static $fulltextAvailable = null;

        if ($fulltextAvailable === null) {
            $fulltextAvailable = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'products')
                ->where('index_type', 'FULLTEXT')
                ->count() > 0;
        }

        if ($fulltextAvailable) {
            // حذف اپراتورهای boolean-mode از ورودی کاربر
            $safe = trim(preg_replace('/[+\-<>()~*@"\'\\\\]/u', ' ', $term));
            $tokens = preg_split('/\s+/u', $safe) ?: [];
            $hasLongToken = false;

            foreach ($tokens as $token) {
                if (mb_strlen($token) >= 3) {
                    $hasLongToken = true;
                    break;
                }
            }

            if ($safe !== '' && $hasLongToken) {
                // عبارت دقیق + پیشوندِ آخرین توکن (معادل رفتاری LIKE ولی روی ایندکس)
                $query->whereRaw(
                    'MATCH (products.title, products.title_en, products.description) AGAINST (? IN BOOLEAN MODE)',
                    ['"' . str_replace('"', ' ', $safe) . '" ' . str_replace('"', ' ', $safe) . '*']
                );

                return;
            }
        }
    }

    $query->where(function ($query2) use ($like) {
        $query2->where('products.title', 'like', $like)
            ->orWhere('products.title_en', 'like', $like);
    });
}

/**
 * سانیار فاز C — برندهای دارای محصول در زیردرخت یک دسته (برای نوار برندهای صفحه دسته).
 * کش ۱۰ دقیقه‌ای مثل بقیه ویترین؛ ورودی ریشه‌ی درخت و خروجی کالکشن برندها (id, name, slug, image).
 */
function saniar_category_brands(Category $category, int $limit = 20)
{
    $treeIds = front_category_subtree_ids($category);

    if ($treeIds === []) {
        return collect();
    }

    $version = Cache::get('front.categories.version', 0);
    $cacheKey = 'saniar.category.brands.' . md5(json_encode($treeIds)) . '.' . $version . '.' . $limit;

    return Cache::remember($cacheKey, 600, function () use ($treeIds, $limit) {
        return Brand::select('brands.id', 'brands.name', 'brands.slug', 'brands.image')
            ->join('products', function ($join) use ($treeIds) {
                $join->on('products.brand_id', '=', 'brands.id')
                    ->where('products.published', 1)
                    ->whereIn('products.id', function ($q) use ($treeIds) {
                        $q->select('category_product.product_id')
                            ->from('category_product')
                            ->whereIn('category_product.category_id', $treeIds);
                    });
            })
            ->groupBy('brands.id', 'brands.name', 'brands.slug', 'brands.image')
            ->orderBy('brands.name')
            ->limit($limit)
            ->get();
    });
}

function highest_banner()
{
    return Banner::whereJsonContains('groups', 'index_highest_banner')
        ->where('published', true)
        ->orderBy('ordering')
        ->get();
}


function widget_seeder()
{
    $theme = current_theme_name();

    if (Widget::where('theme', $theme)->exists()) {
        return;
    }

    // ویجت‌های صفحه اصلی — چیدمان پیش‌فرض قالب سانیار
    $homeWidgets = [
        [
            'widget' => ['title' => 'اسلایدر اصلی و پیشنهاد شگفت‌انگیز', 'key' => 'main-slider', 'theme' => $theme, 'ordering' => 1],
            'options' => [['key' => 'number', 'value' => '5'], ['key' => 'ordering', 'value' => 'asc'], ['key' => 'moment_number', 'value' => '8']]
        ],
        [
            'widget' => ['title' => 'خدمات فروشگاه', 'key' => 'sevices-sliders', 'theme' => $theme, 'ordering' => 2],
            'options' => [['key' => 'number', 'value' => '5']]
        ],
        [
            'widget' => ['title' => 'لوگو همکاران و برندها', 'key' => 'coworker-sliders', 'theme' => $theme, 'ordering' => 3],
            'options' => [['key' => 'number', 'value' => '10']]
        ],
        [
            'widget' => ['title' => 'دسته بندی ها', 'key' => 'categories', 'theme' => $theme, 'ordering' => 4],
            'options' => [
                ['key' => 'categories', 'value' => 'on', 'type' => 'product_categories', 'categories' => Category::where(['show_in_index' => 1, 'type' => 'productcat'])->pluck('id')]
            ]
        ],
        [
            'widget' => ['title' => 'پرفروش‌ترین محصولات', 'key' => 'products-default-block', 'theme' => $theme, 'ordering' => 5],
            'options' => [
                ['key' => 'title', 'value' => 'پرفروش‌ترین محصولات'],
                ['key' => 'inventory_status', 'value' => 'all'],
                ['key' => 'sort_type', 'value' => 'sell'],
                ['key' => 'order_by_stock', 'value' => 'yes'],
                ['key' => 'link', 'value' => '/product/specials'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
                ['key' => 'sub_category_products', 'value' => 'yes'],
                ['key' => 'number', 'value' => '10'],
            ]
        ],
        [
            'widget' => ['title' => 'بنر تکی', 'key' => 'middle-banners', 'theme' => $theme, 'ordering' => 6],
            'options' => [['key' => 'number', 'value' => '1'], ['key' => 'place', 'value' => 'index_banners_place1']]
        ],
        [
            'widget' => ['title' => 'جدیدترین محصولات', 'key' => 'products-default-block', 'theme' => $theme, 'ordering' => 7],
            'options' => [
                ['key' => 'title', 'value' => 'جدیدترین محصولات'],
                ['key' => 'inventory_status', 'value' => 'all'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'order_by_stock', 'value' => 'no'],
                ['key' => 'link', 'value' => '/product/specials'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
                ['key' => 'sub_category_products', 'value' => 'yes'],
                ['key' => 'number', 'value' => '10'],
            ]
        ],
        [
            'widget' => ['title' => 'بنر چهارتایی', 'key' => 'middle-banners-4', 'theme' => $theme, 'ordering' => 8],
            'options' => [['key' => 'place', 'value' => 'index_banners_place1'], ['key' => 'number', 'value' => '4']]
        ],
        [
            'widget' => ['title' => 'محصولات تخفیف دار', 'key' => 'products-moment-block', 'theme' => $theme, 'ordering' => 9],
            'options' => [
                ['key' => 'title', 'value' => 'محصولات شگفت‌انگیز'],
                ['key' => 'products_type', 'value' => 'discount'],
                ['key' => 'inventory_status', 'value' => 'all'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'order_by_stock', 'value' => 'yes'],
                ['key' => 'number', 'value' => '10'],
                ['key' => 'categories', 'value' => 'off'],
                ['key' => 'sub_category_products', 'value' => 'yes'],
                ['key' => 'link', 'value' => '/product/specials'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
            ]
        ],
        [
            'widget' => ['title' => 'آخرین مطالب وبلاگ', 'key' => 'posts', 'theme' => $theme, 'ordering' => 10],
            'options' => [['key' => 'number', 'value' => '10'], ['key' => 'sort_type', 'value' => 'latest']]
        ],
        [
            'widget' => ['title' => 'سوالات متداول', 'key' => 'faqs', 'theme' => $theme, 'ordering' => 11],
            'options' => [['key' => 'title', 'value' => 'سوالات متداول'], ['key' => 'number', 'value' => '6']]
        ],
    ];

    // ویجت‌های صفحه مقالات
    $postWidgets = [
        [
            'widget' => ['title' => 'دسته بندی ها', 'key' => 'post-categories', 'theme' => $theme, 'ordering' => 1, 'page' => 'posts'],
            'options' => [
                ['key' => 'post_categories', 'value' => 'on', 'type' => 'post_categories', 'categories' => Category::where(['show_in_index' => 1, 'type' => 'postcat'])->pluck('id')]
            ]
        ],
        [
            'widget' => ['title' => 'برگزیده ها', 'key' => 'posts-three-box-block', 'theme' => $theme, 'ordering' => 2, 'page' => 'posts'],
            'options' => [['key' => 'title', 'value' => 'برگزیده ها'], ['key' => 'sort_type', 'value' => 'latest'], ['key' => 'sub_category_post', 'value' => 'yes']]
        ],
        [
            'widget' => ['title' => 'مقالات', 'key' => 'posts-default-block', 'theme' => $theme, 'ordering' => 3, 'page' => 'posts'],
            'options' => [
                ['key' => 'title', 'value' => 'پربازدید ترین ها'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'link_title', 'value' => 'بیشتر'],
                ['key' => 'number', 'value' => '10'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'بنر چهارتایی', 'key' => 'middle-banners-4', 'theme' => $theme, 'ordering' => 4, 'page' => 'posts'],
            'options' => [['key' => 'place', 'value' => 'index_banners_place1'], ['key' => 'number', 'value' => '4']]
        ],
    ];

    // ایجاد ویجت‌ها
    createWidgets($homeWidgets);
    createWidgets($postWidgets);
}

// گزینه‌های پایه محصولات
function baseProductOptions($orderByStock = 'yes')
{
    return [
        ['key' => 'inventory_status', 'value' => 'all'],
        ['key' => 'sort_type', 'value' => 'latest'],
        ['key' => 'order_by_stock', 'value' => $orderByStock],
        ['key' => 'link', 'value' => '/product/specials'],
        ['key' => 'link_title', 'value' => 'مشاهده همه'],
        ['key' => 'image', 'value' => theme_asset("images/amazing/amazing.png")],
        ['key' => 'sub_category_products', 'value' => 'yes'],
        ['key' => 'number', 'value' => '10'],
    ];
}

// ایجاد ویجت‌ها و آپشن‌ها
function createWidgets($widgets)
{
    foreach ($widgets as $data) {
        $widget = Widget::create($data['widget']);

        foreach ($data['options'] as $option) {
            if (isset($option['type']) && in_array($option['type'], ['product_categories', 'post_categories'])) {
                $opt = $widget->options()->create(['key' => $option['key'], 'value' => $option['value']]);
                if (!empty($option['categories'])) {
                    $opt->categories()->sync($option['categories']);
                }
            } else {
                $widget->options()->create($option);
            }
        }
    }
}

function theme_first_config()
{
    widget_seeder();
}

function Get_All_Tags($tag_name)
{
    $ids=[];
    if ($tag_name=="post"){
        $tagName='App\Models\Post';
    }elseif($tag_name=="product"){
        $tagName='App\Models\Product';
    }
    $taggables=DB::table('taggables')->where('taggable_type',$tagName)->get();
    foreach ($taggables as $taggable){
        array_push($ids,$taggable->tag_id);
    }
    return \App\Models\Tag::whereIn('id',$ids)->get();
}
