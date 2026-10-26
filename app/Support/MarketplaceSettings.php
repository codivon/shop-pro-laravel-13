<?php

namespace App\Support;

/*
 * لایهٔ مرکزی تنظیمات بازارچه (فاز ۹۹)
 *
 * همهٔ کلیدها در جدول options نگهداری می‌شوند (همان مکانیزم استاندارد هسته:
 * option() / option_update() + کش per-language) و از تب «بازارچه» در
 * تنظیمات برنامه‌نویس (/admin/{prefix}/developer) مدیریت می‌شوند.
 *
 * قاعدهٔ طلایی مدیر پروژه: «هر امکانی که اضافه می‌شود از تنظیمات قابل
 * شخصی‌سازی باشد؛ حداقل فعال/غیرفعال کردن» — بازارچه پیش از این فاز هیچ
 * کلید تنظیماتی نداشت؛ هر ۷ کلید این کلاس گیت واقعی در کد دارند و
 * پیش‌فرض هر کلید دقیقاً معادل رفتار قبل از این فاز است تا با
 * فعال/غیرفعال کردن هیچ‌چیزِ موجود خراب نشود.
 *
 * قرارداد مقدار: '1' روشن / '0' خاموش (هم‌خوان با گیت 'enabled' منوی
 * سایدبار فاز ۸۳ که مقدار را با option($key, '1') != '1' می‌سنجد).
 *
 * تصمیم مستند دربارهٔ کلید اصلی:
 *   packages_market_enabled فقط «بازارچه» (مرور/خرید/نصب از سرور بازار)
 *   را می‌بندد؛ صفحهٔ «ماژول‌های نصب‌شده» همیشه در دسترس می‌ماند — چون
 *   فعال/غیرفعال‌سازی و مدیریت پکیجِ خراب حیاتی است و به سرور بازار
 *   وابسته نیست.
 */
class MarketplaceSettings
{
    /** کلید گزینه‌ای کلید اصلی — منوی «بازار» + صفحات packages/* را می‌بندد */
    public const KEY_ENABLED = 'packages_market_enabled';

    /**
     * تعداد آیتم هر صفحهٔ بازار — مقدار قبل از این فاز در PackageIndex
     * hard-code شده بود (per_page=12 برای پکیج‌ها و قالب‌ها).
     */
    public const DEFAULT_PER_PAGE = 12;

    /** سقف امن تعداد در صفحه (ورودی عددی ادمین محدود می‌شود) */
    public const MAX_PER_PAGE = 60;

    /* ================= کلید اصلی ================= */

    /** کلید اصلی بازارچه — خاموش ⇒ منوی بازار مخفی + صفحات packages پیام تمیز «غیرفعال» */
    public static function marketEnabled(): bool
    {
        return option(self::KEY_ENABLED, '1') === '1';
    }

    /* ================= گیت‌های عملیات ================= */

    /** نصب/خرید/به‌روزرسانی پکیج و قالب از بازار — خاموش ⇒ فقط مرور */
    public static function allowInstall(): bool
    {
        return option('packages_market_allow_install', '1') === '1';
    }

    /** حذف پکیج/قالب (از بازار و صفحهٔ نصب‌شده‌ها) — خاموش ⇒ دکمهٔ حذف مخفی + گارد سروری */
    public static function allowUninstall(): bool
    {
        return option('packages_market_allow_uninstall', '1') === '1';
    }

    /** تب قالب‌ها — خاموش ⇒ تب و بخش قالب‌ها مخفی (در تب «همه» هم) */
    public static function showTemplates(): bool
    {
        return option('packages_market_show_templates', '1') === '1';
    }

    /**
     * چک خودکار به‌روزرسانی هنگام ورود به بازار (فاز ۹۹ — قابلیت جدید):
     * پیش از این فاز هیچ چک خودکاری وجود نداشت (دکمهٔ دستی «بررسی آپدیت‌ها»)؛
     * پس پیش‌فرض این کلید «خاموش» است = دقیقاً رفتار امروز. روشن ⇒ mountِ
     * بازار، checkUpdates را خودکار اجرا می‌کند (دکمهٔ دستی هم می‌ماند).
     */
    public static function autoCheckUpdates(): bool
    {
        return option('packages_market_auto_check_updates', '0') === '1';
    }

    /** تعداد آیتم هر صفحهٔ بازار (پکیج‌ها و قالب‌ها) */
    public static function itemsPerPage(): int
    {
        $value = (int) option('packages_market_items_per_page', (string) self::DEFAULT_PER_PAGE);

        if ($value < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($value, self::MAX_PER_PAGE);
    }

    /** متن بنر اختیاری بالای بازار — خالی ⇒ بدون بنر */
    public static function noticeText(): string
    {
        return trim((string) option('packages_market_notice_text', ''));
    }

    /* ================= پیام‌های استاندارد گیت‌ها ================= */

    /** پیام صفحه/اکشن وقتی کلید اصلی خاموش است */
    public static function disabledMessage(): string
    {
        return 'بازارچه از تنظیمات غیرفعال شده است.';
    }

    /** پیام گارد سروری نصب/به‌روزرسانی */
    public static function installDisabledMessage(): string
    {
        return 'نصب و به‌روزرسانی پکیج از تنظیمات بازارچه غیرفعال شده است.';
    }

    /** پیام گارد سروری حذف */
    public static function uninstallDisabledMessage(): string
    {
        return 'حذف پکیج از تنظیمات بازارچه غیرفعال شده است.';
    }
}
