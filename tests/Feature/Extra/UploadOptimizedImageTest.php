<?php

namespace Tests\Feature\Extra;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۷۰ — آپلود بهینهٔ تصویر (uploadOptimizedImage)
 *
 * باگ واقعی کاربر (صفحهٔ افزودن محصول — درون‌ریزی از دیجی‌کالا):
 *   Intervention\Image\ImageManager::__construct(): Argument #1 ($config)
 *   must be of type array, string given — bootstrap/helpers.php line 1559
 *
 * ریشه: `new ImageManager(Driver::class)` نام کلاس را به‌صورت «رشته» می‌داد؛
 * سازندهٔ نسخهٔ نهایی v3 درایورِ آبجکت می‌خواهد (و روی vendorهای قدیمی‌تر آرایه).
 */
class UploadOptimizedImageTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Storage::fake('public');
    }

    #[Test]
    public function uploadOptimizedImage_initializes_manager_and_saves_optimized_file(): void
    {
        $image = UploadedFile::fake()->image('product.png', 120, 90);

        $path = uploadOptimizedImage($image, 'products', null, [
            'optimize'  => false,  // وابسته به ابزار سیستمی نیست
            'watermark' => false,
            'format'    => 'png', // PHP تست سندباکس webp/jpeg ندارد؛ مسیر کد یکسان است
        ]);

        $this->assertIsString($path);
        $this->assertNotSame('', $path);
        $this->assertStringStartsWith('uploads/products/', $path);
        $this->assertStringEndsWith('.png', $path, 'خروجی باید با فرمت درخواستی ذخیره شود');
        $this->assertTrue(Storage::disk('public')->exists($path), 'فایل باید روی دیسک ذخیره شده باشد');

        // محتوا باید تصویر معتبر PNG باشد
        $content = Storage::disk('public')->get($path);
        $this->assertTrue(
            str_starts_with($content, "\x89PNG"),
            'محتوای ذخیره‌شده باید یک فایل PNG معتبر باشد'
        );
    }

    #[Test]
    public function uploadOptimizedImage_from_path_works_like_direct_upload(): void
    {
        // مسیر درون‌ریزی دیجی‌کالا: تصویر از URL دانلود و از مسیر فایل آپلود می‌شود
        $tmp = tempnam(sys_get_temp_dir(), 'digi');
        file_put_contents($tmp, UploadedFile::fake()->image('digi.png', 80, 80)->getContent());

        $path = uploadOptimizedImageFromPath($tmp, 'products', null, [
            'optimize'  => false,
            'watermark' => false,
            'format'    => 'png', // PHP تست سندباکس webp/jpeg ندارد
        ]);

        $this->assertIsString($path);
        $this->assertStringStartsWith('uploads/products/', $path);
        $this->assertStringEndsWith('.png', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));

        @unlink($tmp);
    }
}
