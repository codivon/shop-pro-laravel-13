<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\NewSeller;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerRegistration;
use App\Models\Wallet;
use App\Notifications\Sms\SellerPhoneOtp;
use App\Support\Finnotech;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۱۰ — ثبت‌نام مرحله‌ای جدید فروشندگان (پنل مدرن) + لندینگ.
 * --------------------------------------------------------------------------
 *  • گام ۱ فقط شمارهٔ موبایل (ایمیل/رمز به گام کسب‌وکار منتقل شد) → کد ۶ رقمی
 *  • تایمر ارسال مجدد ۲ دقیقه‌ای سروری — با رفرش از دست نمی‌رود (updated_at دیتابیس)
 *  • گام وضعیت (حقیقی/حقوقی) + گام اطلاعات تکمیلی (کد ملی + احراز هویت فینوتک)
 *  • ذخیرهٔ هر مرحله در seller_registrations + گیت ترتیب مراحل (جلوتر ممنوع، برگشت آزاد)
 *  • سوییچseller_kyc_mobile_verify + کلیدهای فینوتک در تب چندفروشندگی برنامه‌نویس
 *  • لندینگ: ثبت‌نام سریع فقط موبایل (ارسال مستقیم کد) + پیش‌فرض‌های محتوای seed شده
 */
class Phase110Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();

        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_LANDING_PAGE, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, '0');
        MultiVendorSettings::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, '1');
        option_update(MultiVendorSettings::KEY_LANDING_PAGE, '1');
        option_update(MultiVendorSettings::KEY_ENABLED, 'false');
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, '0');
        MultiVendorSettings::flushCache();
        Cache::flush();

        parent::tearDown();
    }

    private function enableVendor(): void
    {
        MultiVendorSettings::setEnabled(true);
        MultiVendorSettings::flushCache();
        Cache::flush();
    }

    /** ادمین creator + گیت سراسری (الگوی فاز ۱۰۹) */
    private function actingCreator(): Admin
    {
        Gate::after(function ($user = null) {
            return $user instanceof Admin;
        });

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۱۰',
            'username' => 'creator-'.Str::random(6),
            'mobile' => '0912'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'email' => 'creator-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    private function randomMobile(): string
    {
        return '0936'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    }

    /** عدد فارسی برای نام‌های یکتای فروشگاه (قانون persian_alpha_num لاتین قبول نمی‌کند) */
    private function faNumber(): string
    {
        return strtr((string) random_int(100000, 999999), [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    /** ارسال موبایل (گام ۱) و برگرداندن موبایل */
    private function sendOtp(?string $mobile = null): string
    {
        $mobile = $mobile ?: $this->randomMobile();

        $response = $this->post(route('seller.registration_phone_send'), ['mobile' => $mobile]);

        $response->assertOk()->assertJsonPath('status', 'success');

        return $mobile;
    }

    /**
     * فاز ۱۱۷ — شناسهٔ ملی حقوقی «معتبر» با الگوریتم رسمی (همان الگوریتم ir_company_id)
     */
    private function validCompanyId(): string
    {
        $base = (string) random_int(1000000000, 9999999999); // ۱۰ رقم اول
        $dec = [29, 27, 23, 19, 17];
        $t = ((int) $base[9]) + 2;
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += ($t + (int) $base[$i]) * $dec[$i % 5];
        }

        $r = $sum % 11;

        return $base.($r === 10 ? 0 : $r);
    }

    /**
     * فاز ۱۱۷ — کد ملی «معتبر» با الگوریتم رسمی کنترل رقم بساز
     * (ولیدیشن جدید registration/identity کدهای تصادفی را رد می‌کند)
     */
    private function validNationalCode(): string
    {
        $base = (string) random_int(100000000, 999999999);

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $base[$i]) * (10 - $i);
        }

        $remainder = $sum % 11;

        return $base.($remainder < 2 ? $remainder : 11 - $remainder);
    }

    /** جریان کامل تا گام داده‌شده: send → verify → password → (type) → (identity) → (business) */
    private function flowTo(string $step, string $type = 'real'): SellerRegistration
    {
        $mobile = $this->sendOtp();
        $code = (string) NewSeller::where('mobile', $mobile)->value('code_mobile_verification');

        $this->post(route('seller.registration_phone_verify'), [
            'mobile' => $mobile,
            'verify_code' => $code,
        ])->assertJsonPath('status', 'success');

        $draft = SellerRegistration::where('mobile', $mobile)->first();
        $this->assertNotNull($draft);

        // فاز ۱۱۲ — گام رمز عبور بعد از تأیید کد
        $this->post(route('seller.registration_password_store'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertJsonPath('status', 'success');
        $draft->refresh();

        if (in_array($step, ['type', 'identity', 'business', 'documents'], true)) {
            $this->post(route('seller.registration_type_store'), ['type' => $type])
                ->assertJsonPath('status', 'success');
            $draft->refresh();
        }

        if (in_array($step, ['identity', 'business', 'documents'], true)) {
            $payload = [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'علی',
                'last_name' => 'فرهادی',
                'ownership_confirm' => '1',
                'info_correct' => '1',
            ];

            if ($type === 'real') {
                $payload += ['gender' => 'male', 'birth_year' => '1370', 'birth_month' => '6', 'birth_day' => '15'];
            } else {
                $payload += [
                    'company_name' => 'شرکت تست فاز',
                    'company_type' => 'سهامی خاص',
                    'company_registration_number' => '1234',
                    'company_national_identity_number' => $this->validCompanyId(),
                    'company_economic_number' => '',
                ];
            }

            $this->post(route('seller.registration_identity_store'), $payload)
                ->assertJsonPath('status', 'success');
            $draft->refresh();
        }

        if ($step === 'documents') {
            $this->post(route('seller.registration_business_store'), [
                'email' => 'biz-'.Str::random(6).'@test.ir',
                'password' => 'secret12345',
                'state_id' => 1,
                'city_id' => 1,
                'address' => 'تهران خیابان تست پلاک ۵',
                'post_code' => '1234567890',
                'phone' => '02112345678',
                'business_name' => 'فروشگاه تست فاز '.$this->faNumber(),
                'shaba_number' => 'IR120570028180010101010101',
                'main_supply_category_id' => 1,
                'number_of_products' => '50',
                'econtract' => '1',
                'lat_and_long' => '',
            ])->assertJsonPath('status', 'success');
            $draft->refresh();
        }

        return $draft;
    }

    /* ================= ۱) گام موبایل و کد ۶ رقمی ================= */

    #[Test]
    #[TestDox('صفحهٔ ثبت‌نام فقط فیلد موبایل دارد (ایمیل/رمز حذف شده) و ۸ گامی است')]
    public function registration_page_is_phone_only(): void
    {
        $this->enableVendor();

        $page = $this->get(route('seller.registration'));
        $page->assertOk();
        $page->assertSee(route('seller.registration_phone_send'), false);
        $page->assertSee('شمارهٔ موبایل', false);
        $page->assertSee('گام', false);
        $page->assertSee('۸', false);
        // فیلدهای قدیمی حذف شده‌اند (با کوتیشن انتهایی لنگر می‌شود تا با URL جدید قاطی نشود)
        $page->assertDontSee('name="email"', false);
        $page->assertDontSee('name="password"', false);
        $page->assertDontSee(route('seller.registration_new_seller')."'", false);
    }

    #[Test]
    #[TestDox('ارسال موبایل کد ۶ رقمی می‌سازد، پیش‌نویس می‌سازد و نوتیفیکیشن می‌فرستد')]
    public function phone_send_creates_draft_and_six_digit_code(): void
    {
        $this->enableVendor();

        $mobile = $this->sendOtp();

        $newSeller = NewSeller::where('mobile', $mobile)->first();
        $this->assertNotNull($newSeller);
        $this->assertTrue((int) $newSeller->code_mobile_verification >= 100000);
        $this->assertTrue((int) $newSeller->code_mobile_verification <= 999999);

        $draft = SellerRegistration::where('mobile', $mobile)->first();
        $this->assertNotNull($draft);
        $this->assertSame('otp', $draft->step);
        $this->assertNotNull($draft->token);

        Notification::assertSentTo($newSeller, SellerPhoneOtp::class);
    }

    #[Test]
    #[TestDox('موبایلِ فروشندهٔ موجود رد می‌شود با پیام «قبلاً ثبت شده»')]
    public function registered_mobile_is_rejected(): void
    {
        $this->enableVendor();

        $seller = Seller::create([
            'slug' => 'ph-'.Str::random(8),
            'email' => 'ph-'.Str::random(6).'@test.ir',
            'mobile' => $this->randomMobile(),
            'password' => Hash::make('secret1234'),
            'status' => 'ACTIVE',
            'status_register' => 'complete',
        ]);

        $response = $this->post(route('seller.registration_phone_send'), ['mobile' => $seller->mobile]);

        // فاز ۱۱۱/۱۱۲ — فروشندهٔ فعالِ تکمیل‌شده ⇒ exists + آدرس ورود (مودال دو‌دکمه‌ای فرانت)
        $response->assertOk()->assertJsonPath('status', 'exists');
        $this->assertStringContainsString('از قبل ثبت‌نام کرده', (string) $response->json('message'));
        $this->assertSame(route('seller.login'), (string) $response->json('login_url'));
        Notification::assertNothingSent();
    }

    #[Test]
    #[TestDox('تأیید کد درست پیش‌نویس را به گام رمز عبور می‌برد؛ کد اشتباه رد می‌شود')]
    public function otp_verify_advances_to_type(): void
    {
        $this->enableVendor();
        $mobile = $this->sendOtp();
        $code = (string) NewSeller::where('mobile', $mobile)->value('code_mobile_verification');

        // کد اشتباه
        $this->post(route('seller.registration_phone_verify'), [
            'mobile' => $mobile,
            'verify_code' => $code !== '000000' ? '000000' : '111111',
        ])->assertJsonPath('status', 'error');

        // کد درست — فاز ۱۱۲: مقصد بعدی گام رمز عبور است
        $this->post(route('seller.registration_phone_verify'), [
            'mobile' => $mobile,
            'verify_code' => $code,
        ])->assertJsonPath('redirect', route('seller.registration_password'));

        $this->assertSame('password', SellerRegistration::where('mobile', $mobile)->value('step'));

        // ذخیرهٔ رمز → گام وضعیت
        $this->post(route('seller.registration_password_store'), [
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertJsonPath('redirect', route('seller.registration_type'));

        $this->assertSame('type', SellerRegistration::where('mobile', $mobile)->value('step'));
        $this->assertNotSame('', (string) SellerRegistration::where('mobile', $mobile)->value('password'));
    }

    #[Test]
    #[TestDox('ارسال مجدد کد تا ۲ دقیقه بسته است و بعد از آن کد جدید با نوتیفیکیشن می‌آید')]
    public function resend_gate_is_two_minutes(): void
    {
        $this->enableVendor();
        $mobile = $this->sendOtp();
        $oldCode = (string) NewSeller::where('mobile', $mobile)->value('code_mobile_verification');

        // بلافاصله: بسته (گیت ۲ دقیقه)
        $this->post(route('seller.registration_phone_resend'), ['mobile' => $mobile])
            ->assertJsonPath('status', 'errorTime');

        // ۳ دقیقه بعد: باز + کد عوض می‌شود
        NewSeller::where('mobile', $mobile)->update(['updated_at' => now()->subMinutes(3)]);
        Notification::fake();

        $this->post(route('seller.registration_phone_resend'), ['mobile' => $mobile])
            ->assertJsonPath('status', 'success');

        $newCode = (string) NewSeller::where('mobile', $mobile)->value('code_mobile_verification');
        $this->assertNotSame($oldCode, $newCode);
        $this->assertTrue((int) $newCode >= 100000 && (int) $newCode <= 999999);
    }

    #[Test]
    #[TestDox('صفحهٔ کد، تایمر ارسال مجدد سمت سرور را رندر می‌کند (اندپوینت resend و ۶ خانه)')]
    public function otp_page_renders_server_timer(): void
    {
        $this->enableVendor();
        $mobile = $this->sendOtp();

        $page = $this->withSession([SellerRegistrationFlow::SESSION_VERIFICATION => $mobile])
            ->get(route('seller.registration_mobile'));

        $page->assertOk();
        $page->assertSee(route('seller.registration_phone_resend'), false);
        $page->assertSee(route('seller.registration_phone_verify'), false);
        // تایمر از دیتابیس (updated_at + ۱۲۰ ثانیه) — غیر صفر چون تازه ارسال شده
        $page->assertSee('ارسال مجدد کد تا', false);
    }

    #[Test]
    #[TestDox('با گم‌شدن سشنِ تأیید (رفت‌وبرگشت به لندینگ)، صفحهٔ کد از پیش‌نویس بازیابی می‌شود و حلقه نمی‌زند')]
    public function otp_page_recovers_from_lost_session(): void
    {
        $this->enableVendor();
        $mobile = $this->sendOtp();
        $draft = SellerRegistration::where('mobile', $mobile)->first();

        // لندینگ سشنِ تأیید را پاک می‌کند (رفتار قدیمی) — پیش‌نویس می‌ماند
        $page = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_mobile'));

        $page->assertOk();
        $page->assertSee($mobile, false);

        // و اگر اصلاً NewSeller نباشد، حلقهٔ ریدایرکت نمی‌سازد (پیش‌نویس پاک و به ثبت‌نام)
        $mobile2 = $this->sendOtp();
        $draft2 = SellerRegistration::where('mobile', $mobile2)->first();
        NewSeller::where('mobile', $mobile2)->delete();

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft2->id])
            ->get(route('seller.registration_mobile'))
            ->assertRedirect(route('seller.registration'));
    }

    /* ================= ۲) گیت ترتیب مراحل ================= */

    #[Test]
    #[TestDox('پریدن به مرحلهٔ جلوتر ممنوع است و به مرحلهٔ فعلی برمی‌گردد؛ برگشت آزاد است')]
    public function forward_steps_are_blocked(): void
    {
        $this->enableVendor();

        // بدون پیش‌نویس: POST وضعیت → خطا با مقصد گام اول
        $this->post(route('seller.registration_type_store'), ['type' => 'real'])
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('redirect', route('seller.registration'));

        // پیش‌نویس در گام otp: GET صفحهٔ اطلاعات تکمیلی → ریدایرکت به ثبت‌نام
        $mobile = $this->sendOtp();
        $draft = SellerRegistration::where('mobile', $mobile)->first();

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_identity'))
            ->assertRedirect(route('seller.registration'));

        // POST کسب‌وکار قبل از تکمیل هویت هم بسته است
        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_business_store'), [])
            ->assertJsonPath('status', 'error');
    }

    #[Test]
    #[TestDox('ورود به صفحهٔ ثبت‌نام با پیش‌نویس نیمه‌کاره، به همان مرحله ریدایرکت می‌شود (رزومه)')]
    public function registration_page_resumes_draft(): void
    {
        $this->enableVendor();

        // جریان تا گام اطلاعات تکمیلی (پیش‌نویس در همان مرحله)
        $draft = $this->flowTo('type');

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration'))
            ->assertRedirect(route('seller.registration_identity'));
    }

    /* ================= ۳) وضعیت و اطلاعات تکمیلی ================= */

    #[Test]
    #[TestDox('انتخاب وضعیت حقوقی ذخیره می‌شود و گام بعد به آن افتخار می‌کند')]
    public function type_choice_is_saved(): void
    {
        $this->enableVendor();
        // جریان تا گام وضعیت (تأیید کد انجام شده)
        $draft = $this->flowTo('type');
        $this->assertSame('identity', $draft->step); // flowTo تا type یعنی انتخاب هم انجام شده

        // بازگشت آزاد به گام وضعیت (ویرایش مجدد)
        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_type'))
            ->assertOk();

        $this->post(route('seller.registration_type_store'), ['type' => 'legal'])
            ->assertJsonPath('redirect', route('seller.registration_identity'));

        $draft->refresh();
        $this->assertSame('legal', $draft->type);
        $this->assertSame('identity', $draft->step);
    }

    #[Test]
    #[TestDox('اطلاعات تکمیلی حقیقی بدون فینوتک ذخیره و ادامه می‌شود (kyc=skip)')]
    public function identity_real_saves_without_kyc(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('type');

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_identity_store'), [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'مریم',
                'last_name' => 'تستی',
                'gender' => 'female',
                'birth_year' => '1365',
                'birth_month' => '3',
                'birth_day' => '10',
                'ownership_confirm' => '1',
                'info_correct' => '1',
            ])->assertJsonPath('redirect', route('seller.registration_business_details'));

        $draft->refresh();
        $this->assertSame('business', $draft->step);
        $this->assertSame('skip', $draft->kyc_status);
    }

    #[Test]
    #[TestDox('چک‌باکس «موبایل و کد ملی متعلق به یک نفر» اجباری است')]
    public function ownership_checkbox_is_required(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('type');

        $response = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_identity_store'), [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'مریم',
                'last_name' => 'تستی',
                'gender' => 'female',
                'birth_year' => '1365',
                'birth_month' => '3',
                'birth_day' => '10',
                'info_correct' => '1',
            ]);

        $response->assertSessionHasErrors('ownership_confirm');
        $this->assertSame('identity', $draft->refresh()->step);
    }

    #[Test]
    #[TestDox('فینوتک روشن + تطابق موبایل/کدملی ⇒ kyc=verified و ادامه')]
    public function kyc_match_verifies_and_continues(): void
    {
        $this->enableVendor();
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, '1');
        option_update('finnotech_client_id', 'test-client');
        option_update('finnotech_client_secret', 'test-secret');
        Cache::flush();

        Http::fake([
            '*/dev/v2/oauth2/token' => Http::response(['value' => 'fake-token', 'expiresIn' => 3600]),
            '*/shahkar/mobileVerify*' => Http::response([
                'result' => ['type' => 'Successful'],
                'value' => ['isValid' => true],
            ]),
        ]);

        $draft = $this->flowTo('type');

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_identity_store'), [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'علی',
                'last_name' => 'مطابق',
                'gender' => 'male',
                'birth_year' => '1370',
                'birth_month' => '1',
                'birth_day' => '1',
                'ownership_confirm' => '1',
                'info_correct' => '1',
            ])->assertJsonPath('status', 'success');

        $draft->refresh();
        $this->assertSame('business', $draft->step);
        $this->assertSame('verified', $draft->kyc_status);
        $this->assertNotNull($draft->kyc_checked_at);
    }

    #[Test]
    #[TestDox('فینوتک روشن + مغایرت موبایل/کدملی ⇒ رد شدن با پیام و توقف در همان گام')]
    public function kyc_mismatch_blocks_next_step(): void
    {
        $this->enableVendor();
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, '1');
        option_update('finnotech_client_id', 'test-client');
        option_update('finnotech_client_secret', 'test-secret');
        Cache::flush();

        Http::fake([
            '*/dev/v2/oauth2/token' => Http::response(['value' => 'fake-token', 'expiresIn' => 3600]),
            '*/shahkar/mobileVerify*' => Http::response([
                'result' => ['type' => 'Successful'],
                'value' => ['isValid' => false],
            ]),
        ]);

        $draft = $this->flowTo('type');

        $response = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_identity_store'), [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'علی',
                'last_name' => 'مغایر',
                'gender' => 'male',
                'birth_year' => '1370',
                'birth_month' => '1',
                'birth_day' => '1',
                'ownership_confirm' => '1',
                'info_correct' => '1',
            ]);

        $response->assertOk()->assertJsonPath('status', 'error');
        $this->assertStringContainsString('متعلق به یک نفر نیستند', (string) $response->json('message'));

        $draft->refresh();
        $this->assertSame('identity', $draft->step);
    }

    #[Test]
    #[TestDox('خطای اتصال فینوتک ثبت‌نام را نمی‌بندد (kyc=unavailable + هشدار)')]
    public function kyc_outage_does_not_block_registration(): void
    {
        $this->enableVendor();
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, '1');
        option_update('finnotech_client_id', 'test-client');
        option_update('finnotech_client_secret', 'test-secret');
        Cache::flush();

        Http::fake([
            '*/dev/v2/oauth2/token' => Http::response(['error' => 'unavailable'], 500),
        ]);

        $draft = $this->flowTo('type');

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_identity_store'), [
                'national_code' => $this->validNationalCode(),
                'first_name' => 'علی',
                'last_name' => 'قطعی',
                'gender' => 'male',
                'birth_year' => '1370',
                'birth_month' => '1',
                'birth_day' => '1',
                'ownership_confirm' => '1',
                'info_correct' => '1',
            ])->assertJsonPath('status', 'success');

        $draft->refresh();
        $this->assertSame('business', $draft->step);
        $this->assertSame('unavailable', $draft->kyc_status);
    }

    /* ================= ۴) گام کسب‌وکار (ساخت حساب) ================= */

    #[Test]
    #[TestDox('ثبت کسب‌وکار حساب Seller + SellerInfo + Wallet می‌سازد و NewSeller حذف می‌شود')]
    public function business_store_creates_seller_account(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('identity', 'legal');

        $response = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->post(route('seller.registration_business_store'), [
                'email' => 'biz-'.Str::random(6).'@test.ir',
                'password' => 'secret12345',
                'state_id' => 1,
                'city_id' => 1,
                'address' => 'تهران خیابان تست پلاک ۵',
                'post_code' => '1234567890',
                'phone' => '02112345678',
                'business_name' => 'فروشگاه تست فاز '.$this->faNumber(),
                'shaba_number' => 'IR120570028180010101010101',
                'main_supply_category_id' => 1,
                'number_of_products' => '50',
                'econtract' => '1',
                'lat_and_long' => '',
            ]);

        $response->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect', route('seller.registration_documents'));

        $seller = Seller::where('mobile', $draft->mobile)->first();
        $this->assertNotNull($seller);
        $this->assertTrue(Hash::check('secret12345', $seller->password));
        $this->assertSame('YES', $seller->mobile_verification);

        $info = SellerInfo::where('seller_id', $seller->id)->first();
        $this->assertNotNull($info);
        $this->assertSame('business', $info->private_business);
        $this->assertSame('شرکت تست فاز', $info->company_name);
        $this->assertMatchesRegularExpression('/^فروشگاه تست فاز [۰-۹]+$/', (string) $info->business_name);
        $this->assertMatchesRegularExpression('/^biz-[A-Za-z0-9]{6}@test\.ir$/', (string) $seller->email);

        $this->assertTrue(Wallet::where('seller_id', $seller->id)->exists());
        $this->assertFalse(NewSeller::where('mobile', $draft->mobile)->exists());

        $draft->refresh();
        $this->assertSame('documents', $draft->step);
        $this->assertNotNull($draft->email);
    }

    #[Test]
    #[TestDox('پس از ساخت حساب، ورود مجدد به گام کسب‌وکار به مدارک ریدایرکت می‌شود')]
    public function business_page_redirects_after_account_created(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('documents', 'real');

        $seller = Seller::where('mobile', $draft->mobile)->first();
        $this->assertNotNull($seller, 'جریان تا مدارک باید حساب ساخته باشد');

        $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_business_details'))
            ->assertRedirect(route('seller.registration_documents'));
    }

    /* ================= ۵) تنظیمات برنامه‌نویس + لندینگ ================= */

    #[Test]
    #[TestDox('سوییچ احراز هویت فینوتک و کلیدها از تب چندفروشندگی ذخیره می‌شوند')]
    public function developer_saves_kyc_settings(): void
    {
        $this->enableVendor();
        $this->actingCreator();

        Livewire::test(Developer::class)
            ->set('vendorKycMobileVerify', true)
            ->set('vendorFinnotechClientId', 'cid-110')
            ->set('vendorFinnotechClientSecret', 'sec-110')
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('1', option(MultiVendorSettings::KEY_KYC_MOBILE, '0'));
        $this->assertSame('cid-110', option('finnotech_client_id'));
        $this->assertSame('sec-110', option('finnotech_client_secret'));
        $this->assertTrue(MultiVendorSettings::kycMobileVerifyEnabled());
        $this->assertTrue(Finnotech::ready());
    }

    #[Test]
    #[TestDox('لندینگ: ثبت‌نام سریع فقط موبایل است و مستقیم به اندپوینت ارسال کد وصل است')]
    public function landing_quick_form_is_phone_only(): void
    {
        $this->enableVendor();

        $page = $this->get(route('seller.index'));
        $page->assertOk();
        $page->assertSee(route('seller.registration_phone_send'), false);
        $page->assertDontSee('sp-hero-shop', false); // فیلد نام فروشگاه حذف شده
        $page->assertDontSee('?mobile=', false);
    }

    #[Test]
    #[TestDox('پیش‌فرض‌های محتوای لندینگ (چرا ما/مزایا/سوالات) seed شده‌اند و رندر می‌شوند')]
    public function landing_defaults_are_seeded_and_rendered(): void
    {
        $this->enableVendor();

        $this->assertTrue(\DB::table('seller_heroes')->count() >= 6);
        $this->assertTrue(\DB::table('seller_commissions')->count() >= 6);
        $this->assertTrue(\DB::table('seller_questions')->count() >= 6);

        $page = $this->get(route('seller.index'));
        $page->assertOk();
        $page->assertSee('چرا فروش در اینجا؟', false);
        $page->assertSee('بازدید و مشتریان آماده', false);
        $page->assertSee('مزایای فروشندگان', false);
        $page->assertSee('سوالات متداول', false);
        $page->assertSee('تسویهٔ شفاف و سریع', false);
        // آیکون‌های SVG درون‌خطی رندر می‌شوند
        $page->assertSee('<svg xmlns="http://www.w3.org/2000/svg"', false);
    }

    #[Test]
    #[TestDox('صفحهٔ وضعیت فروشنده (کارت‌های حقیقی/حقوقی) رندر می‌شود')]
    public function seller_type_page_renders(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('type');

        $page = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_type'));

        $page->assertOk();
        $page->assertSee('فروشندهٔ حقیقی هستم', false);
        $page->assertSee('فروشندهٔ حقوقی هستم', false);
        $page->assertSee(route('seller.registration_type_store'), false);
    }

    #[Test]
    #[TestDox('صفحهٔ اطلاعات تکمیلی هشدار «موبایل و کد ملی متعلق به یک نفر» را نشان می‌دهد')]
    public function identity_page_shows_ownership_warning(): void
    {
        $this->enableVendor();
        $draft = $this->flowTo('identity');

        $page = $this->withSession([SellerRegistrationFlow::SESSION_DRAFT => $draft->id])
            ->get(route('seller.registration_identity'));

        $page->assertOk();
        $page->assertSee('متعلق به یک نفر باشد', false);
        $page->assertSee(route('seller.registration_identity_store'), false);
        $page->assertSee('name="national_code"', false);
    }
}
