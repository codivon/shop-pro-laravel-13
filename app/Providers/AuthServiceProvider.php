<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Admin;
use App\Models\Seller;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot()
    {
        $this->registerPolicies();

        if (!$this->app->runningInConsole() && application_installed()) {
            foreach ($this->getPermissions() as $permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    // بررسی اگر کاربر ادمین است
                    if ($user instanceof Admin) {
                        return $user->level == 'creator' || $user->hasRole($permission->roles);
                    }

                    // امنیتی: فروشنده‌ها هیچ پرمیشن پنل مدیریتی ندارند
                    // (پیش‌تر true بود — هر فروشنده‌ای به همه‌ی گیت‌های پنل پاس می‌شد)
                    if ($user instanceof Seller) {
                        return false;
                    }

                    return false;
                });
            }
        }
    }

    protected function getPermissions()
    {
        return Permission::where('active', true)->with('roles')->get();
    }
}
