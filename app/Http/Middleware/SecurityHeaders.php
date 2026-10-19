<?php

namespace App\Http\Middleware;

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
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        return $response;
    }
}
