<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

/*
 * سرویس محدودساز تلاش ورود (ضد brute-force و credential stuffing)
 * - محدودیت بر اساس «نام کاربری + IP» اعمال می‌شود
 * - هر ۵ تلاش ناموفق ⇒ قفل تدریجی: ۱۵ دقیقه، ۳۰ دقیقه، ۱ ساعت، ... (حداکثر ۲۴ ساعت)
 * - هر دو پنل (جدید/Livewire و قدیمی) از همین سرویس استفاده می‌کنند
 */
class LoginThrottle
{
    /** حداکثر تلاش مجاز قبل از قفل */
    public const MAX_ATTEMPTS = 5;

    /** مدت قفل پایه (ثانیه) */
    public const LOCK_SECONDS = 900; // ۱۵ دقیقه

    /** پنجره شمارش تلاش‌های ناموفق (ثانیه) */
    public const FAILURE_WINDOW = 900;

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
        $key = self::key($username, $ip);

        // اگر قفل فعال است، قفل را تمدید نکن — فقط صبر تا پایان
        if (RateLimiter::tooManyAttempts(self::lockKey($username, $ip), 1)) {
            return;
        }

        $hits = RateLimiter::hit($key, self::FAILURE_WINDOW);

        // هر بار که به سقف رسید ⇒ قفل تدریجی و صفر شدن شمارنده
        if ($hits >= self::MAX_ATTEMPTS) {
            $round   = intdiv($hits, self::MAX_ATTEMPTS); // دوره‌ی شکست: ۱، ۲، ۳، ...
            $seconds = (int) min(self::LOCK_SECONDS * (2 ** ($round - 1)), 86400); // حداکثر ۲۴ ساعت

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
