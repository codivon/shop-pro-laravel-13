<?php

namespace App\Services;

/*
 * لایهٔ مرکزی تنظیمات امنیتی (فاز ۴۴)
 *
 * همهٔ کلیدها در جدول options نگهداری می‌شوند و از تب «امنیت» در
 * تنظیمات برنامه‌نویس (/admin/{prefix}/developer) مدیریت می‌شوند.
 * مقدار پیش‌فرض هر کلید دقیقاً معادل رفتار قبل از این فاز است تا
 * با فعال/غیرفعال کردن هیچ‌چیزِ موجود خراب نشود.
 */
class SecuritySettings
{
    /* نقاط کپچا */
    public const CAPTCHA_POINTS = ['admin_login', 'register', 'customer_login', 'comments'];

    /* نقاط قفل تلاش‌ها */
    public const LOCKOUT_POINTS = ['admin', 'customer', 'comments'];

    /* کانال‌های 2FA */
    public const TWO_FACTOR_CHANNELS = ['email', 'sms', 'both'];

    /* ================= کپچا ================= */

    /** کلید اصلی کپچا — پیش‌فرض: از config/security.php (رفتار قبلی) */
    public static function captchaMasterEnabled(): bool
    {
        return option(
            'security_captcha_enabled',
            config('security.login_captcha', true) ? 'on' : 'off'
        ) === 'on';
    }

    /** آیا کپچا برای این نقطه فعال است؟ (کلید اصلی × کلید نقطه) */
    public static function captchaEnabled(string $point): bool
    {
        if (! in_array($point, self::CAPTCHA_POINTS, true) || ! self::captchaMasterEnabled()) {
            return false;
        }

        $defaults = [
            'admin_login'    => 'on',  // قبلاً هم روی ورود مدیر فعال بود
            'register'       => 'on',  // قبلاً هم روی ثبت‌نام فعال بود
            'customer_login' => 'off', // نقطهٔ جدید
            'comments'       => 'off', // نقطهٔ جدید
        ];

        return option('security_captcha_'.$point, $defaults[$point] ?? 'off') === 'on';
    }

    /** تلهٔ زمان: حداقل ثانیهٔ پرکردن فرم — ۰ یعنی غیرفعال */
    public static function timeTrapSeconds(): int
    {
        return max(0, (int) option('security_time_trap_seconds', (string) BotDetector::MIN_FORM_SECONDS));
    }

    /* ================= هانی‌پات ================= */

    public static function honeypotEnabled(): bool
    {
        return option('security_honeypot_enabled', 'on') === 'on';
    }

    /* ================= قفل تلاش‌ها ================= */

    public static function lockoutEnabled(): bool
    {
        return option('security_lockout_enabled', 'on') === 'on';
    }

    /** سقف تلاش برای نقطهٔ admin|customer|comments */
    public static function lockoutMax(string $point): int
    {
        $defaults = ['admin' => LoginThrottle::MAX_ATTEMPTS, 'customer' => 5, 'comments' => 5];

        return max(1, (int) option('security_lockout_'.$point.'_max', (string) ($defaults[$point] ?? 5)));
    }

    /** پنجرهٔ دقیقه‌ای شمارش تلاش‌ها */
    public static function lockoutMinutes(string $point): int
    {
        $defaults = ['admin' => 15, 'customer' => 1, 'comments' => 10];

        return max(1, (int) option('security_lockout_'.$point.'_minutes', (string) ($defaults[$point] ?? 10)));
    }

    /** پنجره به ثانیه */
    public static function lockoutWindowSeconds(string $point): int
    {
        return self::lockoutMinutes($point) * 60;
    }

    /* ================= هدرهای امنیتی ================= */

    public static function headersEnabled(): bool
    {
        return option('security_headers_enabled', 'on') === 'on';
    }

    public static function hstsEnabled(): bool
    {
        return option('security_hsts_enabled', 'off') === 'on';
    }

    /* ================= ورود دومرحله‌ای مدیر (2FA) ================= */

    public static function twoFactorEnabled(): bool
    {
        return option('admin_2fa_enabled', 'off') === 'on';
    }

    /** email | sms | both */
    public static function twoFactorChannel(): string
    {
        $channel = (string) option('admin_2fa_channel', 'email');

        return in_array($channel, self::TWO_FACTOR_CHANNELS, true) ? $channel : 'email';
    }

    /** شمارهٔ مقصد پیامک 2FA — از تنظیمات پیامک پنل (admin_mobile_number) */
    public static function twoFactorMobile(): ?string
    {
        $mobile = trim((string) option('admin_mobile_number'));

        return $mobile !== '' ? $mobile : null;
    }

    /** ایمیل مقصد کد (همیشه موجود است؛ در نبود ایمیل، 2FA ایمیلی غیرفعال می‌شود) */
    public static function twoFactorEmailTargets(\App\Models\Admin $admin): ?string
    {
        return filled($admin->email) ? (string) $admin->email : null;
    }
}
