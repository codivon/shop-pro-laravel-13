<?php

namespace Tests\Feature\Extra;

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
 * بازتولید و قفل‌شدن باگ واقعی سرور کاربر (فاز ۵۱):
 * کلونِ خام از گیت → .env خودکار از .env.example ساخته می‌شود (اعتبارنامه‌ی
 * دیتابیس خالی) → نشان storage/installed نیست → کش گزینه‌ها هم سرد است →
 * میدلور سراسری SecurityHeaders در خود صفحات /install از طریق
 * SecuritySettings::headersEnabled() → option() کوئری options می‌زد و با
 * «Access denied for user ''@'localhost'» درخواست نصب ۵۰۰ می‌شد.
 *
 * تکنیک تست: به‌جای خراب‌کردن اعتبارنامه‌ی mysql (که اگر اتصال در بوت از قبل
 * resolve شده باشد اثر نمی‌کند)، اتصال پیش‌فرض روی نامی می‌رود که اصلاً در
 * کانفیگ وجود ندارد؛ اگر گارد فاز ۵۱ برداشته شود، اولین دست‌زدن option() به
 * دیتابیس با InvalidArgumentException قرمز می‌شود — مستقل از سرور دیتابیس.
 *
 * مهم: این تست عمداً بدون RefreshDatabase است.
 */
class InstallerFreshCloneTest extends TestCase
{
    /** مسیر نشان نصب — مستقیم ساخته می‌شود چون storage_path() قبل از بوت اپ در دسترس نیست */
    protected string $installedMarker;

    protected function setUp(): void
    {
        $this->installedMarker = dirname(__DIR__, 3).'/storage/installed';

        // سناریوی «کلون خام» — حذف نشان باید قبل از بوت اپ انجام شود
        @unlink($this->installedMarker);

        parent::setUp();

        // کش گزینه‌ها هم باید سرد باشد (روی سرور تازه هیچ کشی وجود ندارد)
        Cache::flush();

        // هر دست‌زدن به دیتابیس باید منفجر شود (اتصالِ ناموجود)
        config(['database.default' => 'ghost_connection_not_configured']);
    }

    protected function tearDown(): void
    {
        // برگرداندن نشان برای بقیه‌ی تست‌های همین پروسه
        touch($this->installedMarker);

        parent::tearDown();
    }

    #[Test]
    public function install_page_renders_without_touching_db(): void
    {
        $this->get('/install')->assertOk();
    }

    #[Test]
    public function install_requirements_page_renders_without_touching_db(): void
    {
        $this->get('/install/requirements')->assertOk();
    }

    #[Test]
    public function option_helper_returns_default_without_db_before_install(): void
    {
        // بدون گارد فاز ۵۱، همین فراخوانی با «connection not configured» منفجر می‌شد
        $this->assertSame('on', option('security_headers_enabled', 'on'));
        $this->assertSame('fallback', option('some_random_option', 'fallback'));
    }

    #[Test]
    public function security_headers_middleware_skips_db_before_install(): void
    {
        // مسیر دقیق باگ کاربر: میدلور سراسری → SecuritySettings::headersEnabled()
        $response = $this->get('/install');

        $response->assertOk();
        // بدون گارد، پاسخ اصلاً ساخته نمی‌شد (قبل از رندر ۵۰۰ می‌شد)
    }
}
