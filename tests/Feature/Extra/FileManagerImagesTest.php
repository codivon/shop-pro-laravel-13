<?php

namespace Tests\Feature\Extra;

use Alexusmai\LaravelFileManager\FileManager;
use App\Services\CompatFileManager;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * فاز ۷۳ — نمایش تصاویر فایل‌منیجر
 *
 * مشکل واقعی کاربر: در صفحهٔ «مدیریت فایل‌ها» تصاویر نمایش داده نمی‌شدند و
 * با انتخاب تصویر و کلیک روی «پیش‌نمایش» هم تصویر دیده نمی‌شد.
 *
 * ریشه: endpointهای thumbnails/preview پکیج alexusmai با
 * `Intervention\Image\Laravel\Facades\Image::read()` (API نسخهٔ نهایی v3)
 * نوشته شده‌اند و روی سرورهایی که vendor قدیمی دارند 500 می‌دهند.
 *
 * راه‌حل: بایند App\Services\CompatFileManager به‌جای کلاس پکیج:
 *  - preview: فایل خام با mime درست (بدون پردازش — همیشه کار می‌کند)
 *  - thumbnails: رسترها با لایهٔ سازگار shop_image_* کوچک می‌شوند؛
 *    SVG/بقیه و هر خطا → خود فایل خام (fail-safe)
 */
class FileManagerImagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('downloads');
    }

    /** یک PNG واقعی ۱×۱ */
    private function pngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
    }

    #[Test]
    public function compat_file_manager_is_bound_in_container(): void
    {
        $service = app(FileManager::class);

        $this->assertInstanceOf(
            CompatFileManager::class,
            $service,
            'باید نسخهٔ سازگار به‌جای کلاس پکیج بایند شده باشد'
        );
    }

    #[Test]
    public function thumbnails_serves_resized_raster_image(): void
    {
        Storage::disk('downloads')->put('test/logo.png', $this->pngBytes());

        $response = app(FileManager::class)->thumbnails('downloads', 'test/logo.png');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent(), 'خروجی باید PNG معتبر باشد');
        $this->assertNotSame('', $response->getContent());
    }

    #[Test]
    public function thumbnails_falls_back_to_raw_for_svg_and_non_raster(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';
        Storage::disk('downloads')->put('test/vector.svg', $svg);

        $response = app(FileManager::class)->thumbnails('downloads', 'test/vector.svg');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<svg', $response->getContent(), 'SVG باید خام برگردد');
    }

    #[Test]
    public function preview_serves_exact_original_bytes(): void
    {
        Storage::disk('downloads')->put('test/photo.png', $this->pngBytes());

        $response = app(FileManager::class)->preview('downloads', 'test/photo.png');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->pngBytes(), $response->getContent(), 'پیش‌نمایش باید خود فایل باشد');
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function thumbnails_never_fails_even_for_broken_image(): void
    {
        // باینری خراب — نه تصویر واقعی — باید fail-safe با خود فایل جواب دهد
        Storage::disk('downloads')->put('test/broken.png', "\x00\x01\x02not-an-image");

        $response = app(FileManager::class)->thumbnails('downloads', 'test/broken.png');

        $this->assertSame(200, $response->getStatusCode(), 'حتی برای فایل خراب باید ۲۰۰ برگردد');
        $this->assertSame("\x00\x01\x02not-an-image", $response->getContent());
    }
}
