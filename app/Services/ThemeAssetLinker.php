<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * فاز ۶۵ — ساخت لینک دارایی‌های قالب (themes/X/src/resources/assets → public/themes/X)
 * با زنجیرهٔ فال‌بک کامل و idempotent:
 *
 *   ۱) symlink سالم از قبل موجود؟ → هیچ کاری لازم نیست
 *   ۲) تابع بومی symlink() (اگر روی هاست disable نشده باشد)
 *   ۳) exec با ln -s / mklink (فقط اگر تابع exec واقعاً موجود باشد!)
 *   ۴) کپی واقعی محتوا + فایل مارکر (تضمینی — روی هر هستی کار می‌کند)
 *
 * ریشهٔ باگ فاز ۶۵: File::link() لاراول روی لینوکس وقتی symlink غیرفعال باشد
 * بی‌دقت exec('ln -s …') می‌زند؛ روی هاست‌هایی که exec هم در disable_functions است
 * (هاست اشتراکی سخت‌گیرانه) خطای مهلک:
 *   «Call to undefined function Illuminate\Filesystem\exec()»
 * رخ می‌دهد و دکمهٔ «تنظیم به عنوان قالب» در پنل با ۵۰۰ می‌میرد.
 */
class ThemeAssetLinker
{
    /**
     * مارکر فایل داخل پوشه‌های کپی‌شده توسط این سرویس؛
     * برای تشخیص «کپی قدیمی خودمان» (نوسازی/ارتقای symlink) از پوشهٔ دست‌ساز ناشناس.
     */
    public const COPY_MARKER = '.shoplink-copy';

    /**
     * لینک یا کپی — همیشه بی‌خطر برای فراخوانی چندباره.
     *
     * پارامترهای allowSymlink/allowExec فقط برای تست‌پذیری‌اند؛
     * پیش‌فرض از قابلیت‌های واقعی PHP کشف می‌شوند (function_exists).
     *
     * @param  string      $target  مسیر هدف (پوشهٔ assets تم)
     * @param  string      $link    مسیر لینک در public
     * @param  bool|null   $allowSymlink
     * @param  bool|null   $allowExec
     * @return string حالت نهایی: symlink|exec|existing|existing-dir|copy
     *
     * @throws RuntimeException اگر هدف وجود نداشته باشد
     */
    public static function linkOrCopy(string $target, string $link, ?bool $allowSymlink = null, ?bool $allowExec = null): string
    {
        $allowSymlink = $allowSymlink ?? function_exists('symlink');
        $allowExec    = $allowExec    ?? function_exists('exec');

        if (!is_dir($target) && !is_file($target)) {
            throw new RuntimeException("هدف لینک وجود ندارد: {$target}");
        }

        // ۰) symlink سالم از قبل برقرار است → idempotent
        if (self::linkValid($link)) {
            return 'existing';
        }

        // symlink شکسته → پاک شود تا جایگزین ممکن باشد
        if (is_link($link)) {
            @unlink($link);
        }

        // مسیر به‌صورت پوشه/فایل واقعیِ موجود است:
        //  - کپی قدیمی خودمان (دارای مارکر) → نوسازی: پاک و از نو بساز
        //    (به‌روزرسانی تم + ارتقای خودکار کپی→symlink وقتی هاست اجازه داد)
        //  - پوشه/فایل ناشناس → دست نمی‌زنیم (از بین نبردن فایل دست‌ساز ادمین)
        if (file_exists($link)) {
            $isOurStaleCopy = is_dir($link) && is_file(rtrim($link, '/').'/'.self::COPY_MARKER);

            if (! $isOurStaleCopy) {
                return 'existing-dir';
            }

            File::deleteDirectory($link);
        }

        File::ensureDirectoryExists(dirname($link));

        // ۱) تابع بومی symlink — روی هاست‌های اشتراکی ممکن است disable باشد؛
        //    در آن حالت PHP فقط warning می‌دهد و false برمی‌گرداند نه استثنا
        if ($allowSymlink) {
            if (@symlink($target, $link) && self::linkValid($link)) {
                return 'symlink';
            }
            if (is_link($link)) {
                @unlink($link); // لینک نیمه‌ساخته/شکسته
            }
        }

        // ۲) دستور سیستم‌عامل — فقط با گارد function_exists (رفع باگ فاز ۶۵)
        if ($allowExec) {
            if (windows_os()) {
                $mode = is_dir($target) ? 'J' : 'H';
                @exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($target).' 2>&1', $out, $code);
            } else {
                @exec('ln -s '.escapeshellarg($target).' '.escapeshellarg($link).' 2>&1', $out, $code);
            }

            if (self::linkValid($link)) {
                return 'exec';
            }
            if (is_link($link)) {
                @unlink($link); // ln شکسته
            }
        }

        // ۳) فال‌بک تضمینی: کپی واقعی محتوا + مارکر مالکیت
        if (!file_exists($link)) {
            File::makeDirectory($link, 0755, true, true);
        }

        if (is_dir($target)) {
            File::copyDirectory($target, $link);

            /*
             * فاز ۸۳ — مارکر حالا «مُهر زمانی» هم دارد:
             * بیشترین mtime منبع در لحظهٔ کپی. ThemeAssetSync با مقایسهٔ
             * این عدد و mtime فعلیِ منبع، کهنگیِ کپی را بدون اسکن لینک تشخیص می‌دهد.
             * (مارکرهای متنی قدیمی به‌صورت (int) صفر خوانده می‌شوند → یک‌بار نوسازی خودکار)
             */
            @file_put_contents($link.'/'.self::COPY_MARKER, (string) ThemeAssetSync::maxMtime($target));
        } else {
            File::copy($target, $link);
        }

        return 'copy';
    }

    /** آیا مسیر به‌عنوان symlink سالم وجود دارد؟ (هدف موجود است) */
    protected static function linkValid(string $link): bool
    {
        return is_link($link) && @readlink($link) !== false && file_exists($link);
    }
}
