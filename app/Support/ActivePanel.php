<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

/**
 * تشخیص «پنل فعال» — فاز ۳۴
 * --------------------------------------------------------------------------
 * تا فاز ۳۲ هر ماژول ActivePanel خودش را داشت (Modules\X\Support\ActivePanel)
 * و فقط ویوهایی که خودشان @extends شرطی داشتند دو-پنلی می‌شدند. مشکل عملی:
 * نسخه‌های «قدیمی» پکیج‌ها که روی سرورهای کاربر نصب است هیچ شرطی ندارند و همیشه
 * back.layouts.master (کروم قدیمی) را رندر می‌کنند.
 *
 * حالا «میزبان» تصمیم می‌گیرد: هر دو لایه‌ی میزبان (back.layouts.master و
 * admin-panel::layouts.module) قبل از انتخاب chrome از همین کلاس می‌پرسند؛
 * بنابراین حتی پکیج‌های قدیمی هم داخل کروم پنل فعال رندر می‌شوند و برای
 * پروژه‌های آینده هم هیچ قراردادی روی پکیج تحمیل نمی‌شود.
 *
 * اولویت تشخیص:
 *   ۱. پارامتر ?ap=new|old  (لینک‌های منوی پکیج‌ها در سایدبار جدید آن را اضافه
 *      می‌کنند → با دو تبِ همزمانِ پنل قدیمی/جدید هم قطعی کار می‌کند)
 *   ۲. مسیر admin-old/...  → قطعاً پنل قدیمی
 *   ۳. کوکی ap_panel       (هر chrome هنگام لود ست می‌کند؛ آخرین بازدید برنده است)
 *   ۴. پیش‌فرض: پنل جدید
 */
class ActivePanel
{
    /** پنل جدید (Livewire) فعال باشد؟ */
    public static function useNewPanel(): bool
    {
        // ۱) پارامتر صریح لینک (?ap=new|old)
        $explicit = request()->query('ap');

        if ($explicit === 'new') {
            return true;
        }

        if ($explicit === 'old') {
            return false;
        }

        // ۲) مسیر پنل قدیمی همیشه قدیمی است (حتی اگر کوکی جدید بگوید)
        if (request()->is('admin-old/*') || request()->is('admin-old')) {
            return false;
        }

        // ۳) کوکی «آخرین پنل بازدیدشده»
        if (request()->cookie('ap_panel') === 'old') {
            return false;
        }

        // ۴) پیش‌فرض: پنل جدید
        return true;
    }

    /** کروم پنل جدید برای صفحات کلاسیک وجود دارد؟ (پروژه‌های آینده ممکن است نداشته باشند) */
    public static function hasNewChrome(): bool
    {
        return View::exists('admin-panel::layouts.module-new');
    }

    /**
     * لایه‌ی «واقعی»ای که باید @extends شود؛ صفحات کلاسیک (پکیج‌ها + صفحات قدیمی
     * خود میزبان) از این استفاده می‌کنند تا فقط «یک» نقطه تصمیم وجود داشته باشد.
     * همیشه به لایه‌ی واقعی اشاره می‌کند (نه wrapper) تا ریکورشن رخ ندهد.
     */
    public static function layoutToExtend(): string
    {
        if (self::useNewPanel() && self::hasNewChrome()) {
            return 'admin-panel::layouts.module-new';
        }

        return 'back.layouts.master-legacy';
    }
}
