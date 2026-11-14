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

/*
 * گارد یکتایی لود helpers قالب — فاز ۱۵۲ تم سانیار
 * اگر این فایل به هر دلیل دوباره include شود (مثلاً از مسیر دیگر یا
 * provider تکراری)، توابع دوباره تعریف نمی‌شوند و از «Cannot redeclare
 * function» جلوگیری می‌شود.
 */
if (defined('SANIAR_THEME_HELPERS_LOADED')) {
    return;
}
define('SANIAR_THEME_HELPERS_LOADED', 1);

/**
 * FIX مقیاس: middleware throttle فقط در production — در تست/سندباکس محدودیتی نیست.
 */
if (!function_exists('production_throttle')) {
function production_throttle(string $limiter): string
{
    return app()->environment('production') ? 'throttle:' . $limiter : '';
}
}

/**
 * شناسه‌های دسته‌های فیلتر ویجت — همهٔ دسته‌ها در «یک کوئری» + زیردرخت از مپ کش‌شده
 * (جایگزین Category::find + allChildCategories بازگشتی به‌ازای هر دسته = N+1)
 */
if (!function_exists('front_widget_category_ids')) {
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
}

/**
 * فرزندان منتشرشدهٔ یک دسته — اگر relation ازقبل لود شده باشد (منو/ویجت)
 * بدون کوئری پاسخ می‌دهد؛ وگرنه یک کوئری (رفتار قدیم getCategories).
 */
if (!function_exists('front_category_children')) {
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
}

if (!function_exists('get_widget')) {
function get_widget($widget)
{
    /*
     * فاز ۱۵۳ — گارد null: اگر رکورد ویجت در دیتابیس نباشد (نصب تازه یا ویجت
     * غیرفعال‌شده)، برخی کنترلرها first() خالی پاس می‌دهند و دسترسی به
     * $widget->key باعث «Attempt to read property key on null» می‌شد.
     * آرایهٔ خالی برمی‌گردد تا ویوها با @if/count() بدون Fatal مدیریتش کنند.
     */
    if (empty($widget) || !isset($widget->key)) {
        return [];
    }

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
}

if (!function_exists('get_widget_uncached')) {
function get_widget_uncached($widget)
{
    $variables = [];

    switch ($widget->key) {

        case 'fullscreen-slider': {
            $variables['fullscreen_slider'] = Slider::detectLang()
                ->whereJsonContains('groups', 'fullscreen_slider')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            /* فاز ۱۶۴ — تعداد اسلایدر موبایل از تنظیمات خود ابزارک */
            $variables['mobile_sliders'] = Slider::detectLang()
                ->whereJsonContains('groups', 'mobile_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('mobile_number', $widget->option('number', 5)))
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

            /* فاز ۱۶۳ — فالبک: اگر ادمین اسلایدری در گروه main_sliders نگذاشته،
               اسلایدرهای گروه fullscreen_slider (که روی سایت موجود است) نمایش داده
               می‌شوند تا ابزار همیشه کار کند و صفحهٔ خالی دیده نشود. */
            if (count($variables['main_sliders']) === 0) {
                $variables['main_sliders'] = Slider::whereJsonContains('groups', 'fullscreen_slider')
                    ->whereJsonContains('pages', $widget->page)
                    ->where('published', true)
                    ->orderBy('ordering', $widget->option('ordering', 'asc'))
                    ->take($widget->option('number', 5))
                    ->get();
            }

            $variables['mobile_sliders'] = Slider::whereJsonContains('groups', 'mobile_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            /* فاز ۱۶۴ — تنظیمات مخصوص همین نمونه ابزارک:
               ستون محصولات شگفت‌انگیز با تایمر + دو بنر کناری هرکدام با کلید
               «بله/خیر» خودشان — تا هر نمونه main-slider چیدمان متفاوتی بگیرد */
            $variables['moment_products'] = collect();
            if ($widget->option('show_moment', 'yes') === 'yes') {
                $variables['moment_products'] = Product::query()
                    ->with('lowestPrice', 'category:id,title,slug')
                    ->select('id', 'title', 'type', 'category_id', 'slug', 'image', 'special', 'image_alt')
                    ->published()
                    ->available()
                    ->special()
                    ->orderByStock()
                    ->latest()
                    ->take(((int) $widget->option('moment_number', 6)) ?: 6)
                    ->get();
            }

            $variables['index_slider_banners'] = collect();
            if ($widget->option('show_banners', 'no') === 'yes') {
                $variables['index_slider_banners'] = Banner::whereJsonContains('groups', 'index_slider_banners')
                    ->whereJsonContains('pages', $widget->page)
                    ->where('published', true)
                    ->orderBy('ordering', $widget->option('ordering', 'asc'))
                    ->take(((int) $widget->option('banner_number', 2)) ?: 2)
                    ->get();
            }

            break;
        }

        case 'middle-banners': {
            /* فاز ۱۶۴ — گروه بنرها از تنظیمات خود ابزارک («گروه») + تعداد قابل نمایش */
            $variables['index_middle_banners'] = Banner::whereJsonContains('groups', $widget->option('place', 'index_middle_banners'))
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 1) ?: 1)
                ->get();

            break;
        }

        /* فاز ۱۶۳ — اسلایدر شگفت‌انگیز سانیار: محصولات ویژه با تایمر + قیمت/تخفیف */
        case 'amazing-slider': {
            $amazingQuery = Product::query()
                ->with(['lowestPrice', 'category:id,title,slug', 'labels:id,title'])
                ->select(
                    'id',
                    'title',
                    'title_en',
                    'short_description',
                    'type',
                    'category_id',
                    'slug',
                    'image',
                    'special',
                    'special_end_date',
                    'discount',
                    'image_alt'
                )
                ->published()
                ->available()
                ->special()
                ->orderByStock();

            $amazingCategoryFilter = $widget->options->where('key', 'categories')->first();
            if ($ids = front_widget_category_ids($amazingCategoryFilter, $widget->option('sub_category_products', 'yes') == 'yes')) {
                $amazingQuery->whereIn('category_id', $ids);
            }

            /* فاز ۱۶۵ — رفع ارور SQLSTATE[42S22] (Unknown column 'ordering' in 'order clause'):
               جدول products ستون ordering ندارد؛ مرتب‌سازی بر اساس گزینهٔ sort_type
               خود ابزارک (جدیدترین/پرفروش‌ترین/پربازدیدترین/ارزان‌ترین/گران‌ترین) */
            switch ($widget->option('sort_type', 'latest')) {
                case 'sell': {
                    $amazingQuery->orderBy('sell', 'desc');
                    break;
                }
                case 'view': {
                    $amazingQuery->orderBy('view', 'desc');
                    break;
                }
                case 'cheapest': {
                    $amazingQuery->orderByPrice('asc');
                    break;
                }
                case 'expensivest': {
                    $amazingQuery->orderByPrice('desc');
                    break;
                }
                default: {
                    $amazingQuery->latest();
                    break;
                }
            }

            $variables['amazing_products'] = $amazingQuery
                ->take((int) $widget->option('number', 6))
                ->get();

            break;
        }

        /* فاز ۱۶۳ — اسلایدر + دو بنر زیر هم کنارش (همان گروه‌های main-slider
           تا اسلایدر/بنرهای آپلودشدهٔ ادمین بدون تنظیم مجدد نمایش داده شوند) */
        case 'slider-side-banners': {
            $variables['main_sliders'] = Slider::whereJsonContains('groups', 'main_sliders')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 5))
                ->get();

            /* فالبک گروه fullscreen_slider — ابزار بدون آپلود جدید کار کند */
            if (count($variables['main_sliders']) === 0) {
                $variables['main_sliders'] = Slider::whereJsonContains('groups', 'fullscreen_slider')
                    ->whereJsonContains('pages', $widget->page)
                    ->where('published', true)
                    ->orderBy('ordering', $widget->option('ordering', 'asc'))
                    ->take($widget->option('number', 5))
                    ->get();
            }

            /* فاز ۱۶۴ — تعداد بنر کناری از تنظیمات خود ابزارک (پیش‌فرض ۲ بنر زیر هم) */
            $variables['index_slider_banners'] = Banner::whereJsonContains('groups', 'index_slider_banners')
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take((int) $widget->option('banner_number', 2) ?: 2)
                ->get();

            break;
        }

        case 'middle-banners-2': {
            /* فاز ۱۶۴ — گروه بنرها از تنظیمات خود ابزارک + تعداد قابل نمایش */
            $variables['index_middle_2_banners'] = Banner::whereJsonContains('groups', $widget->option('place', 'index_middle_2_banners'))
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 2) ?: 2)
                ->get();

            break;
        }

        case 'middle-banners-4': {
            /* فاز ۱۶۴ — گروه بنرها از تنظیمات خود ابزارک + تعداد قابل نمایش */
            $variables['index_middle_4_banners'] = Banner::whereJsonContains('groups', $widget->option('place', 'index_middle_4_banners'))
                ->whereJsonContains('pages', $widget->page)
                ->where('published', true)
                ->orderBy('ordering', $widget->option('ordering', 'asc'))
                ->take($widget->option('number', 4) ?: 4)
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
            $products = Product::query()
                ->with('lowestPrice', 'category:id,title,slug,type')
                ->select(
                    'id',
                    'title',
                    'type',
                    'category_id',
                    'slug',
                    'image',
                    'special',
                    'image_alt'
                );

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

        /* ==================================================================
           فاز ۱۶۶ — ویجت‌های صفحهٔ مقالات (مطابق blog.html تمپلیت سانیار)
           ================================================================== */

        /* ۱) هدر وبلاگ: دسته‌بندی‌ها + بنر + خبرهای داغ */
        case 'blog-header': {
            /* دسته‌بندی‌های مقالات (سایدبار راست) */
            $variables['blog_header_categories'] = Category::published()
                ->where('type', 'postcat')
                ->whereNull('category_id')
                ->orderBy('ordering')
                ->get(['id', 'title', 'slug', 'image']);

            /* بنر بالای خبرها (گروه بنر تکی + صفحهٔ مقالات — بدون تغییر هسته) */
            $variables['blog_header_banner'] = null;
            if ($widget->option('show_banner', 'yes') === 'yes') {
                $variables['blog_header_banner'] = Banner::whereJsonContains('groups', $widget->option('banner_group', 'index_middle_banners'))
                    ->whereJsonContains('pages', $widget->page)
                    ->where('published', true)
                    ->orderBy('ordering')
                    ->first();
            }

            /* خبرهای داغ (۳ کارت blog-news-item) */
            $headerNewsQuery = Post::query()
                ->with('category:id,title,slug')
                ->select('id', 'title', 'slug', 'image', 'post_type', 'category_id', 'view', 'created_at');

            $headerNewsFilter = $widget->options->where('key', 'post_categories')->first();
            if ($headerNewsFilter && $headerNewsFilter->hasCategory()) {
                if ($ids = front_widget_category_ids($headerNewsFilter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $headerNewsQuery->whereIn('category_id', $ids);
                }
            }

            if ($widget->option('news_editor_pick', 'no') === 'yes') {
                $headerNewsQuery->where('is_editor_pick', true);
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'view': {
                    $headerNewsQuery->orderBy('view', 'desc');
                    break;
                }
                case 'random': {
                    $headerNewsQuery->inRandomOrder();
                    break;
                }
                default: {
                    $headerNewsQuery->latest();
                    break;
                }
            }

            $variables['blog_header_news'] = $headerNewsQuery
                ->published()
                ->take((int) $widget->option('news_number', 3) ?: 3)
                ->get();

            break;
        }

        /* ۲) اسلایدر مطالب وبلاگ (پربازدیدترین/جدیدترین/...) */
        case 'blog-mostviewed-slider': {
            $blogSliderQuery = Post::query()
                ->with('category:id,title,slug')
                ->select('id', 'title', 'slug', 'image', 'post_type', 'category_id', 'view', 'created_at');

            $blogSliderFilter = $widget->options->where('key', 'post_categories')->first();
            if ($blogSliderFilter && $blogSliderFilter->hasCategory()) {
                if ($ids = front_widget_category_ids($blogSliderFilter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $blogSliderQuery->whereIn('category_id', $ids);
                }
            }

            switch ($widget->option('sort_type', 'view')) {
                case 'latest': {
                    $blogSliderQuery->latest();
                    break;
                }
                case 'oldest': {
                    $blogSliderQuery->oldest();
                    break;
                }
                case 'random': {
                    $blogSliderQuery->inRandomOrder();
                    break;
                }
                default: {
                    $blogSliderQuery->orderBy('view', 'desc');
                    break;
                }
            }

            $variables['blog_slider_posts'] = $blogSliderQuery
                ->published()
                ->take((int) $widget->option('number', 10) ?: 10)
                ->get();

            break;
        }

        /* ۳) ویدیوهای وبلاگ: گرید tv-blog-item + پلی‌لیست سایدبار */
        case 'blog-videos': {
            $gridNumber    = (int) $widget->option('grid_number', 6) ?: 6;
            $sidebarNumber = (int) $widget->option('sidebar_number', 7) ?: 7;

            $videosQuery = Post::query()
                ->with('category:id,title,slug')
                ->select('id', 'title', 'slug', 'image', 'post_type', 'category_id', 'view', 'created_at')
                ->where('post_type', 'video');

            $videosFilter = $widget->options->where('key', 'post_categories')->first();
            if ($videosFilter && $videosFilter->hasCategory()) {
                if ($ids = front_widget_category_ids($videosFilter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $videosQuery->whereIn('category_id', $ids);
                }
            }

            if ($widget->option('sort_type', 'latest') === 'view') {
                $videosQuery->orderBy('view', 'desc');
            } else {
                $videosQuery->latest();
            }

            $blogVideos = $videosQuery->published()
                ->take(max($gridNumber, $sidebarNumber))
                ->get();

            /* فالبک: اگر هیچ ویدیویی نبود، آخرین مقالات (بدون آیکون پخش) */
            if ($blogVideos->isEmpty() && $widget->option('fallback_to_latest', 'yes') === 'yes') {
                $fallbackQuery = Post::query()
                    ->with('category:id,title,slug')
                    ->select('id', 'title', 'slug', 'image', 'post_type', 'category_id', 'view', 'created_at');

                if ($videosFilter && $videosFilter->hasCategory()) {
                    if ($ids = front_widget_category_ids($videosFilter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                        $fallbackQuery->whereIn('category_id', $ids);
                    }
                }

                $blogVideos = $fallbackQuery->published()
                    ->latest()
                    ->take(max($gridNumber, $sidebarNumber))
                    ->get();
            }

            $variables['video_grid_posts']    = $blogVideos->take($gridNumber)->values();
            $variables['video_sidebar_posts'] = $blogVideos->take($sidebarNumber)->values();

            break;
        }

        /* ۴) گرید آخرین مطالب وبلاگ */
        case 'blog-latest-grid': {
            $blogGridQuery = Post::query()
                ->with('category:id,title,slug')
                ->select('id', 'title', 'slug', 'image', 'post_type', 'category_id', 'view', 'created_at');

            $blogGridFilter = $widget->options->where('key', 'post_categories')->first();
            if ($blogGridFilter && $blogGridFilter->hasCategory()) {
                if ($ids = front_widget_category_ids($blogGridFilter, $widget->option('sub_category_post', 'yes') == 'yes')) {
                    $blogGridQuery->whereIn('category_id', $ids);
                }
            }

            switch ($widget->option('sort_type', 'latest')) {
                case 'view': {
                    $blogGridQuery->orderBy('view', 'desc');
                    break;
                }
                case 'oldest': {
                    $blogGridQuery->oldest();
                    break;
                }
                case 'random': {
                    $blogGridQuery->inRandomOrder();
                    break;
                }
                default: {
                    $blogGridQuery->latest();
                    break;
                }
            }

            $variables['blog_grid_posts'] = $blogGridQuery
                ->published()
                ->take((int) $widget->option('number', 8) ?: 8)
                ->get();

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
}

/**
 * مپ id => parent_id برای «همه» دسته‌ها — کش دائمی.
 * زیردرخت هر دسته در حافظه از روی این مپ محاسبه می‌شود (بدون کوئری بازگشتی).
 * با تغییر دسته‌ها (CategoryObserver) ابطال می‌شود.
 */
if (!function_exists('front_category_map')) {
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
}

/**
 * شناسه‌های زیردرخت یک دسته (شامل خودش) — O(n) در حافظه، بدون کوئری بازگشتی N+1.
 */
if (!function_exists('front_category_subtree_ids')) {
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
}

/**
 * شمارش محصولات منتشرشدهٔ هر دسته (به‌همراه زیردسته‌ها) در «یک کوئری» + کش.
 * جایگزین N+1 خط‌به‌خطِ $category->allPublishedProducts()->count() در ویجت دسته‌ها.
 */
if (!function_exists('front_category_published_counts')) {
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
}

/**
 * مقادیر فیلتر مشخصات (Specification) سایدبار دسته — با زیرکوئری ایندکس‌خور
 * به‌جای لیست غول‌پیکر id محصولات + کش ۵ دقیقه‌ای.
 */
if (!function_exists('front_filter_spec_values')) {
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
}

/**
 * مقادیر فیلتر ویژگی‌ها (AttributeGroup) سایدبار دسته — زیرکوئری + کش ۵ دقیقه‌ای.
 */
if (!function_exists('front_filter_attribute_values')) {
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
}

/**
 * شرط WHERE جستجوی محصولات با استفاده از ایندکس FULLTEXT (در صورت وجود)
 * و فالبک LIKE فقط روی title برای توکن‌های خیلی کوتاه.
 * هرگز description را LIKE نمی‌زند (full-scan در میلیون رکورد).
 */
if (!function_exists('front_search_match')) {
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
            // FIX: بررسی FULLTEXT فقط روی MySQL/معنای معتبر دارد؛ روی SQLite/Postgres
            // جدول information_schema.statistics وجود ندارد و همین چک باعث خطای ۵۰۰
            // در صفحهٔ جستجو می‌شد — پس روی درایورهای غیر MySQL مستقیم به LIKE می‌رویم.
            try {
                $fulltextAvailable = DB::getDriverName() === 'mysql'
                    && DB::table('information_schema.statistics')
                        ->where('table_schema', DB::raw('DATABASE()'))
                        ->where('table_name', 'products')
                        ->where('index_type', 'FULLTEXT')
                        ->count() > 0;
            } catch (\Throwable $e) {
                $fulltextAvailable = false;
            }
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
}

if (!function_exists('highest_banner')) {
function highest_banner()
{
    return Banner::whereJsonContains('groups', 'index_highest_banner')
        ->where('published', true)
        ->orderBy('ordering')
        ->get();
}
}


if (!function_exists('widget_seeder')) {
function widget_seeder()
{
    $theme = current_theme_name();

    if (Widget::where('theme', $theme)->exists()) {
        return;
    }

    // ویجت‌های صفحه اصلی
    $homeWidgets = [
        [
            'widget' => ['title' => 'بنر تکی', 'key' => 'middle-banners', 'theme' => $theme, 'ordering' => 1],
            'options' => [['key' => 'number', 'value' => '1'], ['key' => 'place', 'value' => 'index_banners_place1']]
        ],
        [
            'widget' => ['title' => 'اسلایدر اصلی و بنر کناری', 'key' => 'main-slider', 'theme' => $theme, 'ordering' => 2],
            'options' => [['key' => 'number', 'value' => '5'], ['key' => 'banner_position', 'value' => 'left']]
        ],
        [
            'widget' => ['title' => 'استوری و هایلایت ها', 'key' => 'main-story', 'theme' => $theme, 'ordering' => 3],
            'options' => [['key' => 'number', 'value' => '10']]
        ],
        [
            'widget' => ['title' => 'محصولات ویژه', 'key' => 'products-colorful-block', 'theme' => $theme, 'ordering' => 4],
            'options' => array_merge(
                [['key' => 'title', 'value' => 'محصولات ویژه'], ['key' => 'block_color', 'value' => '#2ba5da']],
                baseProductOptions()
            )
        ],
        [
            'widget' => ['title' => 'بنر چهارتایی', 'key' => 'middle-banners-4', 'theme' => $theme, 'ordering' => 5],
            'options' => [['key' => 'place', 'value' => 'index_banners_place1'], ['key' => 'number', 'value' => '4']]
        ],
        [
            'widget' => ['title' => 'محصولات ویژه دو', 'key' => 'products-colorful-block', 'theme' => $theme, 'ordering' => 6],
            'options' => array_merge(
                [['key' => 'title', 'value' => 'محصولات ویژه'], ['key' => 'block_color', 'value' => '#53c83c']],
                baseProductOptions()
            )
        ],
        [
            'widget' => ['title' => 'کادر محصولات با پیشنهاد لحظه ای', 'key' => 'products-moment-block', 'theme' => $theme, 'ordering' => 7],
            'options' => [
                ['key' => 'products_type', 'value' => 'all'],
                ['key' => 'inventory_status', 'value' => 'all'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'order_by_stock', 'value' => 'yes'],
                ['key' => 'number', 'value' => '10'],
                ['key' => 'categories', 'value' => 'off'],
                ['key' => 'sub_category_products', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'دسته بندی ها', 'key' => 'categories', 'theme' => $theme, 'ordering' => 8],
            'options' => [
                ['key' => 'categories', 'value' => 'on', 'type' => 'product_categories', 'categories' => Category::where(['show_in_index' => 1, 'type' => 'productcat'])->pluck('id')]
            ]
        ],
        [
            'widget' => ['title' => 'محصولات تخفیف دار', 'key' => 'products-default-block', 'theme' => $theme, 'ordering' => 9],
            'options' => array_merge([['key' => 'title', 'value' => 'محصولات تخفیف دار'], ['key' => 'products_type', 'value' => 'discount']], baseProductOptions('no'))
        ],
        [
            'widget' => ['title' => 'بنر 4 تایی', 'key' => 'middle-banners-4', 'theme' => $theme, 'ordering' => 10],
            'options' => [['key' => 'place', 'value' => 'index_banners_place2'], ['key' => 'number', 'value' => '4']]
        ],
        [
            'widget' => ['title' => 'جدید ترین محصولات', 'key' => 'products-default-block', 'theme' => $theme, 'ordering' => 11],
            'options' => array_merge([['key' => 'title', 'value' => 'جدید ترین محصولات']], baseProductOptions('no'))
        ],
        [
            'widget' => ['title' => 'بنر دوتایی', 'key' => 'middle-banners-2', 'theme' => $theme, 'ordering' => 12],
            'options' => [['key' => 'place', 'value' => 'index_banners_place1'], ['key' => 'number', 'value' => '2']]
        ],
        [
            'widget' => ['title' => 'اسلایدر لوگو همکاران', 'key' => 'coworker-sliders', 'theme' => $theme, 'ordering' => 13],
            'options' => [['key' => 'number', 'value' => '10']]
        ],
        [
            'widget' => ['title' => 'بنر تکی', 'key' => 'middle-banners', 'theme' => $theme, 'ordering' => 14],
            'options' => [['key' => 'place', 'value' => 'index_banners_place2'], ['key' => 'number', 'value' => '10']]
        ],
    ];

    // ویجت‌های صفحه مقالات
    $postWidgets = [
        /* فاز ۱۶۶ — ویجت‌های جدید صفحهٔ مقالات (مطابق blog.html تمپلیت) — بالای لیست */
        [
            'widget' => ['title' => 'هدر وبلاگ', 'key' => 'blog-header', 'theme' => $theme, 'ordering' => 1, 'page' => 'posts'],
            'options' => [
                ['key' => 'show_search', 'value' => 'yes'],
                ['key' => 'show_categories', 'value' => 'yes'],
                ['key' => 'show_banner', 'value' => 'yes'],
                ['key' => 'news_number', 'value' => '3'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'news_editor_pick', 'value' => 'no'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'پربازدید ترین مطالب', 'key' => 'blog-mostviewed-slider', 'theme' => $theme, 'ordering' => 2, 'page' => 'posts'],
            'options' => [
                ['key' => 'title', 'value' => 'پربازدید ترین مطالب'],
                ['key' => 'sort_type', 'value' => 'view'],
                ['key' => 'number', 'value' => '10'],
                ['key' => 'link', 'value' => '/blog/articles?sort=most_viewed'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'آخرین ویدیوهای', 'key' => 'blog-videos', 'theme' => $theme, 'ordering' => 3, 'page' => 'posts'],
            'options' => [
                ['key' => 'title', 'value' => 'آخرین ویدیوهای'],
                ['key' => 'grid_number', 'value' => '6'],
                ['key' => 'sidebar_number', 'value' => '7'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'fallback_to_latest', 'value' => 'yes'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'آخرین مطالب', 'key' => 'blog-latest-grid', 'theme' => $theme, 'ordering' => 4, 'page' => 'posts'],
            'options' => [
                ['key' => 'title', 'value' => 'آخرین مطالب'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'number', 'value' => '8'],
                ['key' => 'link', 'value' => '/blog/articles'],
                ['key' => 'link_title', 'value' => 'مشاهده همه'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'دسته بندی ها', 'key' => 'post-categories', 'theme' => $theme, 'ordering' => 5, 'page' => 'posts'],
            'options' => [
                ['key' => 'post_categories', 'value' => 'on', 'type' => 'post_categories', 'categories' => Category::where(['show_in_index' => 1, 'type' => 'postcat'])->pluck('id')]
            ]
        ],
        [
            'widget' => ['title' => 'برگزیده ها', 'key' => 'posts-three-box-block', 'theme' => $theme, 'ordering' => 6, 'page' => 'posts'],
            'options' => [['key' => 'title', 'value' => 'برگزیده ها'], ['key' => 'sort_type', 'value' => 'latest'], ['key' => 'sub_category_post', 'value' => 'yes']]
        ],
        [
            'widget' => ['title' => 'مقالات تصادفی', 'key' => 'posts-big-box-block', 'theme' => $theme, 'ordering' => 7, 'page' => 'posts'],
            'options' => [['key' => 'title', 'value' => 'مقالات تصادفی'], ['key' => 'sort_type', 'value' => 'random'], ['key' => 'sub_category_post', 'value' => 'yes']]
        ],
        [
            'widget' => ['title' => 'مقالات', 'key' => 'posts-default-block', 'theme' => $theme, 'ordering' => 8, 'page' => 'posts'],
            'options' => [
                ['key' => 'title', 'value' => 'پربازدید ترین ها'],
                ['key' => 'sort_type', 'value' => 'latest'],
                ['key' => 'link_title', 'value' => 'بیشتر'],
                ['key' => 'number', 'value' => '10'],
                ['key' => 'sub_category_post', 'value' => 'yes'],
            ]
        ],
        [
            'widget' => ['title' => 'بنر چهارتایی', 'key' => 'middle-banners-4', 'theme' => $theme, 'ordering' => 9, 'page' => 'posts'],
            'options' => [['key' => 'place', 'value' => 'index_banners_place1'], ['key' => 'number', 'value' => '4']]
        ],
        [
            'widget' => ['title' => 'هشتگ های داغ', 'key' => 'posts-tags', 'theme' => $theme, 'ordering' => 10, 'page' => 'posts'],
            'options' => [['key' => 'title', 'value' => 'هشتگ های داغ'], ['key' => 'sort_type', 'value' => 'most_used'], ['key' => 'ordering', 'value' => 'asc']]
        ],
    ];

    // ایجاد ویجت‌ها
    createWidgets($homeWidgets);
    createWidgets($postWidgets);
}
}

// گزینه‌های پایه محصولات
if (!function_exists('baseProductOptions')) {
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
}

// ایجاد ویجت‌ها و آپشن‌ها
if (!function_exists('createWidgets')) {
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
}

if (!function_exists('theme_first_config')) {
function theme_first_config()
{
    widget_seeder();
}
}

if (!function_exists('Get_All_Tags')) {
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
}

/* ============================================================
   فاز ۱۶۱ — جزیرهٔ دادهٔ تنوع‌ها برای به‌روزرسانی لحظه‌ای خرید سریع
   (theme-product.js → #js-product-variants-data)
   ============================================================
   چرا کوئری خام و نه روابط Eloquent؟
   روی سایت واقعی، ساخت جزیره با مدل‌ها (prices()->get() +
   regularPrice()/salePrice()/get_attributes()) برای «همهٔ» محصولات
   استثنا می‌داد و جزیره همیشه [] رندر می‌شد؛ نتیجهٔ قابل بازتولید:
   باکس خرید سریع با تغییر تنوع به‌روز نمی‌شد. این نسخه با DB خام
   (بدون اکسسور/رویداد مدل) کاملاً مقاوم است و گردکردن قیمت را
   مطابق Price::toRoundPrice هسته بازتولید می‌کند.
   ============================================================ */
if (!function_exists('saniar_variants_payload')) {
function saniar_variants_payload($product): array
{
    try {
        $priceRows = \Illuminate\Support\Facades\DB::table('prices')
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->get(['id', 'price', 'discount_price', 'discount', 'discount_expire_at', 'stock']);

        if ($priceRows->isEmpty()) {
            return [];
        }

        $attributeRows = \Illuminate\Support\Facades\DB::table('attribute_price')
            ->join('attributes', 'attributes.id', '=', 'attribute_price.attribute_id')
            ->whereIn('attribute_price.price_id', $priceRows->pluck('id'))
            ->get(['attribute_price.price_id', 'attributes.id', 'attributes.name']);

        $attributesByPrice = $attributeRows->groupBy('price_id');

        /* گردکردن قیمت — بازتولید Price::toRoundPrice */
        $roundingAmount = $product->rounding_amount ?? 'default';
        if ($roundingAmount == 'default') {
            $roundingAmount = option('default_rounding_amount', 'no');
        }
        $roundingType = $product->rounding_type ?? 'default';
        if ($roundingType == 'default') {
            $roundingType = option('default_rounding_type', 'close');
        }

        $round = function ($value) use ($roundingAmount, $roundingType) {
            $value = (float) $value;
            if (in_array((string) $roundingAmount, ['100', '1000', '10000', '100000'])) {
                if ($roundingType == 'up') {
                    $value = ceil($value / $roundingAmount) * $roundingAmount;
                } else if ($roundingType == 'down') {
                    $value = floor($value / $roundingAmount) * $roundingAmount;
                } else {
                    $value = round($value / $roundingAmount) * $roundingAmount;
                }
            }
            return $value;
        };

        $payload = [];

        foreach ($priceRows as $row) {
            /* خطای یک ردیف، کل جزیره را خالی نکند */
            try {
                $hasDiscount = false;
                if (!empty($row->discount)) {
                    try {
                        $hasDiscount = is_null($row->discount_expire_at)
                            || \Carbon\Carbon::parse($row->discount_expire_at)->gt(now());
                    } catch (\Throwable $dateError) {
                        $hasDiscount = false;
                    }
                }

                $regular = $round($row->price);
                $sale = $hasDiscount ? $round($row->discount_price ?? $row->price) : $regular;

                $attrs = $attributesByPrice->get($row->id, collect());

                $payload[] = [
                    'id'         => (int) $row->id,
                    'attrs'      => $attrs->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
                    'attr_names' => $attrs->pluck('name')->map(fn ($n) => trim((string) $n))->filter()->values()->all(),
                    'price'      => number_format($regular),
                    'sale'       => number_format($sale),
                    'discount'   => $hasDiscount ? (int) $row->discount : 0,
                    'stock'      => (int) $row->stock,
                ];
            } catch (\Throwable $variant_row_exception) {
                continue;
            }
        }

        return $payload;
    } catch (\Throwable $variants_exception) {
        return [];
    }
}
}
