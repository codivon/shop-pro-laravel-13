<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Filter;
use App\Observers\ProductObserver;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\PostObserver;
use App\Observers\SliderObserver;
use App\Services\PulseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RachidLaasri\LaravelInstaller\Helpers\EnvironmentManager;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use App\Observers\GlobalActivityObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PulseService::class, fn() => new PulseService());

        // فاز ۲۱ — نصب‌کننده: مایگریشن‌ها مرحله‌به‌مرحله (قابل توقف/ادامه) اجرا شوند
        // به‌جای اجرای همه در یک درخواست که روی هاست Timeout می‌خورد
        $this->app->bind(
            \RachidLaasri\LaravelInstaller\Controllers\DatabaseController::class,
            \App\Http\Controllers\Install\DatabaseController::class
        );

        // نما (views) پنل مدیریت جدید — admin-panel::*
        $this->loadViewsFrom(resource_path('views/admin-panel'), 'admin-panel');

        // کامپوننت‌های کلاسی و anonymous پنل جدید
        $this->callAfterResolving('blade.compiler', function ($blade) {
            $blade->componentNamespace('App\\View\\Components\\AdminPanel', 'admin-panel');
            $blade->anonymousComponentPath(resource_path('views/admin-panel/components'), 'admin-panel');
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Schema::defaultStringLength(191);

        // مسیر build پنل جدید (خروجی Vite جدا از Mix)
        \Illuminate\Support\Facades\Vite::useBuildDirectory('admin-panel');

        // تعیین گارد پیش‌فرض بر اساس نشست فعال — به‌ویژه برای endpoint آپدیت Livewire
        // تا ‎@can و auth() در رندر مجدد کامپوننت‌ها (adminPanel/seller/web) درست کار کنند
        \Illuminate\Support\Facades\Route::pushMiddlewareToGroup('web', \App\Http\Middleware\ResolveDefaultGuard::class);

        $this->loadTheme();

        if (application_installed()) {

            if (!$this->app->runningInConsole()) {
                $this->viewComposer();
                $this->configs();
            }

        } else if (!application_installed() && !$this->app->runningInConsole()) {
            if (!file_exists(base_path('.env'))) {
                copy(base_path('.env.example'), base_path('.env'));
            }

            $this->app->bind(EnvironmentManager::class, MyEnvironmentManager::class);

            if (request()->segment(1) != 'install') {
                return redirect('install')->send();
            }
        }

        Validator::extend(
            'captcha',
            fn ($attribute, $value) => app(\App\Services\CaptchaService::class)->verify($value),
            'کد امنیتی وارد شده صحیح نیست یا منقضی شده است.'
        );


        $this->observers();

        $this->app->booted(function () {
            $this->registerTaggableObservers();

            // ★ فاز ۲۲: پس از پایان مایگریشن (نصب/آپدیت) کش وجود جدول redirects باطل شود
            $this->app['events']->listen(\Illuminate\Database\Events\MigrationsEnded::class, function () {
                \Illuminate\Support\Facades\Cache::forget('redirects.table_exists');
            });
        });

        $this->registerObservers();
    }

    private function viewComposer()
    {
        // SHARE WITH SPECIFIC VIEW

        view()->composer('*', function ($view) {
            $current_local = local_info();

            $view->with('current_local', $current_local);
        });

        view()->composer(['back.partials.notifications', 'back.partials.sidebar'], function ($view) {

            $notifications = auth('adminPanel')->user()->unreadNotifications;

            $view->with('notifications', $notifications);
        });

        view()->composer(['back.products.partials.filters', 'back.products.partials.index-filters'], function ($view) {

            $categories = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();

            $view->with('categories', $categories);
        });

        view()->composer(['back.products.categories.edit'], function ($view) {

            $filters = Filter::latest()->get();

            $view->with('filters', $filters);
        });

        view()->composer(['back.menus.index', 'back.sliders.create', 'back.sliders.edit', 'back.banners.create', 'back.banners.edit', 'back.links.create', 'back.links.edit'], function ($view) {

            $pages = Page::detectLang()->pluck('slug');

            $view->with('pages', $pages);
        });
    }

    private function configs()
    {
        //
    }

    private function observers()
    {
        Product::observe(ProductObserver::class);
        Slider::observe(SliderObserver::class);
        Banner::observe(BannerObserver::class);
        Post::observe(PostObserver::class);
        Category::observe(CategoryObserver::class);
    }

    public static function loadTheme()
    {
        // register theme service provider

        $theme = get_current_theme();

        if ($theme && class_exists($theme['service_provider'])) {
            app()->register($theme['service_provider']);
        }
    }

    protected function registerTaggableObservers()
    {
        // مسیر مدل‌ها
        $modelsPath = app_path('Models');

        if (!is_dir($modelsPath)) {
            return;
        }

        // همه فایل‌های مدل را اسکن کن
        foreach (File::files($modelsPath) as $file) {
            $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (class_exists($modelClass) && $this->usesTaggableTrait($modelClass)) {
                $modelClass::observe(\App\Observers\TaggableObserver::class);
            }
        }
    }

    protected function usesTaggableTrait($modelClass)
    {
        return in_array(\App\Traits\Taggable::class, class_uses_recursive($modelClass));
    }

    protected function registerObservers(): void
    {
        // مسیر مدل‌ها
        $modelsPath = app_path('Models');

        if (!File::exists($modelsPath)) {
            return;
        }

        // دریافت همه فایل‌های مدل
        $modelFiles = File::files($modelsPath);

        foreach ($modelFiles as $file) {
            $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            // بررسی وجود کلاس مدل
            if (class_exists($modelClass)) {
                // مدل‌هایی که نباید Observer داشته باشند
                $excludedModels = [
                    'App\Models\Activity',
                    'App\Models\ActivityLog',
                ];

                if (!in_array($modelClass, $excludedModels)) {
                    try {
                        $modelClass::observe(\App\Observers\GlobalActivityObserver::class);
                    } catch (\Exception $e) {
                        // خطا را نادیده بگیر
                    }
                }
            }
        }
    }
}
