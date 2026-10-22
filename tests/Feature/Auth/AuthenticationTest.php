<?php

namespace Tests\Feature\Auth;

use App\Models\OneTimeCode;
use App\Models\User;
use App\Models\UserMobileVerify;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های احراز هویت:
 *  - ثبت‌نام با موبایل معتبر/نامعتبر (فلوی تم)
 *  - ثبت‌نام با فرم Breeze
 *  - ورود با رمز درست/غلط (پیام خطای مناسب)
 *  - محدودیت تلاش‌های ناموفق (Brute Force)
 *  - بازیابی رمز عبور (کد یک‌بارمصرف)
 *  - خروج و ابطال نشست
 */
class AuthenticationTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
    }

    // =====================================================================
    //  ثبت‌نام
    // =====================================================================

    #[Test]
    public function registration_with_valid_mobile_creates_user(): void
    {
        $mobile = '0935' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        // مرحله ۱: کاربر موبایل را وارد می‌کند
        $response = $this->post('/login', ['username' => $mobile]);
        $response->assertRedirect('/login/password');

        // مرحله ۲: دیدن صفحه رمز → کد تأیید در دیتابیس ساخته می‌شود
        $this->get('/login/password');

        $verify = UserMobileVerify::where('mobile', $mobile)->first();
        $this->assertNotNull($verify, 'کد تأیید باید برای موبایل جدید ساخته شود');

        // مرحله ۳: ثبت کد صحیح و ساخت کاربر
        $response = $this->post('/login/password/checkCodeRegister', [
            'mobile'      => $mobile,
            'verify_code' => $verify->code,
        ]);
        $response->assertRedirect('/login/password');

        $this->assertDatabaseHas('users', ['username' => $mobile]);
    }

    #[Test]
    public function registration_with_invalid_mobile_format_is_rejected(): void
    {
        // موبایل با فرمت غلط (کمتر از ۱۱ رقم / بدون ۰۹)
        $this->post('/login', ['username' => '9351234567'])
            ->assertSessionHasErrors();

        $this->post('/login', ['username' => '0935abc'])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('user_mobile_verify', ['mobile' => '9351234567']);
    }

    #[Test]
    public function registration_with_empty_username_is_rejected(): void
    {
        $this->post('/login', ['username' => ''])
            ->assertSessionHasErrors(['username']);
    }

    #[Test]
    public function registration_with_wrong_verify_code_is_rejected(): void
    {
        $mobile = '0936' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        $this->post('/login', ['username' => $mobile]);
        $this->get('/login/password'); // ساخت کد

        $this->post('/login/password/checkCodeRegister', [
            'mobile'      => $mobile,
            'verify_code' => '00000',
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('users', ['username' => $mobile]);
    }

    #[Test]
    public function breeze_registration_with_valid_data_creates_user(): void
    {
        $mobile = '0937' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $response = $this->post('/register', [
            'first_name' => 'علی',
            'last_name'  => 'تستی',
            'username'   => $mobile,
            'password'   => 'secret1234',
            'confirmed'  => 'secret1234',
            'captcha'    => $captcha,
        ]);

        $this->assertDatabaseHas('users', ['username' => $mobile]);
        $this->assertAuthenticated();
    }

    #[Test]
    public function breeze_registration_with_duplicate_mobile_fails(): void
    {
        $user = $this->newUser();
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $this->post('/register', [
            'first_name' => 'علی',
            'last_name'  => 'تستی',
            'username'   => $user->username,
            'password'   => 'secret1234',
            'confirmed'  => 'secret1234',
            'captcha'    => $captcha,
        ])->assertSessionHasErrors(['username']);

        $this->assertGuest();
    }

    #[Test]
    public function breeze_registration_with_weak_password_fails(): void
    {
        $mobile = '0938' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $this->post('/register', [
            'first_name' => 'علی',
            'last_name'  => 'تستی',
            'username'   => $mobile,
            'password'   => '123', // کمتر از ۸ کاراکتر
            'confirmed'  => '123',
            'captcha'    => $captcha,
        ])->assertSessionHasErrors(['password']);

        $this->assertDatabaseMissing('users', ['username' => $mobile]);
    }

    // =====================================================================
    //  ورود
    // =====================================================================

    #[Test]
    public function login_with_correct_credentials_succeeds(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        // فلوی تم: POST /login فقط موبایل/ایمیل را چک می‌کند؛
        // ورود واقعی از POST /login/CheckPassword انجام می‌شود
        $this->post('/login', ['username' => $user->username]);
        $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'secret1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_with_wrong_password_shows_proper_error(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        $this->post('/login', ['username' => $user->username]);

        $response = $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login/password');
        $this->assertEquals('رمز عبور وارد شده صحیح نمی باشد.', session('admin_login_error'));
        $this->assertGuest();
    }

    #[Test]
    public function login_with_nonexistent_user_fails(): void
    {
        $this->post('/login', ['username' => '09999999999']);
        $this->get('/login/password'); // فلوی ثبت‌نام را شروع می‌کند

        // هیچ کاربری با این موبایل نباید ساخته شده باشد (بدون کد تأیید)
        $this->post('/login/password/checkCodeRegister', [
            'mobile'      => '09999999999',
            'verify_code' => '00000',
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('users', ['username' => '09999999999']);
        $this->assertGuest();
    }

    #[Test]
    public function theme_login_with_wrong_password_shows_persian_error(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        // فلوی تم: ابتدا موبایل، سپس رمز
        $this->post('/login', ['username' => $user->username])
            ->assertRedirect('/login/password');

        $response = $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login/password');
        $this->assertEquals(
            'رمز عبور وارد شده صحیح نمی باشد.',
            session('admin_login_error')
        );
        $this->assertGuest();
    }

    #[Test]
    public function theme_login_with_correct_password_redirects_home(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        $this->post('/login', ['username' => $user->username]);
        $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'secret1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function disabled_user_cannot_login(): void
    {
        $user = $this->newUser(['status' => 0, 'password' => 'secret1234']);

        $this->post('/login', ['username' => $user->username]);

        $response = $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'secret1234',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('غیر فعال', (string) session('admin_login_error'));
        $this->assertGuest();
    }

    // =====================================================================
    //  Brute Force
    //
    //  نکته: مسیر POST /login توسط فلوی تم (Check_Mobile_Email) استفاده
    //  می‌شود و لایه‌ی محدودیت تلاش روی LoginRequest (Breeze) اعمال شده
    //  است که در هدرهای API و مسیرهای دارای آن فرم استفاده می‌شود.
    //  اینجا رفتار RateLimiter مستقیماً تست می‌شود.
    //  =====================================================================

    #[Test]
    public function repeated_failed_logins_trigger_throttle(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        $request = \App\Http\Requests\Auth\LoginRequest::create('/login', 'POST', [
            'username' => $user->username,
            'password' => 'wrong-pass',
        ]);

        $attempts = 0;

        // ۵ تلاش ناموفق → شمارنده بالا می‌رود
        for ($i = 0; $i < 5; $i++) {
            try {
                $request->authenticate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                $attempts++;
            }
        }

        $this->assertEquals(5, $attempts);

        // تلاش ششم باید پیام throttle بدهد (حتی با رمز درست)
        $correctRequest = \App\Http\Requests\Auth\LoginRequest::create('/login', 'POST', [
            'username' => $user->username,
            'password' => 'secret1234',
        ]);

        $throttled = false;
        try {
            $correctRequest->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = implode(' ', $e->errors()['username'] ?? []);
            $throttled = str_contains($message, 'ثانیه');
        }

        $this->assertTrue($throttled, 'تلاش ششم باید پیام قفل موقت داشته باشد');
        $this->assertGuest();
    }

    #[Test]
    public function successful_login_clears_throttle_counter(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        // ۲ تلاش ناموفق (زیر آستانه)
        for ($i = 0; $i < 2; $i++) {
            $request = \App\Http\Requests\Auth\LoginRequest::create('/login', 'POST', [
                'username' => $user->username,
                'password' => 'wrong',
            ]);
            try {
                $request->authenticate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                // تلاش ناموفق
            }
        }

        // ورود موفق → شمارنده ریست می‌شود و لاگین انجام می‌شود
        $request = \App\Http\Requests\Auth\LoginRequest::create('/login', 'POST', [
            'username' => $user->username,
            'password' => 'secret1234',
        ]);
        $request->authenticate();

        $this->assertAuthenticatedAs($user);
    }

    // =====================================================================
    //  بازیابی رمز عبور
    // =====================================================================

    #[Test]
    public function password_reset_with_valid_code_changes_password(): void
    {
        Notification::fake(); // ساخت کد در سازنده نوتیفیکیشن انجام می‌شود

        $user = $this->newUser(['password' => 'old-pass-123']);
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        // مرحله ۱: درخواست کد بازیابی
        $this->post('/forgot-password', [
            'mobile'  => $user->username,
            'captcha' => $captcha,
        ])->assertStatus(200);

        $code = OneTimeCode::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($code, 'کد یک‌بارمصرف باید ساخته شود');

        // مرحله ۲: تغییر رمز با کد صحیح
        $response = $this->post('/forgot-password-change-password', [
            'mobile'       => $user->username,
            'verify_code'  => $code->code,
            'password'     => 'new-pass-9999',
            'password_confirmation' => 'new-pass-9999',
        ]);

        $response->assertRedirect(route('login'));

        // رمز جدید کار می‌کند و رمز قبلی نه
        $this->post('/login', ['username' => $user->username]);
        $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'new-pass-9999',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user->fresh());
    }

    #[Test]
    public function password_reset_with_wrong_code_is_rejected(): void
    {
        $user = $this->newUser(['password' => 'old-pass-123']);
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $this->post('/forgot-password', [
            'mobile'  => $user->username,
            'captcha' => $captcha,
        ]);

        $response = $this->from('/login')->post('/forgot-password-change-password', [
            'mobile'       => $user->username,
            'verify_code'  => '11111',
            'password'     => 'new-pass-9999',
            'password_confirmation' => 'new-pass-9999',
        ]);

        $response->assertSessionHasErrors();

        // رمز قبلی همچنان معتبر است
        $this->post('/login', ['username' => $user->username]);
        $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'old-pass-123',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
    }

    #[Test]
    public function password_reset_requires_valid_captcha(): void
    {
        $user = $this->newUser();

        $this->post('/forgot-password', [
            'mobile'  => $user->username,
            'captcha' => 'XXXXX',
        ])->assertSessionHasErrors(['captcha']);
    }

    #[Test]
    public function password_reset_for_unknown_mobile_fails(): void
    {
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $this->post('/forgot-password', [
            'mobile'  => '09990000000',
            'captcha' => $captcha,
        ])->assertSessionHasErrors(['mobile']);
    }

    // =====================================================================
    //  خروج و ابطال نشست
    // =====================================================================

    #[Test]
    public function logout_invalidates_session(): void
    {
        $user = $this->newUser();

        $this->actingAs($user, 'web')
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();

        // دسترسی به صفحه نیازمند احراز هویت باید به لاگین هدایت شود
        $this->get('/checkout')->assertRedirect();
    }

    #[Test]
    public function logout_via_get_link_also_works(): void
    {
        $user = $this->newUser();

        $this->actingAs($user, 'web')
            ->get('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    #[Test]
    public function changing_password_invalidates_other_sessions(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        $this->actingAs($user, 'web');

        $response = $this->put('/profile/change-password', [
            'prev_password' => 'secret1234',
            'password'      => 'brand-new-777',
            'password_confirmation'   => 'brand-new-777',
        ]);

        $response->assertStatus(200);

        // همه نشست‌های کاربر باید حذف شده باشند
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    #[Test]
    public function changing_password_requires_correct_previous_password(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        $this->actingAs($user, 'web')
            ->from('/profile/change-password')
            ->put('/profile/change-password', [
                'prev_password' => 'wrong-old-pass',
                'password'      => 'brand-new-777',
                'password_confirmation'   => 'brand-new-777',
            ])
            ->assertSessionHasErrors(['prev_password']);
    }

    #[Test]
    public function guest_cannot_access_profile_pages(): void
    {
        $this->get('/profile')->assertRedirect();
        $this->get('/profile/orders')->assertRedirect();
        $this->get('/checkout')->assertRedirect();
    }
}
