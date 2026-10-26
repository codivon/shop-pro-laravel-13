<?php

namespace Modules\CustomerClub\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Modules\CustomerClub\Services\LicenseGuard;

class CheckModuleLicense
{
    /**
     * بررسی اعتبار لایسنس ماژول باشگاه مشتریان قبل از اجرای درخواست.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!LicenseGuard::check('customer-club')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'لایسنس پکیج باشگاه مشتریان نامعتبر یا منقضی شده است.',
                ], 403);
            }

            return response()->view('customer-club::errors.license_invalid', [], 403);
        }

        return $next($request);
    }
}
