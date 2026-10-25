<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * فاز ۸۳ — همگام‌سازی خودکار دارایی‌های تم وقتی روی هاست «کپی واقعی» است.
 *
 * ریشهٔ باگ: ThemeAssetLinker روی هاست‌هایی که symlink و exec غیرفعال دارند
 * (هاست اشتراکی سخت‌گیرانه — فاز ۶۵) دارایی‌های تم را «کپی واقعی» می‌کند:
 *   themes/X/src/resources/assets → public/themes/X
 * بعد از هر git pull منبع (src) به‌روز می‌شود ولی کپیِ public کهنه می‌ماند —
 * یعنی رفع‌های JS/CSS فرانت (مثل دکمهٔ هشدار کاهش قیمت فاز ۸۱) هرگز به سایت
 * نمی‌رسیدند و کاربر «تغییرات را نمی‌دید».
 *
 * راه‌حل: در هر بوت (با کش اسکن) اگر پوشهٔ public «کپی خودمان» باشد (مارکر
 * .shoplink-copy)، بیشترین mtime منبع با مُهرِ داخل مارکر مقایسه می‌شود؛
 * منبعِ جدیدتر → پاک و از نو کپی. پس از `git pull` + `optimize:clear` (که کش را
 * خالی می‌کند) اولین بازدید، دارایی‌ها را خودکار تازه می‌کند — بدون shop:link دستی.
 *
 * امنیت: اگر پوشهٔ public مارکر ما را ندارد (symlink سالم یا پوشهٔ دست‌ساز ادمین)
 * هیچ دستی زده نمی‌شود.
 */
class ThemeAssetSync
{
    /** کلید کشِ نتیجهٔ اسکن maxMtime (اسکن هر نیم‌ساعت یک‌بار کافی است) */
    public const SCAN_CACHE_KEY = 'theme.assets.scan.';

    public const CACHE_TTL = 1800; // ۳۰ دقیقه

    /**
     * @param  string  $theme  نام پوشهٔ تم (مثل WeblakShop)
     * @param  bool|null  $force  نوسازی اجباری برای تست/کامند
     * @param  string|null  $srcOverride  مسیر منبع جایگزین (تست‌پذیری)
     * @param  string|null  $linkOverride  مسیر لینک جایگزین (تست‌پذیری)
     * @return array{status: string, stamp?: int}
     *
     * status ها: no-source | no-link | not-our-copy | fresh | refreshed
     */
    public static function syncIfStale(string $theme, ?bool $force = null, ?string $srcOverride = null, ?string $linkOverride = null): array
    {
        $src = $srcOverride ?: base_path("themes/{$theme}/src/resources/assets");
        $link = $linkOverride ?: public_path("themes/{$theme}");

        if (! is_dir($src)) {
            return ['status' => 'no-source'];
        }

        if (! is_dir($link)) {
            return ['status' => 'no-link'];
        }

        $marker = rtrim($link, '/').'/'.ThemeAssetLinker::COPY_MARKER;

        // symlink سالم یا پوشهٔ دست‌سازِ ناشناس → دست نمی‌زنیم
        if (! is_file($marker)) {
            return ['status' => 'not-our-copy'];
        }

        // مارکرهای متنی قدیمی (فاز ۶۵) به‌صورت ۰ خوانده می‌شوند → یک‌بار نوسازی خودکار
        $stamp = (int) @file_get_contents($marker);

        $maxMtime = Cache::remember(self::SCAN_CACHE_KEY.$theme, self::CACHE_TTL, fn () => self::maxMtime($src));

        if ($maxMtime <= $stamp && ! $force) {
            return ['status' => 'fresh'];
        }

        // کپی کهنه است → پاک و از نو بساز (ThemeAssetLinker خودش مُهر جدید را می‌نویسد)
        // گارد: اگر روزی لینکِ ارتقایافته به symlink بود، فقط خودِ لینک حذف شود نه
        // محتوای منبع (deleteDirectory روی symlink محتوای هدف را پاک می‌کند!)
        if (is_link($link)) {
            @unlink($link);
        } else {
            File::deleteDirectory($link);
        }

        ThemeAssetLinker::linkOrCopy($src, $link);

        return ['status' => 'refreshed', 'stamp' => self::maxMtime($src)];
    }

    /** بیشترین mtime فایل‌های یک پوشه به‌صورت بازگشتی (بدون استثنا) */
    public static function maxMtime(string $dir): int
    {
        $max = 0;

        try {
            foreach (File::allFiles($dir) as $file) {
                $mtime = $file->getMTime();

                if ($mtime > $max) {
                    $max = $mtime;
                }
            }
        } catch (Throwable) {
            // مسیر ناخوانا/دسترسی — بی‌خطر
        }

        return $max;
    }
}
