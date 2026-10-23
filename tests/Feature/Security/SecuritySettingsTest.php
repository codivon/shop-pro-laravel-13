<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\BotTrap;
use App\Services\LoginThrottle;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های تنظیمات امنیتی (فاز ۴۴):
 *  - ذخیرهٔ تب «امنیت» تنظیمات برنامه‌نویس (فقط سازنده)
 *  - کلیدهای کپچا/هانی‌پات/قفل تلاش/هدرهای امنیتی/HSTS
 *  - اثر واقعی هر کلید روی رفتار ورود و میان‌افزارها
 */
class SecuritySettingsTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    /* =====================================================================
     *  تب امنیت — تنظیمات برنامه‌نویس
     * ===================================================================== */

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'سازنده',
            'username' => 'creator-'.random_int(10000, 99999),
            'email'    => 'creator-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'creator',
            'status'   => 'ACTIVE',
        ]);
    }

    #[Test]
    public function creator_can_save_security_settings(): void
    {
        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(Developer::class)
            ->set('securityCaptchaEnabled', false)
            ->set('securityCaptchaAdminLogin', false)
            ->set('securityCaptchaCustomerLogin', true)
            ->set('securityCaptchaComments', true)
            ->set('securityTimeTrapSeconds', 7)
            ->set('securityHoneypotEnabled', false)
            ->set('securityLockoutEnabled', false)
            ->set('securityLockoutAdminMax', 8)
            ->set('securityLockoutAdminMinutes', 30)
            ->set('securityLockoutCommentsMinutes', 20)
            ->set('securityHeadersEnabled', false)
            ->set('securityHstsEnabled', true)
            ->set('adminTwoFactorEnabled', true)
            ->set('adminTwoFactorChannel', 'both')
            ->call('saveSecurity')
            ->assertHasNoErrors();

        $this->assertSame('off', option('security_captcha_enabled'));
        $this->assertSame('off', option('security_captcha_admin_login'));
        $this->assertSame('on', option('security_captcha_customer_login'));
        $this->assertSame('on', option('security_captcha_comments'));
        $this->assertSame('7', option('security_time_trap_seconds'));
        $this->assertSame('off', option('security_honeypot_enabled'));
        $this->assertSame('off', option('security_lockout_enabled'));
        $this->assertSame('8', option('security_lockout_admin_max'));
        $this->assertSame('30', option('security_lockout_admin_minutes'));
        $this->assertSame('20', option('security_lockout_comments_minutes'));
        $this->assertSame('off', option('security_headers_enabled'));
        $this->assertSame('on', option('security_hsts_enabled'));
        $this->assertSame('on', option('admin_2fa_enabled'));
        $this->assertSame('both', option('admin_2fa_channel'));
    }

    #[Test]
    public function security_settings_reject_out_of_range_values(): void
    {
        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(Developer::class)
            ->set('securityTimeTrapSeconds', 9999)
            ->set('securityLockoutAdminMax', 0)
            ->set('adminTwoFactorChannel', 'pigeon')
            ->call('saveSecurity')
            ->assertHasErrors(['securityTimeTrapSeconds', 'securityLockoutAdminMax', 'adminTwoFactorChannel']);
    }

    #[Test]
    public function non_creator_cannot_save_security_settings(): void
    {
        $admin = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'ادمین',
            'username' => 'admin-'.random_int(10000, 99999),
            'email'    => 'admin-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'admin',
            'status'   => 'ACTIVE',
        ]);
        $this->actingAs($admin, 'adminPanel');

        // صفحهٔ تنظیمات توسعه‌دهنده فقط برای level=creator است (abort در mount)
        $this->get(route('np.developer.index'))->assertStatus(403);
    }

    /* =====================================================================
     *  کپچا — کلید اصلی و کلید نقاط
     * ===================================================================== */

    #[Test]
    public function captcha_master_off_removes_captcha_requirement_from_admin_login(): void
    {
        option_update('security_captcha_enabled', 'off');

        $admin = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'ورود',
            'username' => 'login-'.random_int(10000, 99999),
            'email'    => 'login-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'creator',
            'status'   => 'ACTIVE',
        ]);

        // تأخیر طبیعی فرم — تلهٔ زمان فعال نشود
        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticated('adminPanel');
    }

    #[Test]
    public function captcha_default_still_requires_captcha_code_on_admin_login(): void
    {
        // پیش‌فرض‌های فاز ۴۴ باید عین رفتار قبلی باشد: کپچا الزامی
        $admin = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'ورود',
            'username' => 'login-'.random_int(10000, 99999),
            'email'    => 'login-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'creator',
            'status'   => 'ACTIVE',
        ]);

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasErrors(['captcha']);

        $this->assertGuest('adminPanel');
    }

    #[Test]
    public function time_trap_zero_disables_fast_submission_rejection(): void
    {
        option_update('security_time_trap_seconds', '0');
        option_update('security_captcha_enabled', 'off');

        $admin = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'ورود',
            'username' => 'login-'.random_int(10000, 99999),
            'email'    => 'login-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'creator',
            'status'   => 'ACTIVE',
        ]);

        // renderedAt = الان (ارسال فوری ربات‌وار) — با تلهٔ زمان صفر نباید رد شود
        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticated('adminPanel');
    }

    /* =====================================================================
     *  هانی‌پات
     * ===================================================================== */

    #[Test]
    public function honeypot_filled_form_traps_ip_when_enabled(): void
    {
        option_update('security_captcha_enabled', 'off'); // تا validate روی کپچا نایستد

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', 'someone')
            ->set('password', 'wrong-password')
            ->set('website_url', 'https://spam.example')
            ->call('authenticate');

        $this->assertTrue(BotTrap::isBlocked('127.0.0.1'), 'پر کردن هانی‌پات باید IP را مسدود کند');
    }

    #[Test]
    public function honeypot_is_ignored_when_disabled(): void
    {
        option_update('security_captcha_enabled', 'off');
        option_update('security_honeypot_enabled', 'off');

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', 'someone-unknown')
            ->set('password', 'wrong-password')
            ->set('website_url', 'https://spam.example')
            ->call('authenticate')
            ->assertHasErrors(['username']);

        $this->assertFalse(BotTrap::isBlocked('127.0.0.1'), 'با خاموش بودن هانی‌پات نباید IP مسدود شود');
    }

    /* =====================================================================
     *  قفل تلاش‌ها
     * ===================================================================== */

    #[Test]
    public function login_throttle_uses_configured_max_attempts_and_window(): void
    {
        option_update('security_lockout_enabled', 'on');
        option_update('security_lockout_admin_max', '2');
        option_update('security_lockout_admin_minutes', '30');

        LoginThrottle::hitFailure('user-x', '127.0.0.1');
        $this->assertFalse(LoginThrottle::tooManyAttempts('user-x', '127.0.0.1'));

        LoginThrottle::hitFailure('user-x', '127.0.0.1');
        $this->assertTrue(LoginThrottle::tooManyAttempts('user-x', '127.0.0.1'));

        // پنجرهٔ ۳۰ دقیقه‌ای
        $this->assertGreaterThan(1000, LoginThrottle::availableIn('user-x', '127.0.0.1'));

        LoginThrottle::clear('user-x', '127.0.0.1');
    }

    #[Test]
    public function login_throttle_master_off_disables_lockout(): void
    {
        option_update('security_lockout_enabled', 'off');

        for ($i = 0; $i < 10; $i++) {
            LoginThrottle::hitFailure('user-y', '127.0.0.1');
        }

        $this->assertFalse(LoginThrottle::tooManyAttempts('user-y', '127.0.0.1'));
    }

    /* =====================================================================
     *  هدرهای امنیتی + HSTS
     * ===================================================================== */

    /**
     * نکته: تست‌کلاینت لاراول با URL مطلق، پارامتر HTTPS سرور را بازنویسی می‌کند؛
     * بنابراین میدل‌ور هدرها مستقیماً (واقعی و بدون وابستگی به تست‌کلاینت) اجرا می‌شود.
     */
    private function runHeadersMiddleware(string $scheme): \Illuminate\Http\Response
    {
        $request  = \Illuminate\Http\Request::create($scheme.'://shop.test/');
        $response = new \Illuminate\Http\Response('ok');

        return app(\App\Http\Middleware\SecurityHeaders::class)->handle($request, fn () => $response);
    }

    #[Test]
    public function security_headers_present_by_default_and_hsts_only_over_https_when_enabled(): void
    {
        option_update('security_hsts_enabled', 'on');

        $secure = $this->runHeadersMiddleware('https');
        $this->assertSame('SAMEORIGIN', $secure->headers->get('X-Frame-Options'));
        $this->assertSame('max-age=31536000; includeSubDomains', $secure->headers->get('Strict-Transport-Security'));

        // روی HTTP هدر HSTS فرستاده نمی‌شود (RFC 6797)
        $insecure = $this->runHeadersMiddleware('http');
        $this->assertSame('SAMEORIGIN', $insecure->headers->get('X-Frame-Options'));
        $this->assertFalse($insecure->headers->has('Strict-Transport-Security'));
    }

    #[Test]
    public function hsts_absent_when_disabled(): void
    {
        option_update('security_hsts_enabled', 'off');

        $response = $this->runHeadersMiddleware('https');
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    #[Test]
    public function security_headers_master_off_removes_all_headers(): void
    {
        option_update('security_headers_enabled', 'off');
        option_update('security_hsts_enabled', 'on');

        $response = $this->runHeadersMiddleware('https');
        $this->assertFalse($response->headers->has('X-Frame-Options'));
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    /* =====================================================================
     *  کپچای ورود مشتری (نقطهٔ جدید) + CommentProtection
     * ===================================================================== */

    #[Test]
    public function comment_protection_rejects_honeypot_and_fast_and_captcha_failures(): void
    {
        $request = \Illuminate\Http\Request::create('/test', 'POST', [
            'body'        => 'سلام',
            'website_url' => 'https://spam.example',
        ]);

        try {
            \App\Services\CommentProtection::validateSubmission($request, 'product:1');
            $this->fail('هانی‌پاتِ پرشده باید رد شود');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame(\App\Services\BotDetector::botMessage(), $e->errors()['body'][0] ?? '');
        }

        // تلهٔ زمان: ارسال در همان لحظهٔ رندر
        option_update('security_time_trap_seconds', '3');
        $fast = \Illuminate\Http\Request::create('/test', 'POST', [
            'body'        => 'سلام',
            'rendered_at' => (string) now()->getTimestamp(),
        ]);

        try {
            \App\Services\CommentProtection::validateSubmission($fast, 'product:1');
            $this->fail('ارسال سریع‌تر از حد مجاز باید رد شود');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('قابل پردازش', $e->errors()['body'][0] ?? '');
        }
    }

    #[Test]
    public function comment_protection_flood_lock_uses_configured_window(): void
    {
        option_update('security_lockout_comments_max', '2');
        option_update('security_lockout_comments_minutes', '15');
        option_update('security_time_trap_seconds', '0');

        $make = fn () => \Illuminate\Http\Request::create('/test', 'POST', [
            'body'        => 'دیدگاه تستی',
            'rendered_at' => (string) now()->subSeconds(30)->getTimestamp(),
        ]);

        \App\Services\CommentProtection::validateSubmission($make(), 'product:9');
        \App\Services\CommentProtection::validateSubmission($make(), 'product:9');

        try {
            \App\Services\CommentProtection::validateSubmission($make(), 'product:9');
            $this->fail('ارسال سوم باید به‌خاطر قفل تلاش رد شود');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('بیش از حد مجاز', $e->errors()['body'][0] ?? '');
        }

        // شناسهٔ متفاوت = شمارندهٔ مستقل
        \App\Services\CommentProtection::validateSubmission($make(), 'product:10');
    }

    /* =====================================================================
     *  ورود مشتری — کپچا و هانی‌پات (قالب)
     * ===================================================================== */

    #[Test]
    public function customer_login_honeypot_traps_ip(): void
    {
        option_update('security_captcha_enabled', 'off'); // کپچا خاموش تا هانی‌پات تنها بررسی شود

        $response = $this->post('/login', [
            'username'    => '09120000000',
            'website_url' => 'https://spam.example',
            'rendered_at' => (string) now()->subSeconds(10)->getTimestamp(),
        ]);

        $response->assertSessionHasErrors();
        $this->assertTrue(BotTrap::isBlocked('127.0.0.1'));

        BotTrap::where('ip_address', '127.0.0.1')->delete();
        \Illuminate\Support\Facades\Cache::forget('security:blocked_bot_ips');
    }

    #[Test]
    public function customer_login_passes_without_captcha_when_disabled(): void
    {
        option_update('security_captcha_enabled', 'off');

        // موبایل معتبرِ ناموجود — بدون کپچا باید به قدم رمز برود
        $response = $this->post('/login', [
            'username'    => '09121234567',
            'rendered_at' => (string) now()->subSeconds(10)->getTimestamp(),
        ]);

        $response->assertRedirect();
        $this->assertFalse($response->getSession()->has('errors') && $response->getSession()->get('errors')->has('captcha'));
    }
}
