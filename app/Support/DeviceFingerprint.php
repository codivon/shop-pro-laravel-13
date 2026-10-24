<?php

// app/Support/DeviceFingerprint.php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * فاز ۷۲ — تولید اثر انگشت دستگاه در یک نقطهٔ مرکزی
 *
 * قبلاً همین منطق سه بار (RecordAdminSession / CheckBlockedDevice / SessionIndex)
 * کپی شده بود؛ از این پس همه از همین کلاس استفاده می‌کنند.
 *
 * دو نوع اثر انگشت:
 *  - session(): مرورگر|سیستم‌عامل|نوع‌دستگاه|آیپی — کلید ردیف جدول admin_sessions
 *    («آیپی و مرورگر و سیستم» — مبنای تشخیص «سیستم اصلی» در محافظت ۴۸ ساعته)
 *  - device(): مرورگر.سیستم‌عامل.نوع‌دستگاه — بدون آیپی؛ برای بلاک دستگاه
 *    (بلاک دستگاه نباید با تغییر آیپی بی‌اثر شود)
 */
class DeviceFingerprint
{
    /** اثر انگشت نشست: مرورگر + سیستم‌عامل + نوع دستگاه + آیپی */
    public static function session(Request $request): string
    {
        $ua = (string) $request->userAgent();

        return md5(self::browserName($ua).'|'.self::platformName($ua).'|'.self::deviceType($ua).'|'.(string) $request->ip());
    }

    /** اثر انگشت دستگاه (بدون آیپی) — برای بلاک دستگاه
     * فرمت قدیمی md5(browser.platform.deviceType) بدون جداکننده حفظ شده است
     * تا بلاک‌های ثبت‌شدهٔ قبلی همچنان معتبر بمانند. */
    public static function device(string $userAgent): string
    {
        return md5(self::browserName($userAgent).self::platformName($userAgent).self::deviceType($userAgent));
    }

    /** اثر انگشت مرورگر (نام + نسخه) — برای بلاک مرورگر (فرمت قدیمی حفظ شده) */
    public static function browser(string $userAgent): string
    {
        return md5(self::browserName($userAgent).self::browserVersion($userAgent));
    }

    public static function browserVersion(string $userAgent): string
    {
        if (preg_match('/Chrome\/(\d+)/', $userAgent, $m)) return $m[1];
        if (preg_match('/Firefox\/(\d+)/', $userAgent, $m)) return $m[1];
        if (preg_match('/Edg\/(\d+)/', $userAgent, $m)) return $m[1];
        if (preg_match('/Safari\/(\d+)/', $userAgent, $m)) return $m[1];
        if (preg_match('/OPR\/(\d+)/', $userAgent, $m)) return $m[1];

        return 'unknown';
    }

    public static function browserName(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) return 'Safari';
        if (strpos($userAgent, 'Edg') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) return 'Opera';

        return 'Unknown';
    }

    public static function platformName(string $userAgent): string
    {
        if (strpos($userAgent, 'Windows') !== false) return 'Windows';
        if (strpos($userAgent, 'Mac') !== false) return 'macOS';
        if (strpos($userAgent, 'Linux') !== false && strpos($userAgent, 'Android') === false) return 'Linux';
        if (strpos($userAgent, 'Android') !== false) return 'Android';
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) return 'iOS';

        return 'Unknown';
    }

    public static function deviceType(string $userAgent): string
    {
        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/i', $userAgent)) return 'mobile';
        if (preg_match('/(ipad|tablet)/i', $userAgent)) return 'tablet';

        return 'desktop';
    }
}
