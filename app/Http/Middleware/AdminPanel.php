<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * احراز هویت پنل مدیریت جدید (Livewire)
 * در صورت عدم ورود، به صفحه ورود پنل جدید هدایت می‌شود.
 */
class AdminPanel
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('adminPanel')->check()) {
            config()->set('auth.defaults.guard', 'adminPanel');
            return $next($request);
        }

        return redirect()->route('np.login');
    }
}
