<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Seller;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * ★ فاز ۲۲: کلید کش پرمیشن‌های فعال —
     * قبلاً کوئری «همه پرمیشن‌ها + نقش‌ها» (۲۴۶ ردیف) روی «هر درخواست» اجرا می‌شد
     * و روی هاست اشتراکی به‌تنهایی تا ۵ ثانیه زمان می‌گرفت.
     */
    public const PERMISSIONS_CACHE_KEY = 'auth.permissions.active';

    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot()
    {
        $this->registerPolicies();

        // ★ فاز ۲۲: باطل‌سازی کش پرمیشن‌ها با هر تغییر در پرمیشن یا نقش
        foreach ([Permission::class, Role::class] as $model) {
            $model::saved(fn () => Cache::forget(self::PERMISSIONS_CACHE_KEY));
            $model::deleted(fn () => Cache::forget(self::PERMISSIONS_CACHE_KEY));
        }

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
        // ★ فاز ۲۲: کش ۱۵ دقیقه‌ای — با ذخیره/حذف پرمیشن یا نقش، فوراً باطل می‌شود
        // (سینک مستقیم pivot از پنل قدیمی حداکثر ۱۵ دقیقه تأخیر دارد + دکمه «پاکسازی کش» پنل)
        return Cache::remember(self::PERMISSIONS_CACHE_KEY, now()->addMinutes(15), function () {
            return Permission::where('active', true)->with('roles')->get();
        });
    }
}
