<?php

namespace Themes\Saniar\src;

use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\Link;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        require_once(__DIR__ . '/helpers.php');

        // set config file
        if ($this->app['config']->get('front') === null) {
            $this->app['config']->set('front', require __DIR__ . '/../config/general.php');
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // load routes

        Route::group([
            'middleware' => ['web'],
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        });

        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'front');

        // load views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'front');

        // share with views
        // کامپوزرها lazy هستند (فقط هنگام رندر ویو اجرا می‌شوند)؛ بدون ثبت در
        // حالت کنسول، ویوهای تم در تست‌ها با متغیرهای تعریف‌نشده 500 می‌دهند
        $this->viewComposer();
    }

    private function viewComposer()
    {
        // SHARE WITH SPECIFIC VIEW


        view()->composer(['front::partials.footer'], function ($view) {

            $footer_links = config('front.linkGroups', []);

            // FIX مقیاس: لینک‌های فوتر در هر بازدید کوئری نمی‌زنند
            $links = Cache::remember('front.footer.links', 300, function () {
                return Link::orderBy('ordering')->get();
            });

            $view->with(compact('footer_links', 'links'));
        });

        view()->composer(['front::partials.menu.menu', 'front::partials.mobile-menu.menu'], function ($view) {

            // FIX مقیاس: درخت منو قبلاً به‌خاطر cache-forget داخل کانستراکتور
            // CategoryObserver در «هر ریکوئست» بازسازی می‌شد — الان کش واقعاً کار می‌کند.
            // postcats/menus هم با کش کوتاه‌مدت از کوئری حذف شدند.
            // eager-loading درخت منو: قبلاً به‌ازای هر آیتم منو ۲-۳ کوئری
            // ($menu->category / $menu->menus / getCategories) اجرا می‌شد = N+1
            [$productcats, $postcats, $menus] = Cache::remember('front.menu.data', 300, function () {
                $productcats = Cache::rememberForever('front.productcats', function () {
                    return Category::published()->whereNull('category_id')
                        ->orderBy('ordering')
                        ->where('type', 'productcat')
                        ->getWithChilds();
                });

                $postcats = Category::published()->where('type', 'postcat')->whereNull('category_id')->orderBy('ordering')->get();
                $menus    = Menu::whereNull('menu_id')
                    // درخت منو + دستهٔ هر آیتم — childrenmenus خودش category را eager می‌کند
                    ->with(['category.categories', 'menus', 'childrenmenus'])
                    ->orderBy('ordering')
                    ->get();

                return [$productcats, $postcats, $menus];
            });

            $view->with(compact('productcats', 'postcats', 'menus'));
        });

        view()->composer(['front::blogs.partials.sidebar'], function ($view) {

            $latest_posts = Cache::remember('front.blog.latest_posts', 300, function () {
                return Post::where('published', true)->latest()->take(6)->get();
            });

            $view->with(compact('latest_posts'));
        });

        view()->composer(['front::user.layouts.master'], function ($view) {

            $user = auth()->user();

            // FIX مقیاس: inRandomOrder روی میلیون محصول = full-scan؛ کش ۱۰ دقیقه‌ای
            $random_products = Cache::remember('front.user_layout.random_products', 600, function () {
                return Product::where('published', true)
                    ->available()
                    ->inRandomOrder()
                    ->limit(10)
                    ->get();
            });

            $view->with(compact('user', 'random_products'));
        });

        view()->composer(['front::partials.cart', 'front::carts.partials.checkout-sidebar', 'front::carts.checkout', 'front::carts.index'], function ($view) {
            $cart = get_cart();
            $view->with('cart', $cart);
        });
    }
}
