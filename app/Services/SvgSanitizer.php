<?php

namespace App\Services;

/*
 * پاک‌سازی محتوای SVG (ضد Stored XSS)
 * SVG می‌تواند حاوی <script>، رویدادهای on*، URL های javascript: و
 * <foreignObject> باشد — هر کدام یک مسیر XSS روی دامنه‌ی اصلی سایت.
 * این پاک‌ساز، خطرناک‌ترین الگوها را حذف می‌کند.
 */
class SvgSanitizer
{
    public static function clean(string $svg): string
    {
        // حذف سشن و XML خارجی خطرناک
        $svg = preg_replace('/<\?xml-stylesheet.*?\?>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<!DOCTYPE[^>\[]*(\[[^\]]*\])?[^>]*>/is', '', $svg) ?? $svg;

        // حذف کامل تگ‌های خطرناک
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<script\b[^>]*\/?>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject\s*>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<foreignObject\b[^>]*\/?>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<(script|foreignObject|use|set|animate|animateTransform|handler)\b[^>]*>.*?<\/\1\s*>/is', '', $svg) ?? $svg;

        // حذف ویژگی‌های رویداد (on*)
        $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $svg) ?? $svg;

        // حذف URL های خطرناک در href/xlink:href/style
        $svg = preg_replace('/(href|xlink:href|src)\s*=\s*("\s*(javascript|vbscript|data:text\/html)[^"]*"|\'\s*(javascript|vbscript|data:text\/html)[^\']*\'|(javascript|vbscript|data:text\/html)[^\s>]*)/is', '', $svg) ?? $svg;

        // حذف تعریف entity (مقداردهی خارجی)
        $svg = preg_replace('/<!ENTITY[^>]*>/is', '', $svg) ?? $svg;

        // حذف style حاوی javascript
        $svg = preg_replace('/style\s*=\s*("[^"]*javascript:[^"]*"|\'[^\']*javascript:[^\']*\')/is', '', $svg) ?? $svg;

        return $svg;
    }
}
