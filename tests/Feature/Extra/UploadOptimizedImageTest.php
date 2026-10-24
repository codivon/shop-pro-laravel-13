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

    #[Test]
    public function shop_image_manager_probe_returns_manager_that_really_decodes(): void
    {
        // فاز ۷۴ — ریشهٔ «Unknown driver type.» سرور لایو: کاندید ImageManager باید
        // با پروب واقعی (decode یک PNG در حافظه) اعتبارسنجی شود، نه فقط class_exists
        $manager = shop_image_manager();

        $this->assertIsObject($manager);

        $probePng = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true
        );

        // همان مسیری که روی v2 معیوب منفجر می‌شد (make/read روی باینری)
        $image = shop_image_open($manager, $probePng);

        $this->assertIsObject($image);
        $this->assertTrue(
            method_exists($image, 'count') || method_exists($image, 'getWidth') || property_exists($image, 'width'),
            'خروجی decode باید آبجکت تصویر باشد'
        );
    }

    #[Test]
    public function undecodable_binary_falls_back_to_raw_file_upload(): void
    {
        // فاز ۷۴ — باینری «RIFF…WEBP» لاگ کاربر: اگر درایور فعلی فرمت را باز نکند
        // فایل خام ذخیره می‌شود و آپلود محصول هرگز ۵۰۰ نمی‌دهد
        $garbage = 'RIFF(I'.chr(0x00).chr(0x00).chr(0x00).'WEBPVP8 not-a-really-decodable-webp-body';

        $tmp = tempnam(sys_get_temp_dir(), 'webp');
        file_put_contents($tmp, $garbage);

        $file = new UploadedFile($tmp, 'digikala-product.webp', 'image/webp', null, true);

        $path = uploadOptimizedImage($file, 'products', null, [
            'optimize'  => false,
            'watermark' => false,
            'format'    => 'webp',
        ]);

        $this->assertIsString($path);
        $this->assertStringStartsWith('uploads/products/', $path);
        $this->assertStringEndsWith('.webp', $path, 'پسوند فایل خام باید از نام اصلی حفظ شود');
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertSame($garbage, Storage::disk('public')->get($path), 'محتوای خام باید بایت‌به‌بایت ذخیره شود');

        @unlink($tmp);
    }
}
