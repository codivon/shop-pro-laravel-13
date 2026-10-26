<?php

namespace Modules\CustomerClub\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\CustomerClub\Console\Commands\BirthdayPointsCommand;
use Modules\CustomerClub\Console\Commands\ExpirePointsCommand;
use Modules\CustomerClub\Console\Commands\InstallPermissionsCommand;
use Modules\CustomerClub\Http\Middlewares\CheckModuleLicense;
use Modules\CustomerClub\Models\ReferralTier;
use Modules\CustomerClub\Observers\ReviewPointsObserver;
use Modules\CustomerClub\Services\LicenseGuard;
use App\Models\Review;
use App\Services\FrontModuleMenuRegistry;
use App\Services\ModuleMenuRegistry;

class CustomerClubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // ⚠️ register() نه singleton() — فاز ۹۰: singletonِ خالی باعث می‌شد
        // Listenerهای OrderPaid هرگز روی Dispatcher ننشینند (باگ هم‌ساخت
        // MarketingMessaging v1.3.0). الگوی درست: CreditPay/CreditPayServiceProvider.
        $this->app->register(EventServiceProvider::class);

        $this->commands([
            InstallPermissionsCommand::class,
            ExpirePointsCommand::class,
            BirthdayPointsCommand::class,
        ]);

        $this->app->booted(function () {
            $router = $this->app->make(Router::class);
            $router->aliasMiddleware('customerclub.license', CheckModuleLicense::class);
        });
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();

        // Observer امتیاز نظر — مستقل از لایسنس (همان قرارداد Listenerها؛
        // اعمال لایسنس در سطح روت‌ها با CheckModuleLicense برقرار است)
        Review::observe(ReviewPointsObserver::class);

        // self-heal سطوح معرفی — تست بند ۲ بدون اجرای کامند، ۳ سطح را می‌خواهد
        try {
            ReferralTier::ensureSeeded();
        } catch (\Throwable $e) {
            // جدول هنوز migrate نشده — بی‌خیال؛ InstallPermissionsCommand بعداً seed می‌کند
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // انقضای امتیازها — روزانه ۰۳:۱۵
            $schedule->command('customerclub:expire-points')
                ->dailyAt('03:15')
                ->withoutOverlapping()
                ->runInBackground()
                ->name('customerclub-expire-points')
                ->description('انقضای لات‌های امتیاز باشگاه مشتریان');

            // امتیاز تولد — روزانه ۰۷:۰۰ (خود کامند گارد فعال‌بودن قانون دارد)
            $schedule->command('customerclub:birthday-points')
                ->dailyAt('07:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->name('customerclub-birthday-points')
                ->description('پاداش سالانهٔ تولد مشتریان باشگاه');
        });

        // خودترمیم پرمیژن‌ها — بدون اجرای دستی کامند، گیت‌های can:customerclub.*
        // از اولین بوت کار می‌کنند (الگوی ensureSeeded؛ idempotent).
        try {
            \Modules\CustomerClub\Permissions\ModulesPermissions::ensureInDatabase();
        } catch (\Throwable $e) {
            // جدول permissions هنوز migrate نشده — کامند نصب بعداً انجام می‌دهد
        }

        // منو/اوبزرور: منو فقط با لایسنس معتبر نمایش داده می‌شود (الگوی MarketingMessaging
        // فاز ۸۵: منو به نصب+فعال‌بودن ماژول وابسته است نه به چک آنلاین لایسنس —
        // اما نکتهٔ فاز ۸۶: منو باید بدون گیت لایسنس همیشه بعد از نصب دیده شود).
        if (!LicenseGuard::check('customer-club')) {
            return;
        }

        $this->registerMenu();
    }

    protected function registerMenu(): void
    {
        // منوی پنل ادمین (دو-پنلی)
        ModuleMenuRegistry::add('customer-club::back.partials.menu', 85);

        // منوی پنل مشتری — «راهنما» در منو نیست (درس فاز ۸۹): راهنما به‌صورت
        // بنر درون‌صفحه‌ای داخل خود باشگاه ارائه می‌شود.
        FrontModuleMenuRegistry::add('customer-club::front.partials.menu', 60);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path('CustomerClub', 'config/config.php'),
            'customer-club'
        );
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path('CustomerClub', 'resources/views');
        $viewPath   = resource_path('views/modules/customer-club');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'customer-club-module-views']);

        $paths = array_merge([$sourcePath], $this->getPublishableViewPaths());
        $this->loadViewsFrom($paths, 'customer-club');
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/customer-club')) {
                $paths[] = $path . '/modules/customer-club';
            }
        }

        return $paths;
    }
}
