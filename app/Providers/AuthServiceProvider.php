<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PulseSettings;
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

        /*
         * فاز ۱۰۴ — دسترسی به داشبورد Laravel Pulse (/pulse).
         *
         * پیش‌فرض پکیج فقط در محیط local باز است (پکیج: fn () => app()->environment('local'))
         * → روی هاست تولیدی حتی سازندهٔ سایت هم ۴۰۳ می‌گرفت. چون پرووایدرهای پکیج قبل از
         * پرووایدرهای اپ بوت می‌شوند، این تعریفِ بعدی همان گیت را override می‌کند.
         *
         * قانون دسترسی: ادمینِ لاگین‌کردهٔ پنل (گارد adminPanel) + سوییچ تنظیماتی
         * (پنل ← تنظیمات برنامه‌نویس ← تب عمومی). فروشنده/مشتری/مهمان = ۴۰۳.
         * عمداً خارج از گارد application_installed/کنسول تعریف شده تا در تست هم فعال باشد.
         */
        Gate::define('viewPulse', function ($user = null) {
            if (! PulseSettings::adminAccess()) {
                return false;
            }

            return Auth::guard('adminPanel')->check();
        });

        if (! $this->app->runningInConsole() && application_installed()) {
            foreach ($this->getPermissions() as $permission) {
                /*
                 * فاز ۲۸ — رفع «ناپدید شدن دکمه‌ها بعد از هر اکشن» در پنل جدید:
                 * درخواست‌های آپدیتِ Livewire از اکپوینت آپدیت لایووایر می‌گذرند که
                 * میدل‌ور AdminPanel را ندارد؛ بنابراین auth()->user() (گارد پیش‌فرض web)
                 * null است و چون پارامتر کلوزر «پذیرنده مهمان» نبود، لاراول اصلاً کلوزر را
                 * صدا نمی‌زد و همه‌ی @canها false می‌شدند (دکمه ایجاد/ویرایش/حذف محو می‌شد).
                 * با default null برای پارامتر، کلوزر حتی برای مهمان هم اجرا می‌شود و ما
                 * خودمان ادمین را از گارد adminPanel برمی‌گردانیم.
                 */
                Gate::define($permission->name, function ($user = null) use ($permission) {
                    $admin = $user instanceof Admin ? $user : Auth::guard('adminPanel')->user();

                    if ($admin instanceof Admin) {
                        return $admin->level == 'creator' || $admin->hasRole($permission->roles);
                    }

                    // امنیتی: فروشنده‌ها و مهمان‌ها هیچ پرمیشن پنل مدیریتی ندارند
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
