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

    /* ═══════ فاز ۷۷ — GD مسیرِ اصلی آپلود + واترمارک GD + پروب OPcache ═══════ */

    #[Test]
    public function upload_entry_point_prefers_pure_gd_even_when_vendor_is_healthy(): void
    {
        // نقطهٔ کلیدی فاز ۷۷: حتی با vendor سالم، uploadOptimizedImage باید از مسیر
        // GD خالص عبور کند (چون OPcache هاست با validate_timestamps=0 ممکن است کلاس
        // کهنه سرو کند — پروب دیسک را کورکورانه نمی‌توان باور کرد)
        $image = UploadedFile::fake()->image('product.png', 400, 300);

        $path = uploadOptimizedImage($image, 'products', null, [
            'optimize' => false,
            'watermark' => false,
            'format' => 'webp',
            'size' => [100, 100],
        ]);

        $this->assertIsString($path);
        $this->assertStringStartsWith('uploads/products/', $path);

        $content = (string) Storage::disk('public')->get($path);
        $info = @getimagesizefromstring($content);

        $this->assertNotFalse($info, 'خروجی مسیر اصلی باید تصویر معتبر باشد');
        $this->assertLessThanOrEqual(100, $info[0]);
        $this->assertLessThanOrEqual(100, $info[1]);
        $this->assertStringEndsWith('.webp', $path, 'روی بیلد GD دارای imagewebp باید webp واقعی ذخیره شود');
    }

    #[Test]
    public function upload_via_gd_applies_watermark_with_alpha(): void
    {
        // تصویر پایه: سفید ۱۰۰×۸۰
        $base = imagecreatetruecolor(100, 80);
        imagefill($base, 0, 0, imagecolorallocate($base, 255, 255, 255));
        $basePath = sys_get_temp_dir().'/wm_base_'.uniqid().'.png';
        imagepng($base, $basePath);
        imagedestroy($base);

        // واترمارک: قرمز مات ۲۰×۱۰
        $wm = imagecreatetruecolor(20, 10);
        imagefill($wm, 0, 0, imagecolorallocate($wm, 255, 0, 0));
        $wmName = 'watermark-test-'.uniqid().'.png';
        $wmPath = public_path($wmName);
        imagepng($wm, $wmPath);
        imagedestroy($wm);

        try {
            $file = new UploadedFile($basePath, 'base.png', 'image/png', null, true);

            $path = uploadOptimizedImage($file, 'products', null, [
                'optimize' => false,
                'format' => 'png',
                'watermark' => true,
                'watermarkPath' => $wmName,
                'watermarkImagePosition' => 'bottom-right',
            ]);

            $decoded = @imagecreatefromstring((string) Storage::disk('public')->get($path));

            $this->assertNotFalse($decoded, 'خروجی باید PNG معتبر باشد');

            // bottom-right با offset 25: واترمارک ۲۰×۱۰ روی ۱۰۰×۸۰ → ناحیهٔ (۵۵..۷۵ , ۴۵..۵۵)
            $index = imagecolorat($decoded, 60, 50);
            $rgb = imagecolorsforindex($decoded, $index);

            $this->assertGreaterThanOrEqual(200, $rgb['red'], 'پیکسل داخل ناحیهٔ واترمارک باید قرمز باشد');
            $this->assertLessThanOrEqual(80, $rgb['green']);
            $this->assertLessThanOrEqual(80, $rgb['blue']);

            imagedestroy($decoded);
        } finally {
            @unlink($wmPath);
            @unlink($basePath);
        }
    }

    #[Test]
    public function watermark_bigger_than_image_is_scaled_down_safely(): void
    {
        $base = imagecreatetruecolor(60, 40);
        imagefill($base, 0, 0, imagecolorallocate($base, 255, 255, 255));
        $binary = '';
        imagepng($base, $tmp = sys_get_temp_dir().'/wm_big_'.uniqid().'.png');
        $binary = (string) file_get_contents($tmp);
        imagedestroy($base);
        @unlink($tmp);

        // واترمارک ۲۰۰×۱۰۰ — بزرگ‌تر از تصویر؛ باید کوچک و درج شود، نه خطا
        $wm = imagecreatetruecolor(200, 100);
        imagefill($wm, 0, 0, imagecolorallocate($wm, 0, 200, 0));
        $wmPath = sys_get_temp_dir().'/wm_green_'.uniqid().'.png';
        imagepng($wm, $wmPath);
        imagedestroy($wm);

        try {
            [$out, $ext] = shop_gd_fallback_encode($binary, 'png', 80, null, null, $wmPath, 'center');

            $this->assertNotNull($out, 'درج واترمارک بزرگ نباید انکود را بشکند');

            $decoded = @imagecreatefromstring((string) $out);

            $this->assertNotFalse($decoded);

            // مرکز تصویر ۶۰×۴۰ باید سبز باشد (واترمارک مقیاس‌شده وسط درج شد)
            $rgb = imagecolorsforindex($decoded, imagecolorat($decoded, 30, 20));

            $this->assertLessThanOrEqual(80, $rgb['red'], 'مرکز تصویر باید سبزِ واترمارک باشد');
            $this->assertGreaterThanOrEqual(120, $rgb['green']);

            imagedestroy($decoded);
        } finally {
            @unlink($wmPath);
        }
    }

    #[Test]
    public function opcache_staleness_probe_reports_safely_without_opcache(): void
    {
        // «بدون OPcache» یعنی توابع opcache_* در باینری PHP هم نیستند (شاخهٔ null
        // اول helper). اگر توابع موجودند — حتی با enable_cli=0 مثل رانرهای
        // GitHub Actions — helper وارد شاخهٔ validate_timestamps می‌شود (false =
        // خودترمیمی) و شاخهٔ null در این محیط قابل بررسی نیست → skip صادقانه.
        if (function_exists('opcache_get_status')) {
            $this->markTestSkipped('توابع OPcache در این محیط موجودند — شاخهٔ null قابل بررسی نیست');
        }

        $this->assertNull(
            shop_intervention_opcache_stale($this->realSrc()),
            'بدون OPcache باید null بدهد (پروب دیسک کافی است — کلاس‌ها از دیسک می‌آیند)'
        );
    }

    #[Test]
    public function doctor_can_write_one_shot_opcache_reset_file(): void
    {
        $exit = Artisan::call('ops:intervention-doctor', ['--write-opcache-reset' => true]);
        $output = Artisan::output();

        try {
            $this->assertSame(0, $exit);
            $this->assertStringContainsString('opcache-reset-', $output, 'باید نام/آدرس فایل یک‌بارمصرف چاپ شود');
            $this->assertStringContainsString('rm public/', $output, 'یادآوری حذف فایل باید باشد');

            $matches = [];

            $this->assertSame(1, preg_match('/opcache-reset-[0-9a-f]{8}\.php/', $output, $matches), 'نام فایل باید الگوی امن داشته باشد');

            $written = public_path($matches[0]);

            $this->assertFileExists($written);
            $this->assertStringContainsString('opcache_reset', (string) file_get_contents($written));
        } finally {
            foreach (glob(public_path('opcache-reset-*.php')) ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
