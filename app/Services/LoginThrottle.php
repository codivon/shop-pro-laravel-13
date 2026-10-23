<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/*
 * سرویس محدودساز تلاش ورود (ضد brute-force و credential stuffing)
 * - محدودیت بر اساس «نام کاربری + IP» اعمال می‌شود
 * - هر N تلاش ناموفق ⇒ قفل تدریجی: پنجره، پنجره×۲، ... (حداکثر ۲۴ ساعت)
 * - هر دو پنل (جدید/Livewire و قدیمی) از همین سرویس استفاده می‌کنند
 *
 * فاز ۴۴: سقف تلاش و پنجرهٔ دقیقه‌ای از تب «امنیت» تنظیمات برنامه‌نویس
 * (SecuritySettings) خوانده می‌شوند؛ کلید اصلی هم قابل خاموش‌کردن است.
 */
class LoginThrottle
{
    /** حداکثر تلاش مجاز قبل از قفل — پیش‌فرض؛ مقدار واقعی از تنظیمات خوانده می‌شود */
    public const MAX_ATTEMPTS = 5;

    /** مدت قفل پایه (ثانیه) — پیش‌فرض؛ مقدار واقعی = پنجرهٔ تنظیم‌شده */
    public const LOCK_SECONDS = 900; // ۱۵ دقیقه

    /** پنجره شمارش تلاش‌های ناموفق (ثانیه) — پیش‌فرض */
    public const FAILURE_WINDOW = 900;

    /** سقف تلاش بر اساس تنظیمات (نقطهٔ admin) */
    public static function maxAttempts(): int
    {
        return SecuritySettings::lockoutMax('admin');
    }

    /** پنجرهٔ شمارش تلاش (ثانیه) بر اساس تنظیمات */
    public static function failureWindow(): int
    {
        return SecuritySettings::lockoutWindowSeconds('admin');
    }

    /**
     * کلید شمارنده‌ی تلاش‌های ناموفق
     */
    public static function key(string $username, string $ip): string
    {
        return 'login:'.sha1(mb_strtolower(trim($username)).'|'.$ip);
    }

    /**
     * کلید قفل فعال
     */
    public static function lockKey(string $username, string $ip): string
    {
        return self::key($username, $ip).':lock';
    }

    /**
     * آیا ورود قفل شده است؟
     */
    public static function tooManyAttempts(string $username, string $ip): bool
    {
        if (! SecuritySettings::lockoutEnabled()) {
            return false;
        }

        return RateLimiter::tooManyAttempts(self::lockKey($username, $ip), 1);
    }

    /**
     * ثانیه‌های باقی‌مانده تا باز شدن قفل
     */
    public static function availableIn(string $username, string $ip): int
    {
        return max(1, RateLimiter::availableIn(self::lockKey($username, $ip)));
    }

    /**
     * ثبت یک تلاش ناموفق — با قفل تدریجی
     */
    public static function hitFailure(string $username, string $ip): void
    {
        if (! SecuritySettings::lockoutEnabled()) {
            return;
        }

        $key          = self::key($username, $ip);
        $maxAttempts  = self::maxAttempts();
        $window       = self::failureWindow();

        // اگر قفل فعال است، قفل را تمدید نکن — فقط صبر تا پایان
        if (RateLimiter::tooManyAttempts(self::lockKey($username, $ip), 1)) {
            return;
        }

        $hits = RateLimiter::hit($key, $window);

        // هر بار که به سقف رسید ⇒ قفل تدریجی و صفر شدن شمارنده
        if ($hits >= $maxAttempts) {
            $round   = max(1, intdiv($hits, $maxAttempts)); // دوره‌ی شکست: ۱، ۲، ۳، ...
            $seconds = (int) min($window * (2 ** ($round - 1)), 86400); // حداکثر ۲۴ ساعت

            RateLimiter::clear($key);                    // شمارنده‌ی تلاش‌ها صفر شود
            RateLimiter::hit(self::lockKey($username, $ip), $seconds); // قفل فعال شود
        }
    }

    /**
     * پاک‌سازی کامل پس از ورود موفق
     */
    public static function clear(string $username, string $ip): void
    {
        RateLimiter::clear(self::key($username, $ip));
        RateLimiter::clear(self::lockKey($username, $ip));
    }
}
