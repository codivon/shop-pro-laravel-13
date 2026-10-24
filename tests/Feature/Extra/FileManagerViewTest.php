<?php

namespace Tests\Feature\Extra;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * فاز ۷۰ — سلامت دارایی‌های فایل‌منیجر (admin/file-manager)
 *
 * گزارش کاربر:
 *  ۱) پیش‌نمایش تصاویر نشان داده نمی‌شود → endpointهای thumbnails/preview
 *     با vendor قدیمی Intervention (نسخه‌ای که سازنده‌اش آرایه می‌خواهد) 500 می‌دهند
 *     ← ریشهٔ مشترک با خطای «افزودن محصول»؛ روی سرور `composer install` لازم است.
 *  ۲) مودال آپلود و گزینه‌ها به‌هم‌ریخته است → فایل‌منیجر alexusmai با Bootstrap 4
 *     نوشته شده ولی bootstrap.min.css لوکال به v5.3.2 ارتقا یافته بود (کلاس‌های
 *     custom-file / مودال v4 در v5 حذف شده‌اند) ← به v4.6.2 برگشت.
 *  ۳) CDNها (use.fontawesome / stackpath) در ایران فیلتراند → همه به لوکال منتقل شد.
 */
class FileManagerViewTest extends TestCase
{
    #[Test]
    public function file_manager_iframe_uses_only_local_assets(): void
    {
        $html = view('back.file-manager-iframe')->render();

        $this->assertStringNotContainsString('use.fontawesome.com', $html, 'CDN فونت‌آسم should not be used');
        $this->assertStringContainsString('vendor/file-manager/css/bootstrap.min.css', $html);
        $this->assertStringContainsString('vendor/file-manager/css/bootstrap-rtl.css', $html);
        $this->assertStringContainsString('vendor/file-manager/css/fontawesome.min.css', $html);
        $this->assertStringContainsString('vendor/file-manager/css/file-manager.css', $html);
        $this->assertStringContainsString('vendor/file-manager/js/file-manager.js', $html);
    }

    #[Test]
    public function ckeditor_file_manager_view_uses_only_local_assets(): void
    {
        $html = view('vendor.file-manager.ckeditor')->render();

        $this->assertStringNotContainsString('use.fontawesome.com', $html);
        $this->assertStringNotContainsString('stackpath.bootstrapcdn.com', $html);
        $this->assertStringContainsString('vendor/file-manager/css/bootstrap.min.css', $html);
        $this->assertStringContainsString('vendor/file-manager/css/fontawesome.min.css', $html);
    }

    #[Test]
    public function bundled_bootstrap_is_v4_compatible_with_filemanager_ui(): void
    {
        $css = file_get_contents(public_path('vendor/file-manager/css/bootstrap.min.css'));

        $this->assertStringContainsString('Bootstrap v4.6.2', $css, 'فایل‌منیجر alexusmai با Bootstrap 4 نوشته شده است');
        $this->assertStringNotContainsString('--bs-', $css, 'متغیرهای Bootstrap 5 نباید باشند');
        // کلاس‌های کلیدی UI فایل‌منیجر که در v5 حذف شده‌اند
        $this->assertStringContainsString('.custom-file', $css);
        $this->assertStringContainsString('.input-group-prepend', $css);
        // RTL اورلِی هم مخصوص v4 است (input-group-append دارد)
        $rtl = file_get_contents(public_path('vendor/file-manager/css/bootstrap-rtl.css'));
        $this->assertStringContainsString('.input-group-append', $rtl);
        $this->assertStringContainsString('direction: rtl', $rtl);
    }

    #[Test]
    public function local_fontawesome_fonts_exist_next_to_css(): void
    {
        // fontawesome.min.css با مسیر نسبی ../webfonts/ فونت‌ها را می‌خواهد
        $this->assertFileExists(public_path('vendor/file-manager/css/fontawesome.min.css'));
        foreach (['fa-solid-900.woff2', 'fa-brands-400.woff2', 'fa-regular-400.woff2'] as $font) {
            $this->assertFileExists(
                public_path('vendor/file-manager/webfonts/'.$font),
                "فونت {$font} باید کنار CSS لوکال باشد"
            );
        }
    }

    #[Test]
    public function compiled_admin_css_contains_template_modal_constraints(): void
    {
        // باگ واقعی کاربر: مودال جزئیات قالب از بالا و پایین صفحه زده بیرون
        // ریشه: blade با max-h-[90vh] ساخته شده بود ولی CSS کامپایل admin قدیمی بود
        $manifest = json_decode((string) file_get_contents(public_path('admin-panel/manifest.json')), true);
        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('resources/admin-panel/css/admin.css', $manifest);

        $cssPath = public_path('admin-panel/'.$manifest['resources/admin-panel/css/admin.css']['file']);
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);
        foreach (['max-h-\\[90vh\\]', 'z-\\[90\\]', 'max-w-4xl', 'overflow-y-auto', 'cursor-pointer'] as $utility) {
            $this->assertTrue(
                str_contains($css, $utility),
                "CSS کامپایل admin باید کلاس {$utility} را داشته باشد (build:admin اجرا شود)"
            );
        }
    }
}
