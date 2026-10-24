<?php

namespace App\Http\Middleware;

use App\Models\BotTrap;
use Closure;
use Illuminate\Http\Request;

/*
 * ربات‌گیر — میان‌افزار سراسری
 * IP هایی که قبلاً در تله‌های ربات ثبت شده‌اند، از کل سایت (وب + API) با 403 پاس داده می‌شوند.
 * لیست IP ها در کش نگه‌داری می‌شود تا در هر درخواست کوئری اضافه‌ای اجرا نشود.
 */
class BlockBots
{
    public function handle(Request $request, Closure $next)
    {
        // فاز ۴۵ — تا زمانی که نصب تمام نشده، تلهٔ ربات (وابسته به دیتابیس) بوت نمی‌شود
        if (! application_installed()) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($ip && BotTrap::isBlocked($ip)) {
            // فاز ۷۰ — مدیرِ واردشدهٔ پنل هرگز قفل نمی‌شود
            // (سناریوی قفل‌شدن: IP اشتراکی CGNAT یا پرشدن تله توسط خودِ مرورگر مدیر)
            // امنیت: مهاجمِ بیرون‌ایستاده نه سشن ادمین دارد نه با این bypass می‌تواند وارد شود.
            if (auth('adminPanel')->check()) {
                return $next($request);
            }

            // پاسخ استاندارد 403 — هیچ اطلاعاتی فاش نمی‌شود
            return response()->make('Forbidden', 403);
        }

        return $next($request);
    }
}
