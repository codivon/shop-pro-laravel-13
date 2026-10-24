<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Filter;
use App\Models\Price;
use App\Observers\ProductObserver;
use App\Observers\PriceObserver;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Slider;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\PostObserver;
use App\Observers\SliderObserver;
use App\Services\PulseService;
use Illuminate\Support\Facades\Cache;
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

        /*
         * فاز ۲۶ — نصب‌کننده: کنترلر «نصب کامل شد» بدون key:generate.
         * نسخه‌ی پکیج در هر بازدید از /install/final دستور key:generate --force
         * اجرا می‌کرد؛ APP_KEY عوض می‌شد → کوکی‌های سشن نامعتبر و هش endpoint
         * لایووایر (وابسته به APP_KEY) با route:cache ناسازگار → پنل ۴۰۴ می‌شد.
         */
        $this->app->bind(
            \RachidLaasri\LaravelInstaller\Controllers\FinalController::class,
            \App\Http\Controllers\Install\FinalController::class
        );

        // نما (views) پنل مدیریت جدید — admin-panel::*
        $this->loadViewsFrom(resource_path('views/admin-panel'), 'admin-panel');

        // فاز ۷۳ — فایل‌منیجر: endpointهای thumbnails/preview پکیج با API انحصاری
        // Intervention v3 نوشته شده‌اند و روی vendor قدیمی 500 می‌دهند (تصاویر و
        // پیش‌نمایش نمایش داده نمی‌شد) → نسخهٔ سازگار + fail-safe جایگزین می‌شود
        $this->app->bind(
            \Alexusmai\LaravelFileManager\FileManager::class,
            \App\Services\CompatFileManager::class
        );

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

        $this->registerRateLimiters();

        // مسیر build پنل جدید (خروجی Vite جدا از Mix)
        \Illuminate\Support\Facades\Vite::useBuildDirectory('admin-panel');

        // تعیین گارد پیش‌فرض بر اساس نشست فعال — به‌ویژه برای endpoint آپدیت Livewire
        // تا ‎@can و auth() در رندر مجدد کامپوننت‌ها (adminPanel/seller/web) درست کار کنند
        \Illuminate\Support\Facades\Route::pushMiddlewareToGroup('web', \App\Http\Middleware\ResolveDefaultGuard::class);

        /*
         * فاز ۴۵ — ترتیب درستِ نصب: «اول وضعیت نصب، بعد هرچیز دیگر»
         * قبلاً loadTheme() قبل از بررسی نصب اجرا می‌شد و سشن/کش ممکن بود قبل از
         * ریدایرکت به /install به دیتابیس دست بزنند → با .env قدیمی (اعتبارنامه‌ی
         * نامعتبر) به‌جای صفحه‌ی نصب، خطای «اتصال به دیتابیس» دیده می‌شد.
         *
         * قاعدهٔ جدید: اگر فایل storage/installed نیست، فارغ از وجود/عدم وجود .env:
         *   ۱) درایورهای session/cache روی file قفل می‌شوند تا هیچ مسیری (حتی
         *      خودِ صفحات /install با StartSession) به دیتابیس دست نزند؛
         *   ۲) درخواست‌های خارج از /install با HttpResponseException دقیقاً در
         *      همین‌جا قطع و به /install ریدایرکت می‌شوند (send() فقط پاسخ را
         *      می‌فرستاد و اجرای بوت/میدلورها ادامه پیدا می‌کرد).
         */
        if (! application_installed()) {
            if (! $this->app->runningInConsole()) {
                config([
                    'session.driver' => 'file',
                    'cache.default'  => 'file',
                    'queue.default'  => 'sync',
                ]);

                if (! file_exists(base_path('.env'))) {
                    copy(base_path('.env.example'), base_path('.env'));
                }

                $this->app->bind(EnvironmentManager::class, MyEnvironmentManager::class);

                if (request()->segment(1) !== 'install') {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('install'));
                }
            }

            return; // نصب انجام نشده — هیچ سرویس وابسته به دیتابیس/قالب بوت نشود
        }

        $this->loadTheme();

        // کامپوزرهای ویو lazy هستند (فقط هنگام رندر ویو اجرا می‌شوند) و
        // بدون آن‌ها ویوهای تم در تست‌ها/کنسول با متغیرهای تعریف‌نشده
        // (مثل $menus) 500 می‌دهند — پس در حالت کنسول هم ثبت می‌شوند
        $this->viewComposer();

        if (! $this->app->runningInConsole()) {
            $this->configs();
        }

        Validator::extend(
            'captcha',
            fn ($attribute, $value) => app(\App\Services\CaptchaService::class)->verify($value),
            'کد امنیتی وارد شده صحیح نیست یا منقضی شده است.'
        );


        $this->observers();

        $this->app->booted(function () {
            // باطل‌سازی کش یکپارچه options هنگام ایجاد/ویرایش/حذف مستقیم گزینه‌ها
            \App\Models\Option::saved(function () {
                $this->forgetOptionsLoadedFlags();
            });
            \App\Models\Option::deleted(function () {
                $this->forgetOptionsLoadedFlags();
            });

            $this->registerTaggableObservers();

            // ★ فاز ۲۲: پس از پایان مایگریشن (نصب/آپدیت) کش وجود جدول redirects باطل شود
            $this->app['events']->listen(\Illuminate\Database\Events\MigrationsEnded::class, function () {
                \Illuminate\Support\Facades\Cache::forget('redirects.table_exists');
            });
        });

        $this->registerObservers();
    }

    /**
     * باطل‌سازی فلگ بارگذاری کش options برای همه زبان‌ها.
     */
    private function forgetOptionsLoadedFlags(): void
    {
        $languages = array_unique(array_merge(['fa', 'en'], (array) config('general.languages', [])));

        foreach ($languages as $lang) {
            \Illuminate\Support\Facades\Cache::forget('options.'.$lang.'.__loaded__');
        }
    }

    /**
     * FIX مقیاس: rate-limiter های ویترین — فقط در production فعالند تا
     * ربات‌ها نتوانند جستجو/جستجوی زنده را هدر بدهند؛ در تست/سندباکس محدود نیستند.
     */
    private function registerRateLimiters(): void
    {
        $isProduction = $this->app->environment('production');

        \Illuminate\Support\Facades\RateLimiter::for('front-search', function ($request) use ($isProduction) {
            if (! $isProduction) {
                return \Illuminate\Cache\RateLimiting\Limit::none();
            }

            return \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by(optional($request->user())->id ?: 'search|'.$request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('front-ajax-search', function ($request) use ($isProduction) {
            if (! $isProduction) {
                return \Illuminate\Cache\RateLimiting\Limit::none();
            }

            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by(optional($request->user())->id ?: 'ajax|'.$request->ip());
        });
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
        Price::observe(PriceObserver::class);
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
        } elseif ($theme) {
            // فاز ۶۸ — قالب فعال قابل بارگذاری نیست (مثلاً پوشه تغییرنام یافته ولی
            // namespace قدیمی مانده). قبلاً بی‌صدا رد می‌شد و صفحهٔ اصلی ۴۰۴ می‌داد؛
            // حالا دست‌کم در لاگ ردیابی می‌شود.
            \Illuminate\Support\Facades\Log::warning(
                'قالب فعال قابل بارگذاری نیست — کلاس '.$theme['service_provider'].' پیدا نشد؛ نام پوشهٔ themes/ باید با فضای‌نام PHP قالب یکی باشد.'
            );
        }
    }

    protected function registerTaggableObservers()
    {
        // مسیر مدل‌ها — نتیجهٔ اسکن کش می‌شود تا هر ریکوئست کل پوشه اسکن نشود
        $taggableModels = Cache::remember('app.models.using_taggable', 3600, function () {
            $modelsPath = app_path('Models');
            $models = [];

            if (! is_dir($modelsPath)) {
                return $models;
            }

            foreach (File::files($modelsPath) as $file) {
                $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

                if (class_exists($modelClass) && $this->usesTaggableTrait($modelClass)) {
                    $models[] = $modelClass;
                }
            }

            return $models;
        });

        foreach ($taggableModels as $modelClass) {
            $modelClass::observe(\App\Observers\TaggableObserver::class);
        }
    }

    protected function usesTaggableTrait($modelClass)
    {
        return in_array(\App\Traits\Taggable::class, class_uses_recursive($modelClass));
    }

    protected function registerObservers(): void
    {
        // لیست مدل‌ها کش می‌شود تا هر ریکوئست دایرکتوری Models اسکن نشود
        $modelClasses = Cache::remember('app.models.all_classes', 3600, function () {
            $modelsPath = app_path('Models');

            if (!File::exists($modelsPath)) {
                return [];
            }

            $classes = [];

            foreach (File::files($modelsPath) as $file) {
                $modelClass = 'App\\Models\\' . $file->getFilenameWithoutExtension();

                if (class_exists($modelClass)) {
                    $classes[] = $modelClass;
                }
            }

            return $classes;
        });

        // مدل‌هایی که نباید Observer داشته باشند
        $excludedModels = [
            'App\\Models\\Activity',
            'App\\Models\\ActivityLog',
        ];

        foreach ($modelClasses as $modelClass) {
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
