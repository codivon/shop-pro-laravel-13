<?php

namespace Tests\Feature\Extra;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۷۵ — گارد یکپارچگی vendor Intervention Image + مسیر GD خالص
 *
 * خطای واقعی سرور لایو هنگام ذخیرهٔ محصول:
 *   Class Intervention\Image\EncodedImage contains 5 abstract methods and
 *   must therefore be declared abstract or implement the remaining methods
 *   (Intervention\Image\Interfaces\FileInterface::save, toFilePointer, size, ...)
 *
 * ریشه: فایل‌های vendor روی هاست از دو نسخهٔ مختلف v3 مخلوط شده‌اند — این
 * خطا E_ERROR غیرقابل شکار است و در لحظهٔ autoload رخ می‌دهد؛ هیچ try/catchی
 * نجاتش نمی‌دهد. راه‌حل: پروب متنیِ یکپارچگی «پیش از هر autoload» + کل خط
 * تصویر روی مسیر GD خالص (بدون هیچ کلاسی از Intervention) وقتی vendor معیوب است.
 */
class InterventionVendorGuardTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Storage::fake('public');
    }

    /** vendor اصلی سندباکس (3.11.8) — سالم */
    private function realSrc(): string
    {
        return base_path('vendor/intervention/image/src');
    }

    /** ساخت یک vendor مخلوط سنتزی — دقیقاً مثل هاست کاربر: اینترفیس جدید + کلاس‌های قدیمی */
    private function makeBrokenVendor(): string
    {
        $dir = sys_get_temp_dir().'/iv_broken_'.uniqid();
        @mkdir($dir.'/Interfaces', 0777, true);
        @mkdir($dir.'/Traits', 0777, true);

        // اینترفیسِ جدید — ۵ متد (همان‌هایی که در لاگ سرور بود)
        file_put_contents($dir.'/Interfaces/FileInterface.php', <<<'PHP'
        <?php
        namespace Intervention\Image\Interfaces;
        interface FileInterface {
            public function save(string $filepath): void;
            public function toFilePointer();
            public function size(): int;
            public function toString(): string;
            public function __toString(): string;
        }
        PHP);

        file_put_contents($dir.'/Interfaces/EncodedImageInterface.php', <<<'PHP'
        <?php
        namespace Intervention\Image\Interfaces;
        use Intervention\Image\Interfaces\FileInterface;
        interface EncodedImageInterface extends FileInterface {
            public function mediaType(): string;
            public function mimetype(): string;
            public function toDataUri(): string;
        }
        PHP);

        // کلاس والد قدیمی — بدون save/toFilePointer/size/toString
        file_put_contents($dir.'/File.php', <<<'PHP'
        <?php
        namespace Intervention\Image;
        use Intervention\Image\Interfaces\FileInterface;
        use Intervention\Image\Traits\CanBuildFilePointer;
        class File implements FileInterface {
            use CanBuildFilePointer;
            public function __construct(mixed $data = null) {}
            public function __toString(): string { return ''; }
        }
        PHP);

        file_put_contents($dir.'/EncodedImage.php', <<<'PHP'
        <?php
        namespace Intervention\Image;
        use Intervention\Image\Interfaces\EncodedImageInterface;
        class EncodedImage extends File implements EncodedImageInterface {
            public function __construct(mixed $data, protected string $mediaType = 'application/octet-stream') { parent::__construct($data); }
            public function mediaType(): string { return $this->mediaType; }
        }
        PHP);

        file_put_contents($dir.'/Traits/CanBuildFilePointer.php', <<<'PHP'
        <?php
        namespace Intervention\Image\Traits;
        trait CanBuildFilePointer {
            public function buildFilePointer(mixed $data = null) {}
        }
        PHP);

        return $dir;
    }

    #[Test]
    public function probe_accepts_healthy_v3_vendor(): void
    {
        $this->assertTrue(
            shop_intervention_vendor_ok($this->realSrc()),
            'vendor سالم 3.11.8 باید «سالم» تشخیص داده شود'
        );
    }

    #[Test]
    public function probe_rejects_mixed_v3_vendor_like_live_server(): void
    {
        $broken = $this->makeBrokenVendor();

        $this->assertFalse(
            shop_intervention_vendor_ok($broken),
            'vendor مخلوط (اینترفیس جدید + کلاس قدیمی) باید «معیوب» تشخیص داده شود'
        );

        // پاک‌سازی
        @unlink($broken.'/Interfaces/FileInterface.php');
        @unlink($broken.'/Interfaces/EncodedImageInterface.php');
        @unlink($broken.'/File.php');
        @unlink($broken.'/EncodedImage.php');
        @unlink($broken.'/Traits/CanBuildFilePointer.php');
        @rmdir($broken.'/Interfaces');
        @rmdir($broken.'/Traits');
        @rmdir($broken);
    }

    #[Test]
    public function probe_accepts_v2_style_vendor_without_encoded_image(): void
    {
        $v2 = sys_get_temp_dir().'/iv_v2_'.uniqid();
        @mkdir($v2, 0777, true);
        file_put_contents($v2.'/ImageManager.php', '<?php class ImageManager {}');

        $this->assertTrue(
            shop_intervention_vendor_ok($v2),
            'v2 فاقد EncodedImage است — لایهٔ سازگار فاز ۷۳ هندلش می‌کند، پس سالم تلقی می‌شود'
        );

        @unlink($v2.'/ImageManager.php');
        @rmdir($v2);
    }

    #[Test]
    public function probe_fails_closed_when_directory_is_missing(): void
    {
        $this->assertFalse(
            shop_intervention_vendor_ok(sys_get_temp_dir().'/iv_missing_'.uniqid()),
            'پوشهٔ ناموجود باید fail-safe و «معیوب» تلقی شود'
        );
    }

    #[Test]
    public function gd_fallback_encodes_scales_and_keeps_aspect_ratio(): void
    {
        // PNG واقعی ۴۰۰×۳۰۰
        $img = imagecreatetruecolor(400, 300);
        imagepng($img, $tmp = sys_get_temp_dir().'/gdfb_'.uniqid().'.png');
        imagedestroy($img);
        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        // فرمت webp درخواست می‌شود؛ روی بیلدهای بدون imagewebp باید به jpg/png سقوط کند
        [$out, $ext] = shop_gd_fallback_encode($binary, 'webp', 80, 100, 100);

        $this->assertNotNull($out, 'تصویر معتبر باید انکود شود');
        $this->assertContains($ext, ['webp', 'jpg', 'png'], 'پسوند واقعی خروجی باید یکی از زنجیرهٔ سقوط باشد');

        $info = @getimagesizefromstring((string) $out);

        $this->assertNotFalse($info, 'خروجی باید تصویری معتبر باشد');
        $this->assertSame([100, 75], [$info[0], $info[1]], 'نسبت ۴:۳ باید در جعبهٔ ۱۰۰ حفظ شود (۱۰۰×۷۵)');
    }

    #[Test]
    public function gd_fallback_returns_null_for_undecodable_binary(): void
    {
        [$out, $ext] = shop_gd_fallback_encode('RIFF-not-a-real-webp', 'png', 80);

        $this->assertNull($out, 'باینری بازنشدنی باید null بدهد تا تماس‌گیرنده خام ذخیره کند');
        $this->assertSame('png', $ext);
    }

    #[Test]
    public function upload_via_gd_processes_file_without_any_intervention_class(): void
    {
        $image = UploadedFile::fake()->image('product.png', 400, 300);

        $path = upload_optimized_image_via_gd($image, 'products', null, [
            'optimize' => false,
            'watermark' => false,
            'format' => 'png',
            'size' => [100, 100],
        ]);

        $this->assertIsString($path);
        $this->assertStringStartsWith('uploads/products/', $path);

        $content = (string) Storage::disk('public')->get($path);

        $info = @getimagesizefromstring($content);

        $this->assertNotFalse($info, 'فایل ذخیره‌شده باید تصویر معتبر باشد');
        $this->assertLessThanOrEqual(100, $info[0]);
        $this->assertLessThanOrEqual(100, $info[1]);
    }

    #[Test]
    public function upload_via_gd_falls_back_to_raw_bytes_for_undecodable_upload(): void
    {
        $garbage = 'RIFF(I'.chr(0x00).chr(0x00).chr(0x00).'WEBPVP8 broken-body';

        $tmp = tempnam(sys_get_temp_dir(), 'webp');
        file_put_contents($tmp, $garbage);

        $file = new UploadedFile($tmp, 'digikala-product.webp', 'image/webp', null, true);

        $path = upload_optimized_image_via_gd($file, 'products', null, [
            'optimize' => false,
            'watermark' => false,
            'format' => 'webp',
        ]);

        $this->assertStringStartsWith('uploads/products/', $path);
        $this->assertStringEndsWith('.webp', $path, 'پسوند اصلی برای فایل خام حفظ می‌شود');
        $this->assertSame($garbage, Storage::disk('public')->get($path), 'باینری خام باید بایت‌به‌بایت ذخیره شود');

        @unlink($tmp);
    }

    #[Test]
    public function doctor_command_reports_diagnosis(): void
    {
        $exit = Artisan::call('ops:intervention-doctor');

        $this->assertSame(0, $exit, 'روی vendor سالم باید کد خروج ۰ بدهد');
        $this->assertStringContainsString('Intervention', Artisan::output());

        // خروجی JSON — کلیدهای کلیدی باید باشند
        Artisan::call('ops:intervention-doctor', ['--json' => true]);

        $report = json_decode((string) trim(Artisan::output()), true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('healthy', $report);
        $this->assertArrayHasKey('copies', $report);
        $this->assertArrayHasKey('gd', $report);
        $this->assertArrayHasKey('opcache', $report);
        $this->assertTrue($report['healthy'], 'روی vendor سالم سندباکس باید healthy=true باشد');
    }
}
