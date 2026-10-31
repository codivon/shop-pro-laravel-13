<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * تست هلپرهای ماژول‌ها — قرارداد دیسک-آگاه فاز ۱۲۳:
 *   module_asset از بین نام‌های کاندید (kebab / lowercase خام / زیرخط‌دار)
 *   گزینه‌ای که «واقعاً روی دیسک موجود باشد» را انتخاب می‌کند و اگر هیچ‌کدام
 *   نبود، به kebab برمی‌گردد — تا CSS/JS صفحه‌های ادمین هرگز ۴۰۴ نشود.
 *
 * تست‌ها هرمتیک‌اند (مستقل از محیط): فایل‌های موقت زیر public/modules ساخته و
 * در tearDown پاک می‌شوند، پس نتیجه روی سندباکس توسعه و CI یکسان است.
 * نام ماژول «TestMod» (camel خالص): kebab = test-mod و lowercase = testmod
 * (استفاده از زیرخط در نام، خروجی kebab را به‌هم می‌ریزد — test-mod_-x).
 */
class ModuleHelpersTest extends TestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        // پاک‌سازی فایل‌های موقت ساخته‌شده در public/modules
        foreach ($this->created as $file) {
            @unlink($file);
        }

        // پاک‌سازی پوشه‌های تودرتو (فقط اگر خالی‌اند — به محتوای واقعی دست نمی‌زنیم)
        foreach (['test-mod', 'testmod'] as $root) {
            foreach (['css', 'images'] as $sub) {
                @rmdir(public_path("modules/{$root}/{$sub}"));
            }
            @rmdir(public_path("modules/{$root}"));
        }
        @rmdir(public_path('modules'));

        parent::tearDown();
    }

    private function touch(string $moduleDir, string $sub, string $file): void
    {
        $dir = public_path("modules/{$moduleDir}/{$sub}");
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $full = $dir.'/'.$file;
        file_put_contents($full, 'test');
        $this->created[] = $full;
    }

    public function test_disk_existing_candidate_wins_over_kebab_fallback(): void
    {
        // فقط پوشهٔ lowercase خام روی دیسک هست ⇒ همان باید انتخاب شود
        $this->touch('testmod', 'css', 'seo.css');

        $this->assertStringEndsWith(
            '/modules/testmod/css/seo.css',
            module_asset('TestMod', 'css/seo.css')
        );
    }

    public function test_kebab_candidate_wins_when_present(): void
    {
        $this->touch('test-mod', 'css', 'seo.css');

        $this->assertStringEndsWith(
            '/modules/test-mod/css/seo.css',
            module_asset('TestMod', 'css/seo.css')
        );
    }

    public function test_fallback_is_kebab_when_nothing_exists_on_disk(): void
    {
        // هیچ فایلی ساخته نشده ⇒ فالبک = kebab (الگوی فاز ۱۲۳)
        $this->assertStringEndsWith(
            '/modules/test-mod/css/seo.css',
            module_asset('TestMod', 'css/seo.css')
        );
    }

    public function test_module_asset_is_insensitive_to_leading_slashes_and_case(): void
    {
        // هردو نام به همان کاندیدِ موجود روی دیسک می‌رسند ⇒ خروجی یکسان
        $this->touch('testmod', 'css', 'seo.css');

        $this->assertSame(
            module_asset('TestMod', 'css/seo.css'),
            module_asset('testmod', '/css/seo.css')
        );
        $this->assertStringEndsWith(
            '/modules/testmod/css/seo.css',
            module_asset('TestMod', 'css/seo.css')
        );
    }

    public function test_module_asset_stays_inside_modules_namespace(): void
    {
        // asset پکیج هرگز نباید به پوشه‌های پنل (back/ یا admin-panel/) اشاره کند
        $this->assertStringContainsString('/modules/', module_asset('DigiPay', 'js/gateway.js'));
        $this->assertStringNotContainsString('/back/', module_asset('DigiPay', 'js/gateway.js'));
    }
}
