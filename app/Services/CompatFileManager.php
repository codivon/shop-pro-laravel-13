<?php

namespace App\Services;

use Alexusmai\LaravelFileManager\FileManager;
use Illuminate\Support\Facades\Storage;

/**
 * فاز ۷۳ — فایل‌منیجر سازگار با هر نسخهٔ Intervention Image
 *
 * مشکل واقعی کاربر: در صفحهٔ مدیریت فایل‌ها تصاویر نمایش داده نمی‌شدند و
 * «پیش‌نمایش» هم تصویر را نشان نمی‌داد. ریشه: endpointهای thumbnails/preview
 * پکیج alexusmai با `Intervention\Image\Laravel\Facades\Image::read()` (API
 * نسخهٔ نهایی v3) نوشته شده‌اند و روی سرورهایی که vendor قدیمی دارند
 * (v3 آلفا فاقد read یا v2 فاقد image-laravel) پاسخ 500 می‌دهند.
 *
 * راه‌حل (این کلاس به‌جای کلاس پکیج بایند می‌شود):
 *  - preview: فایل خام با mime درست — بدون پردازش تصویر؛ همیشه کار می‌کند و سریع‌تر است
 *  - thumbnails: فرمت‌های رستری با لایهٔ سازگار shop_image_* کوچک می‌شوند؛
 *    SVG/بقیهٔ فرمت‌ها و هر خطا → خود فایل خام (fail-safe — تصویر همیشه نمایش داده می‌شود)
 */
class CompatFileManager extends FileManager
{
    /** فرمت‌های رستری که Intervention می‌تواند باز کند (SVG عمداً مستثناست) */
    private const RASTER_PATTERN = '/\.(jpe?g|png|gif|webp|avif|bmp)$/i';

    public function thumbnails($disk, $path): mixed
    {
        $binary = Storage::disk($disk)->get($path);
        $mime   = Storage::disk($disk)->mimeType($path);

        if (preg_match(self::RASTER_PATTERN, $path) && function_exists('shop_image_manager')) {
            try {
                $image = shop_image_open(shop_image_manager(), $binary);

                if (method_exists($image, 'coverDown')) {
                    $image->coverDown(80, 80); // v3
                } elseif (method_exists($image, 'fit')) {
                    $image->fit(80, 80); // v2
                }

                [$encoded] = shop_image_encode(
                    $image,
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                    80
                );

                return response()->make($encoded, 200, ['Content-Type' => $mime]);
            } catch (\Throwable) {
                // سقوط به برگرداندن خام فایل — نمایش تصویر مهم‌تر از کوچک‌سازی است
            }
        }

        return response()->make($binary, 200, ['Content-Type' => $mime]);
    }

    public function preview($disk, $path): mixed
    {
        return response()->make(
            Storage::disk($disk)->get($path),
            200,
            ['Content-Type' => Storage::disk($disk)->mimeType($path)]
        );
    }
}
