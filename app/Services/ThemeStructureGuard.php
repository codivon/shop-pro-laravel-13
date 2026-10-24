<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * فاز ۶۸ — اعتبارسنجی ساختار قالب قبل از فعال‌سازی.
 *
 * قرارداد پلتفرم: کلاس provider قالب باید در فضای‌نام
 *   Themes\{نام‌پوشه}\src\ThemeServiceProvider
 * باشد (مثل Themes\WeblakShop\src و Themes\SaniarTheme\src).
 *
 * اگر پوشهٔ قالب روی هاست «دستی» تغییرنام یابد ولی namespace داخل src همان قدیمی
 * بماند، سه اتفاق پنهان می‌افتاد:
 *   ۱) class_exists ناموفق → AppServiceProvider بی‌صدا provider را رد می‌کرد →
 *      هیچ روت فرانتی ثبت نمی‌شد → صفحهٔ اصلی ۴۰۴ خام (گزارش واقعی کاربر فاز ۶۸).
 *   ۲) فراخوانی دوم class_exists روی همان کلاس = include دوبارهٔ فایل =
 *      «Fatal: Cannot redeclare class …» (کشف‌شده در تست فاز ۶۸).
 *   ۳) پیام‌های خطای گمراه‌کننده در پنل.
 *
 * این سرویس فقط «یک بار» class_exists می‌زند و در صورت شکست، فایل provider را
 * بدون include (با تحلیل متن namespace) بررسی می‌کند تا پیام دقیق فارسی بدهد.
 */
class ThemeStructureGuard
{
    /** نام کلاس provider قالب بر اساس نام پوشه */
    public static function providerClass(string $themeDir): string
    {
        return "Themes\\{$themeDir}\\src\\ThemeServiceProvider";
    }

    /**
     * اعتبارسنجی کامل قبل از ثبت CURRENT_THEME.
     *
     * @param  string $themeDir  نام پوشهٔ قالب (مثلاً SaniarTheme)
     * @param  string $themesDir مسیر ریشهٔ قالب‌ها (مثلاً base_path('themes'))
     * @param  bool   $retryWithDumpOnStaleClassmap  اگر namespace درست بود ولی کلاس
     *         پیدا نشد (classmap کهنه)، یک‌بار dump-autoload و تلاش مجدد
     * @return void
     *
     * @throws RuntimeException با پیام فارسی دقیق در صورت نامعتبر بودن ساختار
     */
    public static function assertActivatable(string $themeDir, string $themesDir, bool $retryWithDumpOnStaleClassmap = true): void
    {
        $providerClass = self::providerClass($themeDir);
        $providerFile  = rtrim($themesDir, '/')."/{$themeDir}/src/ThemeServiceProvider.php";

        if (class_exists($providerClass)) {
            return; // ✓ ساختار سالم
        }

        if (! is_file($providerFile)) {
            throw new RuntimeException(
                "ساختار قالب «{$themeDir}» معتبر نیست: فایل src/ThemeServiceProvider.php یافت نشد."
            );
        }

        $source    = (string) file_get_contents($providerFile);
        $namespace = self::declaredNamespace($source);

        if ($namespace !== null && $namespace === "Themes\\{$themeDir}\\src") {
            // فضای‌نام درست است ولی کلاس رزول نشد → به احتمال زیاد autoload
            // بهینه‌شدهٔ کهنه (classmap) است؛ نوسازی امن و تلاش مجدد.
            if ($retryWithDumpOnStaleClassmap) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('dump-autoload');
                } catch (Throwable $e) {
                    report($e);
                }

                if (class_exists($providerClass)) {
                    return; // ✓ پس از نوسازی autoload
                }
            }

            throw new RuntimeException(
                "کلاس {$providerClass} با وجود فایل و فضای‌نام درست بارگذاری نشد؛ autoload سرور را نوسازی کنید (composer dump-autoload)."
            );
        }

        $actual = $namespace ?? '(فضای‌نام نامشخص)';

        throw new RuntimeException(
            "ساختار قالب «{$themeDir}» معتبر نیست: فضای‌نام واقعی کلاس provider ({$actual}) ".
            "با نام پوشه هم‌خوانی ندارد. نام پوشهٔ قالب در themes/ باید دقیقاً همان بخش ".
            "دوم فضای‌نام PHP قالب باشد (الزامی: Themes\\{$themeDir}\\src\\ThemeServiceProvider)."
        );
    }

    /**
     * استخراج namespace تعریف‌شده در ابتای فایل PHP — بدون include شدن فایل.
     *
     * @return string|null مثلاً 'Themes\SaniarTheme\src'
     */
    public static function declaredNamespace(string $phpSource): ?string
    {
        if (preg_match('/^namespace\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\s*;/m', $phpSource, $m)) {
            return $m[1];
        }

        return null;
    }
}
