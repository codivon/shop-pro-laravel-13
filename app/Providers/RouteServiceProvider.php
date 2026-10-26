<?php

namespace Modules\CustomerClub\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'CustomerClub';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /** روت‌های ادمین — الگوی DigiPay/MarketingMessaging */
    protected function mapAdminRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/admin.php'));
    }
}
