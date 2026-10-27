<?php

namespace App\Support;

/*
 * لایهٔ مرکزی تنظیمات Laravel Pulse (فاز ۱۰۴)
 *
 * پیش‌فرضِ پکیج laravel/pulse گیتِ viewPulse را فقط در محیط local باز تعریف می‌کند
 * (vendor/laravel/pulse/src/PulseServiceProvider.php: fn () => $app->environment('local'))
 * — یعنی روی هاست تولیدی، حتی «سازندهٔ» سایت هم هنگام باز کردن /pulse با ۴۰۳ مواجه می‌شد.
 *
 * تعریف گیت در AuthServiceProvider (بعد از بوتِ پکیج‌ها اجرا می‌شود) این پیش‌فرض را
 * override می‌کند: ادمینِ لاگین‌کردهٔ پنل (گارد adminPanel) + سوییچ تنظیماتیِ زیر
 * (قاعدهٔ طلایی: هر امکان حداقل یک سوییچ فعال/غیرفعال در تنظیمات دارد).
 *
 * محل سوییچ: پنل مدیریت ← تنظیمات برنامه‌نویس ← تب عمومی («داشبورد Pulse برای مدیران»)
 */
class PulseSettings
{
    /** دسترسی مدیران پنل به داشبورد /pulse — '1' روشن / '0' خاموش */
    public const KEY_ADMIN_ACCESS = 'pulse_admin_access';

    /**
     * آیا داشبورد Pulse برای مدیرانِ لاگین‌کردهٔ پنل باز است؟
     * (ناپدید بودن کلید = روشن — نصب تازه نباید مانیتورینگ را قفل کند)
     */
    public static function adminAccess(): bool
    {
        return option(self::KEY_ADMIN_ACCESS, '1') === '1';
    }

    /** نوشتن سوییچ (با قرارداد '1'/'0' فازهای ۸۳/۹۹) */
    public static function setAdminAccess(bool $on): void
    {
        option_update(self::KEY_ADMIN_ACCESS, $on ? '1' : '0');
    }
}
