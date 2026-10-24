<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Mail\SendMessage;
use App\Models\Admin;
use App\Services\AdminTwoFactorService;
use App\Services\SecuritySettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های ورود دومرحله‌ای مدیر (فاز ۴۴):
 *  - خاموش بودن 2FA = ورود مستقیم مثل قبل
 *  - روشن بودن 2FA = چالش، کد ۶ رقمی ایمیلی، ورود پس از تأیید
 *  - قفل ۵ تلاش اشتباه + انقضا + ارسال مجدد هر ۱ دقیقه
 *  - اتصال پنل قدیمی به همان چالش
 */
class AdminTwoFactorTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Mail::fake();
    }

    private function newAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'first_name' => 'تست',
            'last_name'  => 'مدیر',
            'username' => 'admin-'.random_int(10000, 99999),
            'email'    => 'admin-'.random_int(10000, 99999).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level'    => 'creator',
            'status'   => 'ACTIVE',
        ], $attributes));
    }

    /**
     * کد ۶ رقمی ارسال‌شده را از مِیلِ فیک استخراج می‌کند.
     */
    private function extractCode(): string
    {
        $mails = Mail::sent(SendMessage::class);
        $this->assertNotEmpty($mails, 'باید کد ۲FA ایمیلی ارسال شده باشد');

        preg_match('/\b(\d{6})\b/', (string) $mails[0]->content, $m);
        $this->assertArrayHasKey(1, $m, 'کد ۶ رقمی در ایمیل یافت نشد');

        return $m[1];
    }

    #[Test]
    public function two_factor_disabled_by_default_logs_in_directly(): void
    {
        option_update('security_captcha_enabled', 'off'); // فوکوس این تست 2FA است نه کپچا

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticated('adminPanel');
        $this->assertFalse(SecuritySettings::twoFactorEnabled());
    }

    #[Test]
    public function two_factor_email_flow_sends_code_and_logs_in_after_verification(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        // قدم ۱ — اعتبارنامه تأیید می‌شود اما ورود کامل نمی‌شود
        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertRedirect(route('np.two-factor.challenge'));

        $this->assertGuest('adminPanel');

        Mail::assertSent(SendMessage::class, 1);

        // قدم ۲ — صفحهٔ چالش باز می‌شود
        Livewire::test(TwoFactorChallenge::class)
            ->assertSet('challenge.pending', true);

        // قدم ۳ — کد غلط ورود نمی‌دهد
        Livewire::test(TwoFactorChallenge::class)
            ->set('code', '000000')
            ->call('verify')
            ->assertHasErrors(['code']);

        $this->assertGuest('adminPanel');

        // قدم ۴ — کد درست ورود کامل می‌کند
        $code = $this->extractCode();

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', $code)
            ->call('verify')
            ->assertHasNoErrors();

        $this->assertAuthenticated('adminPanel');
    }

    #[Test]
    public function five_wrong_attempts_flush_the_challenge(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate');

        $service = app(AdminTwoFactorService::class);
        $this->assertNotNull($service->pending());

        for ($i = 1; $i <= 5; $i++) {
            Livewire::test(TwoFactorChallenge::class)
                ->set('code', '00000'.$i)
                ->call('verify')
                ->assertHasErrors(['code']);
        }

        // بعد از پنجمین تلاش، چالش پاک شده و باید دوباره وارد شود
        $this->assertNull($service->pending());

        Livewire::test(TwoFactorChallenge::class)
            ->assertRedirect(route('np.login'));
    }

    #[Test]
    public function resend_is_throttled_to_once_per_minute(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate');

        Mail::assertSent(SendMessage::class, 1);

        // ارسال مجدد بلافاصله باید رد شود
        Livewire::test(TwoFactorChallenge::class)
            ->call('resend')
            ->assertHasErrors(['code']);

        Mail::assertSent(SendMessage::class, 1); // ایمیل جدیدی ارسال نشده

        // بعد از ۶۰ ثانیه امکان‌پذیر است
        $this->travel(61)->seconds();
        Livewire::test(TwoFactorChallenge::class)
            ->call('resend')
            ->assertHasNoErrors();

        Mail::assertSent(SendMessage::class, 2);
    }

    #[Test]
    public function sms_channel_without_admin_mobile_shows_actionable_error(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'sms');
        option_update('admin_mobile_number', '');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasErrors(['username']);

        $this->assertGuest('adminPanel');
        Mail::assertNothingSent();
    }

    #[Test]
    public function both_channel_sends_email_and_skips_missing_sms_gracefully(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'both');
        option_update('admin_mobile_number', '');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('np.two-factor.challenge'));

        Mail::assertSent(SendMessage::class, 1);
        $this->assertGuest('adminPanel');
    }

    #[Test]
    public function expired_code_invalidates_the_challenge(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $admin->username)
            ->set('password', 'secret1234')
            ->call('authenticate');

        $code = $this->extractCode();

        // ۶ دقیقه بعد — کد منقضی است
        $this->travel(6)->minutes();

        $service = app(AdminTwoFactorService::class);
        $this->assertNull($service->pending(), 'چالش منقضی باید باطل شود');

        Livewire::test(TwoFactorChallenge::class)
            ->assertRedirect(route('np.login'));
    }

    #[Test]
    public function old_panel_login_redirects_to_the_shared_challenge(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('security_captcha_enabled', 'off');

        $admin = $this->newAdmin();

        $response = $this->post('/admin-old/admin/login/submit', [
            'username' => $admin->username,
            'password' => 'secret1234',
        ]);

        $response->assertRedirect(route('np.two-factor.challenge'));
        $this->assertGuest('adminPanel');
        Mail::assertSent(SendMessage::class, 1);

        // کد تأیید شود → مقصد پنل قدیمی
        $code = $this->extractCode();

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', $code)
            ->call('verify')
            ->assertHasNoErrors();

        $this->assertAuthenticated('adminPanel');
    }

    #[Test]
    public function challenge_page_without_pending_state_redirects_to_login(): void
    {
        option_update('admin_2fa_enabled', 'on');

        Livewire::test(TwoFactorChallenge::class)
            ->assertRedirect(route('np.login'));
    }
}
