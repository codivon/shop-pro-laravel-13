<?php

namespace App\Support;

/**
 * گیت self-heal بوتِ پکیج‌ها — فاز ۹۷ (بستن بدهی کشف‌شدهٔ فاز قبل).
 *
 * زمینه: ۴ پکیج (ArtificialIntelligence/Accounting/Logistics/CustomerClub) در
 * هر بوت/ریکوئست چند کوئری self-heal می‌زنند (ensureInDatabase/ensureSeeded/
 * seedMissingDefaults) — روی پروداکشن FPM یعنی ~۵-۶ کوئری اضافه در هر ریکوئست.
 *
 * راه‌حل: مارکر فایل در storage/framework با TTL (پیش‌فرض ۶ ساعت) — وقتی مارکر
 * تازه است، کوئری‌های self-heal از بوت حذف می‌شوند؛ در انقضای TTL یک‌بار
 * بازبینی می‌شود (خاصیت خودترمیم حفظ می‌شود).
 *
 * قواعد ایمنی:
 *  - در تست‌ها (runningUnitTests) همیشه true → رفتار پیشین بدون تغییر و کل
 *    مجموعهٔ تست بدون ریسک رول‌بک/استاتیک سبز می‌ماند.
 *  - شکست هر سیستمی (دیسک فقط‌خواندنی و…) → true یعنی self-heal مثل قبل اجرا
 *    می‌شود؛ هرگز صفحه‌ای نمی‌شکند.
 *  - کامندهای «بازگردانی/نصب» می‌توانند با forget() بوت بعدی را وادار به
 *    بازبینی کنند.
 */
class PackageSelfHeal
{
    /** TTL پیش‌فرض مارکر — ۶ ساعت */
    public const TTL = 21600;

    /** آیا self-heal این پکیج باید در این بوت اجرا شود؟ */
    public static function shouldRun(string $packageKey, int $ttl = self::TTL): bool
    {
        // در تست‌ها رفتار پیشین — بدون مارکر (رول‌بک تراکنش‌ها خراب نشود)
        if (app()->runningUnitTests()) {
            return true;
        }

        try {
            $path = self::path($packageKey);

            if (is_file($path) && (time() - (int) @filemtime($path)) < $ttl) {
                return false;
            }
        } catch (\Throwable $e) {
            // دسترسی فایل نامطمئن → self-heal مثل قبل اجرا شود
            return true;
        }

        return true;
    }

    /** ثبت مارکر پس از self-heal موفق */
    public static function markDone(string $packageKey): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            @touch(self::path($packageKey));
        } catch (\Throwable $e) {
            // سکوت — فقط یعنی درخواست‌های بعدی دوباره self-heal می‌زنند
        }
    }

    /** باطل‌کردن مارکر — برای کامندهای بازگردانی/نصب */
    public static function forget(string $packageKey): void
    {
        try {
            @unlink(self::path($packageKey));
        } catch (\Throwable $e) {
            // سکوت
        }
    }

    protected static function path(string $packageKey): string
    {
        $key = preg_replace('/[^a-z0-9_-]/i', '', $packageKey) ?: 'package';

        return storage_path('framework/selfheal-'.$key.'.flag');
    }
}
