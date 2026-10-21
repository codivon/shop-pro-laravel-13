<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * تعیین گارد پیش‌فرض بر اساس نشست فعال
 *
 * مسیر Livewire (/livewire/update) از میدل‌ور پنل‌ها عبور نمی‌کند؛
 * بنابراین config('auth.defaults.guard') روی مقدار پیش‌فرض (web) برمی‌گردد
 * و دستورهای ‎@can و auth() در رندر مجدد کامپوننت‌ها به درستی کار نمی‌کنند.
 * این میدل‌ور گارد پیش‌فرض را بر اساس نشست فعال تنظیم می‌کند.
 */
class ResolveDefaultGuard
{
    public function handle($request, Closure $next)
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        /*
        | فاز ۲۱ — رفع «در فرانت، کاربر معمولی لاگین نمایش داده می‌شود» + خطای FK جدول searches
        |
        | قبلاً این میدل‌ور روی «همه‌ی» درخواست‌ها اجرا می‌شد؛ اگر ادمین در پنل لاگین بود،
        | گارد پیش‌فرض کل درخواست (حتی صفحات فروشگاه) adminPanel می‌شد. نتیجه:
        |   ۱) هدر فرانت auth()->check() را برقرار می‌دید و ادمین را به‌عنوان «کاربر» نشان می‌داد.
        |   ۲) لاگ جستجو شناسه‌ی ادمین را در searches.user_id می‌نشاند (FK به users → خطا 1452).
        |
        | حالا گارد فقط در بافت پنل‌ها و endpoint لایووایر تعیین می‌شود؛
        | صفحات فروشگاه همیشه گارد پیش‌فرض (web) را می‌بینند.
        |
        | فاز ۲۸ — رفع «محو شدن دکمه‌ها بعد از هر اکشن»: از وقتی اکپوینت آپدیت لایووایر
        | هش‌دار شد (/livewire-<hash>/update)، الگوی is('livewire/*') دیگر با آن نمی‌خورد
        | (نیاز به «livewire/» تحت‌اللفظی دارد) و این میدل‌ور روی آپدیت‌ها اجرا نمی‌شد؛
        | در نتیجه auth()->user() روی گارد web می‌ماند و همه‌ی @canها در رندر مجدد
        | false می‌شدند. حالا پیشوند «livewire-» هم پوشش داده می‌شود.
        */
        $path = $request->decodedPath();

        $isPanelContext = str_starts_with($path, 'livewire/')
            || str_starts_with($path, 'livewire-')
            || $request->is('admin-old/*')
            || $request->is('admin/'.admin_route_prefix().'*')
            || $request->is('sellers/*')
            || $request->is('sellers');

        if (! $isPanelContext) {
            return $next($request);
        }

        foreach (['adminPanel', 'seller', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                config()->set('auth.defaults.guard', $guard);
                break;
            }
        }

        return $next($request);
    }
}
