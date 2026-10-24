<?php

namespace Tests\Feature\Security;

use App\Models\BotTrap;
use App\Services\BotDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های نفوذ و ضد هک (درخواست کاربر: تست‌های امنیتی و جلوگیری از هک)
 *
 * لایه‌های دفاعی که هرکدام یک تست دارند:
 *  ۱) تله‌ی ربات (Bot Trap): مسیرهای طعمه (wp-admin، .env و…) ⇒ 403 + مسدودسازی IP
 *  ۲) هانی‌پات و تله‌زمان در فرم‌های حساس (BotDetector)
 *  ۳) هدرهای امنیتی پاسخ‌ها (SecurityHeaders)
 *  ۴) Session Fixation — احتمال نفوذ با تثبیت شناسه نشست
 *  ۵) فلگ‌های کوکی نشست (HttpOnly / SameSite)
 *  ۶) هش شدن رمز عبور (هرگز plaintext)
 *  ۷) Mass Assignment — ارتقای سطح دسترسی از طریق فیلدهای اضافه
 *  ۸) Login Enumeration — عدم افشای وجود/نبود کاربر
 *  ۹) Open Redirect — ریدایرکت به دامنه بیرونی
 *  ۱۰) محافظت مسیرهای مدیریتی
 *  ۱۱) دستکاری لینک دانلود (امضای HMAC)
 *  ۱۲) قفل‌شدن با حدس مکرر کد یک‌بارمصرف (OTP brute force)
 *  ۱۳) IDOR — آدرس و خبرنامه‌ی کاربر دیگر
 */
class PenetrationTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    protected function tearDown(): void
    {
        // تله‌ها در تست مسیر طعمه فعال می‌شوند — حتماً پاک شوند
        BotTrap::query()->where('ip_address', '127.0.0.1')->delete();
        Cache::forget('security:blocked_bot_ips');
        parent::tearDown();
    }

    // =====================================================================
    //  ۱) تله‌ی ربات — مسیرهای طعمه
    // =====================================================================

    #[Test]
    public function scanner_probing_decoy_paths_gets_trapped_and_blocked(): void
    {
        // یک اسکنر خودکار مسیرهای معروف CMS و فایل‌های حساس را امتحان می‌کند
        $decoys = ['/wp-login.php', '/wp-admin/', '/phpmyadmin', '/xmlrpc.php'];

        foreach ($decoys as $decoy) {
            $response = $this->get($decoy);
            $this->assertSame(403, $response->status(), "مسیر طعمه {$decoy} باید 403 بدهد");
        }

        // IP اسکنر حالا در تله است — حتی صفحات عادی هم 403 می‌دهند
        $this->assertTrue(BotTrap::isBlocked('127.0.0.1'), 'IP باید مسدود شده باشد');

        $response = $this->get('/');
        $this->assertSame(403, $response->status(), 'بعد از تله‌گذاری کل سایت برای مهاجم 403 است');
    }

    #[Test]
    public function sensitive_files_are_never_served(): void
    {
        // .env و فایل‌های حساس هرگز محتوایشان برگردانده نمی‌شود
        $response = $this->get('/.env');
        $this->assertSame(403, $response->status());
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());

        $response = $this->get('/.git/config');
        $this->assertSame(403, $response->status());
    }

    // =====================================================================
    //  ۲) هانی‌پات فرم‌ها
    // =====================================================================

    #[Test]
    public function honeypot_filled_form_is_detected_as_bot(): void
    {
        $detector = app(BotDetector::class);

        // ربات‌ها همه‌ی فیلدها را پر می‌کنند — فیلد مخفی پر شد = ربات
        $request = \Illuminate\Http\Request::create('/login', 'POST', [
            'username'    => 'admin',
            'password'    => 'x',
            'website_url' => 'http://spam-bot.tld',
        ]);

        $this->assertTrue($detector->isBot($request, now()->subSeconds(30)->getTimestamp()));
    }

    #[Test]
    public function too_fast_form_submission_is_rejected(): void
    {
        $detector = app(BotDetector::class);

        // فرمی که کمتر از ۳ ثانیه بعد از رندر پر شده = ربات
        $request = \Illuminate\Http\Request::create('/login', 'POST', ['username' => 'admin']);

        $this->assertTrue($detector->isBot($request, now()->getTimestamp()));
        $this->assertTrue($detector->isBot($request, now()->subSeconds(1)->getTimestamp()));
    }

    #[Test]
    public function human_pace_form_submission_passes(): void
    {
        $detector = app(BotDetector::class);

        $request = \Illuminate\Http\Request::create('/login', 'POST', ['username' => 'admin']);

        $this->assertFalse(
            $detector->isBot($request, now()->subSeconds(10)->getTimestamp()),
            'پر کردن فرم با سرعت انسانی نباید ربات تشخیص داده شود'
        );
    }

    // =====================================================================
    //  ۳) هدرهای امنیتی
    // =====================================================================

    #[Test]
    public function security_headers_are_sent_on_every_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString(
            'camera=()',
            (string) $response->headers->get('Permissions-Policy'),
            'Permissions-Policy باید سنسورهای حساس را غیرفعال کند'
        );
    }

    // =====================================================================
    //  ۴) Session Fixation
    // =====================================================================

    #[Test]
    public function session_id_is_regenerated_on_successful_login(): void
    {
        $user = $this->newUser(['password' => 'secret1234']);

        // مهاجم قبل از ورود، شناسه نشست را برای قربانی «تثبیت» کرده است
        $this->get('/login');
        $fixedSessionId = session()->getId();

        // قربانی با نشست تثبیت‌شده وارد می‌شود
        $this->post('/login/CheckPassword', [
            'username' => $user->username,
            'password' => 'secret1234',
        ]);

        $this->assertAuthenticated();
        $this->assertNotEquals(
            $fixedSessionId,
            session()->getId(),
            'Laravel باید بعد از ورود موفق شناسه نشست را عوض کند (ضد session fixation)'
        );
    }

    // =====================================================================
    //  ۵) فلگ‌های کوکی نشست
    // =====================================================================

    #[Test]
    public function session_cookie_is_http_only_and_same_site(): void
    {
        $response = $this->get('/login');

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_contains($cookie->getName(), 'session'));

        $this->assertNotNull($sessionCookie, 'کوکی نشست باید ارسال شود');
        $this->assertTrue($sessionCookie->isHttpOnly(), 'کوکی نشست باید HttpOnly باشد (ضد سرقت XSS)');
        $this->assertSame('lax', $sessionCookie->getSameSite(), 'کوکی نشست باید SameSite=Lax باشد (ضد CSRF)');
    }

    // =====================================================================
    //  ۶) ذخیره‌سازی رمز عبور
    // =====================================================================

    #[Test]
    public function passwords_are_never_stored_in_plain_text(): void
    {
        $user = $this->newUser(['password' => 'my-plain-secret-9']);

        $this->assertNotSame(
            'my-plain-secret-9',
            $user->fresh()->password,
            'رمز عبور هرگز نباید خام ذخیره شود'
        );
        $this->assertStringStartsWith('$2y$', $user->fresh()->password, 'رمز باید با bcrypt هش شود');
        $this->assertTrue(Hash::check('my-plain-secret-9', $user->fresh()->password));
    }

    #[Test]
    public function weak_passwords_are_rejected_at_registration(): void
    {
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $response = $this->post('/register', [
            'first_name' => 'ضعیف',
            'last_name'  => 'رمز',
            'username'   => '09121110000',
            'password'   => '1234567', // ۷ کاراکتر — زیر حداقل ۸
            'confirmed'  => '1234567',
            'captcha'    => $captcha,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['username' => '09121110000']);
    }

    // =====================================================================
    //  ۷) Mass Assignment / ارتقای دسترسی
    // =====================================================================

    #[Test]
    public function attacker_cannot_escalate_privileges_via_registration_fields(): void
    {
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        // تزریق فیلدهای مدیریتی در فرم ثبت‌نام
        $response = $this->post('/register', [
            'first_name'          => 'هکر',
            'last_name'           => 'تست',
            'username'            => '09122220000',
            'password'            => 'secret1234',
            'confirmed'           => 'secret1234',
            'captcha'             => $captcha,
            // فیلدهای مخرب:
            'level'               => 'admin',
            'status'              => 1,
            'verified_at'         => now()->toDateTimeString(),
            'force_to_password_change' => false,
        ]);

        $this->assertDatabaseHas('users', ['username' => '09122220000']);

        $user = \App\Models\User::where('username', '09122220000')->first();
        $this->assertSame(
            'user',
            $user->level,
            'ثبت‌نام نباید بتواند سطح دسترسی را admin کند (mass assignment)'
        );
    }

    #[Test]
    public function attacker_cannot_escalate_privileges_via_profile_update(): void
    {
        $user = $this->newUser();

        $this->actingAs($user, 'web')
            ->put('/profile', [
                'first_name' => 'نام',
                'last_name'  => 'خانوادگی',
                'email'      => 'normal-' . \Illuminate\Support\Str::random(5) . '@test.ir',
                // تزریق فیلدهای مدیریتی:
                'level'      => 'admin',
                'status'     => 1,
            ]);

        $this->assertSame(
            'user',
            $user->fresh()->level,
            'به‌روزرسانی پروفایل نباید سطح دسترسی را تغییر دهد'
        );
    }

    // =====================================================================
    //  ۸) Login Enumeration
    // =====================================================================

    #[Test]
    public function login_first_step_does_not_reveal_user_existence(): void
    {
        // نام کاربری موجود و ناموجود — پاسخ اول باید یکسان باشد
        $existing = $this->newUser();

        $responseExisting = $this->post('/login', ['username' => $existing->username]);
        $responseMissing  = $this->post('/login', ['username' => '09120000000']);

        $this->assertSame($responseExisting->status(), $responseMissing->status());
        $this->assertSame(
            $responseExisting->headers->get('Location'),
            $responseMissing->headers->get('Location'),
            'مسیر ریدایرکت برای کاربر موجود و ناموجود باید یکسان باشد'
        );
    }

    // =====================================================================
    //  ۹) Open Redirect
    // =====================================================================

    #[Test]
    public function logout_does_not_redirect_to_external_domains(): void
    {
        $user = $this->newUser();
        $this->actingAs($user, 'web');

        $response = $this->post('/logout', [], ['Referer' => 'https://evil.example.com']);

        $location = (string) $response->headers->get('Location');
        $this->assertStringNotContainsString(
            'evil.example.com',
            $location,
            'خروج نباید به دامنه‌ی بیرونی ریدایرکت شود'
        );
    }

    // =====================================================================
    //  ۱۰) محافظت مسیرهای مدیریتی
    // =====================================================================

    #[Test]
    public function admin_panel_is_not_reachable_for_guests(): void
    {
        $response = $this->get('/admin/admin');

        $this->assertContains($response->status(), [301, 302, 307], 'پنل مدیریت باید مهمان را به لاگین بفرستد');

        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('login', $location);
    }

    #[Test]
    public function old_admin_panel_is_not_reachable_for_guests(): void
    {
        $response = $this->get('/admin-old/admin');

        $this->assertContains($response->status(), [301, 302, 307]);
        $this->assertStringContainsString('login', (string) $response->headers->get('Location'));
    }

    // =====================================================================
    //  ۱۱) دستکاری لینک دانلود
    // =====================================================================

    #[Test]
    public function download_link_cannot_be_forged_without_valid_signature(): void
    {
        $user = $this->newUser();
        $product = $this->newDownloadProduct();
        $price = $product->prices()->first();

        // بدون mac/امضا
        $this->actingAs($user, 'web')
            ->get('/products/' . $price->id . '/download?time=' . now()->addHour()->getTimestamp() . '&mac=forged-signature')
            ->assertStatus(403);

        // بدون هیچ پارامتری
        $this->actingAs($user, 'web')
            ->get('/products/' . $price->id . '/download')
            ->assertStatus(403);
    }

    // =====================================================================
    //  ۱۲) Brute-force روی کد یک‌بارمصرف
    // =====================================================================

    #[Test]
    public function repeated_wrong_otp_guesses_lock_the_account(): void
    {
        \Illuminate\Support\Facades\RateLimiter::clear('otp-confirm:' . sha1('0|127.0.0.1'));

        $user = $this->newUser();

        // سقف: ۵ تلاش (MAX_CODE_ATTEMPTS) — تلاش ششم باید ۴۲۹ بدهد
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login-with-code/confirm', [
                'mobile'      => $user->username,
                'verify_code' => '0000' . $i, // کد غلط
            ], ['Accept' => 'application/json']);

            $this->assertNotSame(429, $response->status(), "تلاش {$i} نباید مسدود باشد");
        }

        // تلاش ششم — مسدود
        $response = $this->post('/login-with-code/confirm', [
            'mobile'      => $user->username,
            'verify_code' => '99999',
        ]);

        $this->assertSame(429, $response->status(), 'حدس مکرر کد باید قفل تلاش ایجاد کند');
        $this->assertGuest();

        \Illuminate\Support\Facades\RateLimiter::clear('otp-confirm:' . sha1($user->id . '|127.0.0.1'));
    }

    // =====================================================================
    //  ۱۳) IDOR — دسترسی به منابع کاربر دیگر
    // =====================================================================

    #[Test]
    public function address_details_of_other_user_are_hidden(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $address = $this->newAddress($owner);

        // GET /profile/addresses/{id} فقط باید آدرسِ خود کاربر را برگرداند
        $response = $this->actingAs($intruder, 'web')
            ->getJson('/profile/addresses/' . $address->id);

        $this->assertSame(
            404,
            $response->status(),
            'نمایش جزئیات آدرس کاربر دیگر باید 404 بدهد (IDOR)'
        );

        // مالک هنوز می‌بیند
        $this->actingAs($owner, 'web')
            ->getJson('/profile/addresses/' . $address->id)
            ->assertOk();
    }

    #[Test]
    public function newsletter_unsubscribe_of_other_user_is_forbidden(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();

        $subscription = \App\Models\Newsletter::create([
            'contact'  => $owner->email,
            'is_active' => true,
        ]);

        // مهاجم نمی‌تواند اشتراک قربانی را لغو کند
        $this->actingAs($intruder, 'web')
            ->get('/newsletter/unsubscribe/' . $subscription->id)
            ->assertNotFound();

        $this->assertTrue($subscription->fresh()->is_active, 'اشتراک قربانی نباید لغو شده باشد');

        // مالک می‌تواند
        $this->actingAs($owner, 'web')
            ->get('/newsletter/unsubscribe/' . $subscription->id)
            ->assertRedirect();

        $this->assertFalse($subscription->fresh()->is_active);
    }
}
