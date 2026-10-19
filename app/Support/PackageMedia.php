<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * ابزار نمایش رسانه/متن پکیج‌ها (فاز ۱۴)
 *
 * ریشه‌ی «تصاویر پکیج لود نمی‌شد»: پنل قدیمی برای تصویر، ابتدا thumbnail_url را
 * می‌گرفت و اگر نبود thumbnail را؛ پاسخ API معمولاً مسیر نسبی (بدون دامنه) می‌دهد،
 * پس مسیر نسبی باید با آدرس سرور پکیج‌ها مطلق شود — پنل جدید فقط thumbnail را
 * مستقیم می‌گذاشت و مرورگر آن را روی دامنه خود سایت می‌جست → 404.
 *
 * ریشه‌ی «متن‌ها به صورت کد HTML نمایش داده می‌شد»: توضیحات API حاوی HTML است و
 * پنل قدیمی با {!! !!} رندر می‌کرد؛ در کارت‌های لیست، متنِ پاک‌شده (strip_tags)
 * نمایش داده می‌شود تا تگ‌ها به‌صورت کد دیده نشوند.
 */
class PackageMedia
{
    /**
     * آدرس قابل نمایش تصویر پکیج — پورت دقیق منطق قدیمی:
     * thumbnail_url → thumbnail → (مسیر نسبی؟ پیشوند دامنه API) → null
     */
    public static function thumbnailUrl(?array $pkg): ?string
    {
        if (empty($pkg)) {
            return null;
        }

        $raw = $pkg['thumbnail_url'] ?? $pkg['thumbnail'] ?? null;

        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        // مطلق (http/https/protocol-relative) — عیناً استفاده می‌شود
        if (Str::startsWith($raw, ['http://', 'https://', '//'])) {
            return $raw;
        }

        // مسیر نسبی → مطلق با دامنه سرور پکیج‌ها
        $base = rtrim((string) config('packages.api.base_url'), '/');

        return $base !== '' ? $base . '/' . ltrim($raw, '/') : null;
    }

    /**
     * متن پاک‌شده از HTML برای کارت‌های لیست (بدون تگِ دیدنی)
     */
    public static function descriptionText(?string $html, int $limit = 90): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $html)));

        return $text !== '' ? Str::limit($text, $limit) : 'بدون توضیحات';
    }
}
