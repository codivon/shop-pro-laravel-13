<?php

namespace Themes\WeblakShop\src;

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

            $footer_links     = config('front.linkGroups', []);
            $links            = Link::orderBy('ordering')->get();

            $view->with(compact('footer_links', 'links'));
        });

        view()->composer(['front::partials.menu.menu', 'front::partials.mobile-menu.menu'], function ($view) {


            $productcats = Cache::rememberForever('front.productcats', function () {
                return Category::published()->whereNull('category_id')
                    ->orderBy('ordering')
                    ->where('type', 'productcat')
                    ->getWithChilds();
            });

            $postcats    = Category::published()->where('type', 'postcat')->whereNull('category_id')->orderBy('ordering')->get();
            $menus       = Menu::whereNull('menu_id')->orderBy('ordering')->get();

            $view->with(compact('productcats', 'postcats', 'menus'));
        });

        view()->composer(['front::blogs.partials.sidebar'], function ($view) {

            $latest_posts = Post::where('published', true)->latest()->take(6)->get();

            $view->with(compact('latest_posts'));
        });

        view()->composer(['front::user.layouts.master'], function ($view) {

            $user = auth()->user();
            $random_products = Product::where('published', true)
                ->available()
                ->inRandomOrder()
                ->limit(10)
                ->get();
            $view->with(compact('user', 'random_products'));
        });

        view()->composer(['front::partials.cart', 'front::carts.partials.checkout-sidebar', 'front::carts.checkout', 'front::carts.index'], function ($view) {
            $cart = get_cart();
            $view->with('cart', $cart);
        });
    }
}
