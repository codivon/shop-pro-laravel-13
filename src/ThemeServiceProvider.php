<?php

namespace Themes\SaniarTheme\src;

use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\Link;
use App\Models\Menu;
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

        // سانیار فاز K — SPA لایووایر ۴:
        // اسکریپت هستهٔ لایووایر با data-navigate-once تزریق می‌شود تا در ناوبری‌های
        // wire:navigate مجدداً اجرا (و دوباره‌بوت) نشود؛ فقط بار اول کامل لود می‌شود.
        if (class_exists(\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class)) {
            app(\Livewire\Mechanisms\FrontendAssets\FrontendAssets::class)
                ->useScriptTagAttributes(['data-navigate-once' => 'true']);
        }

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

        // سانیار فاز K: کامپوزر قدیمی front::blogs.partials.sidebar حذف شد —
        // این ویو در تم سانیار وجود ندارد (سایدبار مقاله از کنترلر خودش داده می‌گیرد)
        // و کامپوزر با کوئری کش‌شدهٔ published هرگز اجرا نمی‌شد.

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
