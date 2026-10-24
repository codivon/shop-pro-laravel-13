<?php

namespace App\Http\Middleware;

use App\Services\SecuritySettings;
use Closure;
use Illuminate\Http\Request;

/*
 * هدرهای امنیتی استاندارد برای همه‌ی پاسخ‌ها
 * - X-Frame-Options: جلوگیری از clickjacking
 * - X-Content-Type-Options: جلوگیری از MIME-sniffing
 * - Referrer-Policy: عدم افشای URL کامل به سایت‌های بیرونی
 * - Permissions-Policy: قطع دسترسی مرورگر به دوربین/میکروفون/موقعیت
 * CSP سراسری فعال نمی‌شود چون اسکریپت‌های inline زیادی در پنل و قالب وجود دارد
 * (فعال‌سازی ناگهانی آن سایت را می‌شکند) — در فاز بعدی با nonce قابل افزودن است.
 *
 * فاز ۴۴: کلید اصلی از تب «امنیت» تنظیمات برنامه‌نویس + HSTS جداگانه
 * (فقط روی پاسخ‌های HTTPS ارسال می‌شود — مطابق RFC 6797).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /*
         * فاز ۵۱ — تا پیش از نصب، وضعیت هدرها از دیتابیس پرسیده نمی‌شود.
         * این میدلور سراسری است و روی خود صفحات /install هم اجرا می‌شود؛
         * SecuritySettings::headersEnabled() از طریق option() به دیتابیس
         * می‌رسد و روی کلون خام (بدون نشان installed و با اعتبارنامه‌ی خالی
         * .env خودکار) درخواست نصب را ۵۰۰ می‌کرد (لاگ واقعی سرور کاربر).
         */
        if (! application_installed()) {
            return $response;
        }

        if (! SecuritySettings::headersEnabled()) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // HSTS فقط روی اتصال امن معنا دارد؛ در HTTP نادیده گرفته می‌شود و نمی‌فرستیم
        if (SecuritySettings::hstsEnabled() && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
