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

        foreach (['adminPanel', 'seller', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                config()->set('auth.defaults.guard', $guard);
                break;
            }
        }

        return $next($request);
    }
}
