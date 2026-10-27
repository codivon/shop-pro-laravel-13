<?php

namespace Tests\Feature\Extra;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۷۶ — پاک‌سازی آخرین نقاطِ مصرف مستقیم Intervention Image
 *
 * بعد از فاز ۷۵ (پروب متنی vendor + مسیر GD خالص) دو نقطه باقی بود که بیرون از
 * گارد، مستقیم کلاس‌های Intervention را autoload می‌کرد:
 *   - ProductController (اسکریپ «افزودن از سایت»): Image::make($url)->save(...)
 *   - GetArticleAiCommand: Image::make($path)->resize(...)->save()
 * علاوه بر آن ۵ فایل فقط «use Intervention\Image\Facades\Image;» (فاساد مخصوص
 * v2) داشتند که در vendor v3 اصلاً وجود ندارد — import مرده.
 *
 * این تست‌ها تضمین می‌کنند کد اپلیکیشن هرگز مستقیم به Intervention وابسته نشود
 * (همهٔ کار تصویر باید از لایهٔ سازگار shop_image_* / GD خالص helpers عبور کند) —
 * پس هیچ رگرسیونی نمی‌تواند کرش غیرقابل‌شکار «abstract methods» را دوباره زنده کند.
 *
 * فاز ۱۰۶ پیوست — استثنای پکیج خودکفا: ماژول‌هایی که intervention/image را
 * وابستگیِ «خودشان» در composer.json دارند (مثل SeoAudit با vendor داخلی v2)
 * لایهٔ سازگارِ خودشان را دارند (ImageOptimizerService با تشخیص v2/v3 در زمان
 * اجرا) و طبق قاعدهٔ «vendor/ خودکفا مجاز است» از این گارد هسته مستثنا هستند؛
 * کد ثالثِ زیر پوشهٔ vendor پکیج‌ها هم جزو «کد اپلیکیشن» نیست.
 */
class InterventionFreeCallSitesTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    #[Test]
    public function no_direct_intervention_import_or_call_survives_in_app_code(): void
    {
        $hits = [];

        foreach ($this->appPhpFiles() as [$file, $code]) {
            $code = $this->withoutComments($code);

            if (preg_match('/^\s*use\s+Intervention\\\\/m', $code)
                || preg_match('/\bImage::make\s*\(/', $code)
                || preg_match('/\bnew\s+\\\\?Intervention\\\\Image\\\\ImageManager\b/', $code)
                || preg_match('/\bnew\s+ImageManager\b/', $code)) {
                $hits[] = $file;
            }
        }

        $this->assertSame(
            [],
            $hits,
            'این فایل‌ها مستقیم به Intervention وابسته‌اند (باید از لایهٔ سازگار helpers عبور کنند): '.implode(', ', $hits)
        );
    }

    #[Test]
    public function reformed_call_sites_use_pure_gd_layer(): void
    {
        $product = base_path('app/Http/Controllers/Back/ProductController.php');
        $article = base_path('app/Console/Commands/GetArticleAiCommand.php');

        $this->assertFileExists($product);
        $this->assertFileExists($article);

        $this->assertStringContainsString('shop_gd_fallback_encode', (string) file_get_contents($product));
        $this->assertStringContainsString('shop_gd_fallback_encode', (string) file_get_contents($article));
        $this->assertStringNotContainsString('Intervention\Image\Facades\Image', (string) file_get_contents($product));
        $this->assertStringNotContainsString('Intervention\Image\Facades\Image', (string) file_get_contents($article));
    }

    /** همهٔ فایل‌های PHP کد اپلیکیشن (app + Modules در صورت وجود) به‌همراه محتوایشان */
    private function appPhpFiles(): array
    {
        $files = [];

        // پکیج‌های خودکفایی که intervention/image را وابستگیِ خودشان دارند
        // (لایهٔ سازگار خودشان را دارند — مستثنا از گارد هسته)
        $excludedModules = [];

        foreach (glob(base_path('Modules/*/composer.json')) ?: [] as $manifest) {
            $json = (array) json_decode((string) file_get_contents($manifest), true);

            if (isset($json['require']['intervention/image'])) {
                $excludedModules[] = dirname($manifest).DIRECTORY_SEPARATOR;
            }
        }

        foreach (['app', 'Modules'] as $root) {
            $dir = base_path($root);

            if (! is_dir($dir)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($it as $item) {
                if (! $item->isFile() || ! str_ends_with($item->getFilename(), '.php')) {
                    continue;
                }

                $path = $item->getPathname();

                // vendorهای خودکفای پکیج‌ها کدِ ثالث‌اند، نه کد اپلیکیشن
                if (str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                foreach ($excludedModules as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        continue 2;
                    }
                }

                $files[] = [$path, (string) file_get_contents($path)];
            }
        }

        return $files;
    }

    /** حذف کامنت‌ها تا متنی که فقط در توضیح آمده اشتباهی نقص حساب نشود (مثل پروب فاز ۷۵) */
    private function withoutComments(string $code): string
    {
        $code = (string) preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = (string) preg_replace('/\/\/[^\n]*/', '', $code);
        $code = (string) preg_replace('/(?<![:"\'])#[^\n]*/', '', $code);

        return $code;
    }
}
