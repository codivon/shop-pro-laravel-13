<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * دودآزمون (Smoke) پنل — بدون نوشتن در دیتابیس، فقط صفحات عمومی و ریدایرکت‌ها
 *
 * گاردهای محیطی (درس از اجرای کاربر روی ویندوز/لودرگون):
 *   ۱) کلون تازهٔ گیت «نصب» نیست (storage/installed ندارد) → روت‌های تم
 *      (front.*) ثبت نمی‌شوند و صفحهٔ اصلی ۴۰۴ می‌دهد → تست‌های HTTP بی‌معنا
 *      هستند و باید با پیام واضح skip شوند نه قرمز.
 *   ۲) دیتابیس محلی در دسترس نیست (MySQL خاموش / .env اشتباه) → اتصال PDO روی
 *      ویندوز تا تایم‌اوت TCP (~۲۰ ثانیه به‌ازای هر تست) معلق می‌ماند → اتصال
 *      با سقف ۳ ثانیه پروب می‌شود و در دسترس‌نبودن → skip با پیام واضح.
 *   روی CI/محیط درست‌تنظیم‌شده هیچ‌کدام از گاردها فعال نمی‌شوند و همهٔ تست‌ها
 *   واقعاً اجرا می‌شوند.
 */
class AdminPanelSmokeTest extends TestCase
{
    /**
     * پیش‌شرط تست‌های HTTP: اپ نصب‌شده + دیتابیس در دسترس.
     * (تست config به هیچ‌کدام نیاز ندارد و بدون گارد اجرا می‌شود.)
     */
    private function requireInstalledAppWithReachableDb(): void
    {
        if (! application_installed()) {
            $this->markTestSkipped(
                'اپ نصب نشده (فایل storage/installed وجود ندارد — کلون تازهٔ گیت). '
                .'تست‌های HTTP فقط روی اپ نصب‌شده معنا دارند؛ نصب کنید یا php artisan install را اجرا کنید.'
            );
        }

        // سقف اتصال ۳ ثانیه — به‌جای معلق‌شدن ۲۰+ ثانیه‌ای PDO روی ویندوز
        $default = (string) config('database.default');
        $connection = (array) config("database.connections.{$default}", []);

        if (($connection['driver'] ?? null) === 'mysql') {
            $connection['options'] = array_merge(
                (array) ($connection['options'] ?? []),
                [\PDO::ATTR_TIMEOUT => 3]
            );
            config(["database.connections.{$default}" => $connection]);
        }

        try {
            DB::select('select 1');
        } catch (\Throwable $e) {
            // کشف قطع دیتابیس را برای کل پروسه ثبت کن تا بقیهٔ تست‌ها فوری skip شوند
            \Tests\TestCase::noteDatabaseDown();

            $this->markTestSkipped(
                'دیتابیس محلی در دسترس نیست — MySQL/MariaDB را روشن کنید و .env را بررسی کنید: '
                .Str::limit($e->getMessage(), 140)
            );
        }
    }

    public function test_login_page_renders(): void
    {
        $this->requireInstalledAppWithReachableDb();

        // REMOTE_ADDR داخل وایت‌لیست ForceHttps (بدون ریدایرکت https)
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->get('/admin/admin/login');

        $response->assertStatus(200);
        $response->assertSee('ورود', false);
    }

    public function test_non_whitelisted_ip_over_http_is_forced_to_https(): void
    {
        $this->requireInstalledAppWithReachableDb();

        // IP خارج از وایت‌لیست ForceHttps روی http باید به https هدایت شود
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        $response = $this->get('/admin/admin/login');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
    }

    public function test_guest_is_redirected_from_panel_root(): void
    {
        $this->requireInstalledAppWithReachableDb();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->get('/admin/admin');

        $this->assertContains(
            $response->status(),
            [301, 302, 307],
            'مهمان باید به صفحه ورود ریدایرکت شود.'
        );
    }

    public function test_admin_prefix_helper_matches_config(): void
    {
        // پنل جدید همیشه /admin/{admin_route_prefix} است؛ این قرارداد
        // منوی پکیج‌ها و ریدایرکت لاگین به آن وابسته است.
        $this->assertNotEmpty(config('general.admin_route_prefix'));
    }

    public function test_app_responds_on_front_homepage(): void
    {
        $this->requireInstalledAppWithReachableDb();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        // صفحه اصلی فروشگاه (بدون احراز هویت) باید پاسخ 200 یا ریدایرکت بدهد
        $response = $this->get('/');

        $this->assertContains($response->status(), [200, 301, 302, 307]);
    }
}
