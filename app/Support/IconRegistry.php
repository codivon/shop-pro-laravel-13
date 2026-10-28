<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| فاز ۱۱۴ — رجیستری آیکون‌ها (Icon Picker)
|--------------------------------------------------------------------------
| منبع مشترک «انتخابگر آیکون» و «رندر مقادیر ذخیره‌شده»:
|
| فرمت‌های پذیرفته‌شده در یک مقدار آیکون (برای سازگاری با داده‌های قدیمی):
|   ۱) SVG خام                → <svg ...>...</svg>            (سیدهای لندینگ فروشندگان)
|   ۲) نام آیکون Lucide       → sparkles                       (نقشهٔ icons-map.php)
|   ۳) کلاس فونت‌آیکون        → fa-solid fa-store | mdi mdi-store
|   ۴) کلاس + استایل (قرارداد) → mdi mdi-store|color:#ef4444;font-size:24px
|      بخش‌ها با «|» جدا می‌شوند؛ اولین بخش کلاس، بقیه declarations استایل.
*/

class IconRegistry
{
    /** @var array<string, string>|null نقشهٔ Font Awesome (name => codepoint) */
    private static ?array $fa = null;

    /** @var array<string, string>|null نقشهٔ MDI (name => codepoint) */
    private static ?array $mdi = null;

    /** @var array<string, string>|null نقشهٔ Lucide (name => inner SVG) */
    private static ?array $lucide = null;

    /**
     * سوییچ برنامه‌نویس (قانون طلایی) — خاموش بودن یعنی فیلدهای آیکون
     * به‌صورت متن ساده بمانند و دکمهٔ انتخابگر رندر نشود.
     */
    public static function pickerEnabled(): bool
    {
        return option('admin_icon_picker', '1') === '1';
    }

    public static function fa(): array
    {
        return self::$fa ??= require resource_path('views/admin-panel/fa-icons-map.php');
    }

    public static function mdi(): array
    {
        return self::$mdi ??= require resource_path('views/admin-panel/mdi-icons-map.php');
    }

    public static function lucide(): array
    {
        return self::$lucide ??= require resource_path('views/admin-panel/icons-map.php');
    }

    /* ---------------- قرارداد class|style ---------------- */

    /** بخش کلاسِ مقدار (قبل از اولین |) */
    public static function iconClass(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return trim(explode('|', $value, 2)[0]);
    }

    /** بخش استایلِ مقدار (بعد از |) — خروجی آماده برای اتریبیوت style */
    public static function iconStyle(?string $value): string
    {
        $value = trim((string) $value);

        if (! str_contains($value, '|')) {
            return '';
        }

        return trim(explode('|', $value, 2)[1]);
    }

    /**
     * آیا این مقدار شبیه «کلاس فونت‌آیکون» است؟ (fa- / mdi / feather / icon- / bi- / ti- / la- / ion-)
     * نام‌های Lucide (مثل sparkles یا circle-alert) هرگز این الگو را ندارند.
     */
    public static function looksLikeFontIcon(?string $class): bool
    {
        $class = trim((string) $class);

        if ($class === '') {
            return false;
        }

        foreach (preg_split('/\s+/', $class) ?: [] as $token) {
            if (preg_match('/^(fa|mdi|feather|bi|ti|la|ion|icon)(-|$)/i', $token) === 1) {
                return true;
            }
        }

        return false;
    }

    /* ---------------- رندر عمومی ---------------- */

    /**
     * هر مقدار آیکون پذیرفته‌شده را به HTML قابل‌نمایش تبدیل می‌کند.
     *
     * @param  string|null  $value  مقدار ذخیره‌شده (SVG / نام Lucide / کلاس FA یا MDI / class|style)
     * @param  string  $class  کلاس پیش‌فرض عنصر (اندازه و رنگ از طریق توکن‌های ap-*)
     */
    public static function render(?string $value, string $class = ''): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // ۱) SVG خام — همان‌طور که هست (فاز ۱۱۴: رنگ/اندازهٔ انتخابگر داخل خود SVG است)
        if (str_starts_with($value, '<svg')) {
            return $value;
        }

        // ۲) کلاس فونت‌آیکون (FA / MDI / feather / کلاس‌های قدیمی icon-* و …)
        //    تشخیص: حداقل یک توکن با پیشوند شناخته‌شده شروع شود (fa- / mdi / feather / icon- / bi- / ti- / la- / ion-)
        if (self::looksLikeFontIcon(self::iconClass($value))) {
            $style = self::iconStyle($value);

            return '<i class="'.htmlspecialchars(self::iconClass($value), ENT_QUOTES, 'UTF-8').'"'
                .($style !== '' ? ' style="'.htmlspecialchars($style, ENT_QUOTES, 'UTF-8').'"' : '')
                .'></i>';
        }

        // ۳) نام آیکون Lucide — از نقشهٔ خود پروژه به SVG تبدیل می‌شود
        $name = self::iconClass($value);
        $style = self::iconStyle($value);

        if (($inner = self::lucide()[$name] ?? null) !== null) {
            // فاز ۱۱۷ — SVG به font-size واکنش نشان نمی‌دهد؛ «اندازه» انتخابگر
            // (font-size:Npx) به width/height تبدیل می‌شود تا در پنل و فرانت درست باشد
            if ($style !== '' && str_contains($style, 'font-size')) {
                $style = (string) preg_replace('/font-size:\s*(\d+(?:\.\d+)?)px/i', 'width:$1px;height:$1px', $style);
            }

            return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"'
                .' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
                .($class !== '' ? ' class="'.htmlspecialchars($class, ENT_QUOTES, 'UTF-8').'"' : '')
                .($style !== '' ? ' style="'.htmlspecialchars($style, ENT_QUOTES, 'UTF-8').'"' : '')
                .'>'.$inner.'</svg>';
        }

        // نام ناشناخته — هیچ‌چیز (رندر متن خام در ویوها ممنوع)
        return '';
    }
}
