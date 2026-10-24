<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * مهمان: هدایت ادمینِ واردشده به داشبورد پنل جدید
 */
class GuestAdminPanel
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (empty($guards)) {
            $guards = ['adminPanel'];
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if (Route::has('np.dashboard')) {
                    return redirect()->route('np.dashboard');
                }

                return redirect('/');
            }
        }

        return $next($request);
    }
}
