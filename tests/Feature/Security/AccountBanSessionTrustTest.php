<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Admins\AdminIndex;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Profile;
use App\Livewire\Admin\System\SessionIndex;
use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\BlockedDevice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * تست‌های فاز ۷۲:
 *  - بن اکانت (فقط سازنده) + خروج همهٔ نشست‌ها + پیام مسدودی در همان صفحهٔ ورود پنل جدید
 *  - محافظت ۴۸ ساعتهٔ «سیستم اصلی» در برابر بن/خروج از دستگاه دیگر
 *  - خروج دومرحله‌ای فردی (پروفایل) + ورود با شماره موبایل
 *  - جداسازی فیلد شماره همراه از نام کاربری در صفحهٔ مدیران
 */
class AccountBanSessionTrustTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        option_update('security_captcha_enabled', 'off');
    }

    private function newAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'first_name' => 'تست',
            'last_name'  => 'مدیر',
            'username'   => 'admin-'.random_int(10000, 99999),
            'email'      => 'admin-'.random_int(10000, 99999).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'admin',
            'status'     => 'ACTIVE',
        ], $attributes));
    }

    private function newCreator(array $attributes = []): Admin
    {
        return $this->newAdmin(array_merge([
            'level'    => 'creator',
            'username' => 'creator-'.random_int(10000, 99999),
        ], $attributes));
    }

    /** گیت‌های پرمیشن در کنسول ثبت نمی‌شوند — مثل creator واقعی همه‌چیز مجاز */
    private function allowAdminGates(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user instanceof Admin ? true : null;
        });
    }

    private function sessionRow(Admin $admin, array $attributes = []): AdminSession
    {
        return AdminSession::create(array_merge([
            'admin_id'           => $admin->id,
            'session_id'         => 'sess-'.Str::random(24),
            'device_fingerprint' => 'fp-'.Str::random(12),
            'device_name'        => 'دستگاه تست',
            'device_type'        => 'desktop',
            'browser'            => 'Chrome',
            'platform'           => 'Windows',
            'ip_address'         => '127.0.0.1',
            'user_agent'         => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
            'last_activity'      => now(),
            'is_active'          => true,
        ], $attributes));
    }

    /* =====================================================================
     *  ۱) بن اکانت — فقط سازنده
     * ===================================================================== */

    #[Test]
    public function creator_can_ban_account_and_all_sessions_are_killed(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();
        $manager = $this->newAdmin();

        $targetSession = $this->sessionRow($manager);
        $this->sessionRow($manager); // نشست دوم همان مدیر

        $this->actingAs($creator, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $targetSession->id)
            ->set('blockType', 'account')
            ->set('blockReason', 'تخلف امنیتی')
            ->call('blockSession')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        // رکورد بن اکانت ثبت شده است
        $this->assertDatabaseHas('blocked_devices', [
            'admin_id'     => $manager->id,
            'block_type'   => 'account',
            'reason'       => 'تخلف امنیتی',
            'is_permanent' => true,
        ]);

        // همهٔ نشست‌های مدیر خروج شده‌اند
        $this->assertSame(0, AdminSession::where('admin_id', $manager->id)->count());
    }

    #[Test]
    public function non_creator_cannot_use_account_ban(): void
    {
        $this->allowAdminGates();

        $managerA = $this->newAdmin();
        $managerB = $this->newAdmin();

        $targetSession = $this->sessionRow($managerB);

        $this->actingAs($managerA, 'adminPanel');

        try {
            Livewire::test(SessionIndex::class)
                ->call('openBlock', $targetSession->id)
                ->set('blockType', 'account')
                ->call('blockSession');

            $this->fail('بن اکانت توسط غیرسازنده باید رد شود (403).');
        } catch (\Throwable) {
            // abort(403) میدل‌ور اکشن — رفتار مورد انتظار
        }

        $this->assertDatabaseMissing('blocked_devices', [
            'admin_id'   => $managerB->id,
            'block_type' => 'account',
        ]);

        // نشست هدف همچنان سر جاست
        $this->assertDatabaseHas('admin_sessions', ['id' => $targetSession->id]);
    }

    #[Test]
    public function creator_cannot_ban_creator_account(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();
        $otherCreator = $this->newCreator();

        $targetSession = $this->sessionRow($otherCreator);

        $this->actingAs($creator, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $targetSession->id)
            ->set('blockType', 'account')
            ->call('blockSession')
            ->assertDispatched('toast', type: 'error');

        $this->assertDatabaseMissing('blocked_devices', [
            'admin_id'   => $otherCreator->id,
            'block_type' => 'account',
        ]);
    }

    /* =====================================================================
     *  ۲) پیام مسدودی در همان صفحهٔ ورود پنل جدید (نه پنل قدیم)
     * ===================================================================== */

    #[Test]
    public function banned_admin_gets_block_message_on_new_login_page(): void
    {
        $manager = $this->newAdmin();

        BlockedDevice::create([
            'admin_id'     => $manager->id,
            'block_type'   => 'account',
            'reason'       => 'تخلف',
            'is_permanent' => true,
        ]);

        $component = Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $manager->username)
            ->set('password', 'secret1234')
            ->call('authenticate');

        $errors = $component->errors();

        $this->assertTrue($errors->has('username'), 'پیام مسدودی باید در فرم ورود نمایش داده شود');
        $this->assertStringContainsString('مسدود', (string) $errors->first('username'));

        // کاربر مهمان می‌ماند — پیام در همین صفحهٔ ورود نمایش داده می‌شود (بدون ریدایرکت به پنل قدیم)
        $this->assertGuest('adminPanel');
    }

    #[Test]
    public function blocked_middleware_sends_admin_to_new_login_page(): void
    {
        $this->allowAdminGates();

        $manager = $this->newAdmin();

        // بلاک آیپی — مقصد ریدایرکت باید صفحهٔ ورود پنل جدید باشد (نه admin-old)
        BlockedDevice::create([
            'admin_id'     => $manager->id,
            'block_type'   => 'ip',
            'ip_address'   => '127.0.0.1',
            'reason'       => 'آیپی مشکوک',
            'is_permanent' => true,
        ]);

        $this->actingAs($manager, 'adminPanel')
            ->get(route('np.profile'))
            ->assertRedirect(route('np.login'));
    }

    /* =====================================================================
     *  ۳) محافظت ۴۸ ساعتهٔ «سیستم اصلی»
     * ===================================================================== */

    #[Test]
    public function primary_session_cannot_be_kicked_from_another_device_within_48h(): void
    {
        $this->allowAdminGates();

        $manager = $this->newAdmin();

        // «سیستم اصلی» مدیر — دستگاه اول
        $primary = $this->sessionRow($manager, [
            'session_id'         => 'primary-'.Str::random(20),
            'device_fingerprint' => 'fp-primary-device',
            'is_primary'         => true,
            'primary_until'      => now()->addHours(48),
        ]);

        $this->actingAs($manager, 'adminPanel');

        // بیننده از «دستگاه دیگر» وارد شده (اثر انگشت متفاوت — مثل هکر با رمز دزدیده‌شده)
        $this->sessionRow($manager, [
            'session_id'         => session()->getId(),
            'device_fingerprint' => 'fp-hijacker-device',
        ]);

        Livewire::test(SessionIndex::class)
            ->call('confirmDelete', $primary->id)
            ->call('destroySession')
            ->assertDispatched('toast', type: 'error');

        // نشست اصلی پابرجاست
        $this->assertDatabaseHas('admin_sessions', ['id' => $primary->id]);

        // بلاک هم نباید ممکن باشد
        Livewire::test(SessionIndex::class)
            ->call('openBlock', $primary->id)
            ->set('blockType', 'session')
            ->call('blockSession');

        $this->assertDatabaseMissing('blocked_devices', [
            'session_id' => $primary->session_id,
        ]);
    }

    #[Test]
    public function primary_session_is_manageable_from_the_same_device(): void
    {
        $this->allowAdminGates();

        $manager = $this->newAdmin();

        $primary = $this->sessionRow($manager, [
            'session_id'         => 'primary-'.Str::random(20),
            'device_fingerprint' => 'fp-shared-device',
            'is_primary'         => true,
            'primary_until'      => now()->addHours(48),
        ]);

        // سازنده روی همان دستگاه (همان آیپی/مرورگر/سیستم) — دکمه‌ها باید فعال باشند
        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        $this->sessionRow($creator, [
            'session_id'         => session()->getId(),
            'device_fingerprint' => 'fp-shared-device',
        ]);

        Livewire::test(SessionIndex::class)
            ->call('confirmDelete', $primary->id)
            ->call('destroySession')
            ->assertDispatched('toast', type: 'success');

        $this->assertDatabaseMissing('admin_sessions', ['id' => $primary->id]);
    }

    #[Test]
    public function expired_primary_protection_allows_kick_from_other_device(): void
    {
        $this->allowAdminGates();

        $manager = $this->newAdmin();

        $primary = $this->sessionRow($manager, [
            'session_id'         => 'primary-'.Str::random(20),
            'device_fingerprint' => 'fp-primary-device',
            'is_primary'         => true,
            'primary_until'      => now()->subHour(), // محافظت منقضی
        ]);

        $this->actingAs($manager, 'adminPanel');

        $this->sessionRow($manager, [
            'session_id'         => session()->getId(),
            'device_fingerprint' => 'fp-hijacker-device',
        ]);

        Livewire::test(SessionIndex::class)
            ->call('confirmDelete', $primary->id)
            ->call('destroySession')
            ->assertDispatched('toast', type: 'success');

        $this->assertDatabaseMissing('admin_sessions', ['id' => $primary->id]);
    }

    #[Test]
    public function first_login_marks_primary_session_for_48h(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();

        $this->actingAs($creator, 'adminPanel')
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
            ->get(route('np.profile'))
            ->assertOk();

        $row = AdminSession::where('admin_id', $creator->id)->first();

        $this->assertNotNull($row, 'نشست مدیر باید ثبت شده باشد');
        $this->assertTrue((bool) $row->is_primary, 'اولین ورود باید «سیستم اصلی» شود');
        $this->assertNotNull($row->primary_until);
        $this->assertTrue($row->primary_until->isFuture());
    }

    /* =====================================================================
     *  ۴) 2FA فردی + ورود با شماره موبایل + پیام دقیق کانال ایمیل
     * ===================================================================== */

    #[Test]
    public function per_admin_two_factor_opt_out_skips_challenge(): void
    {
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');

        $optedOut = $this->newCreator(['two_factor_disabled' => true]);

        // مدیرِ خاموش‌کننده: ورود مستقیم بدون چالش
        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $optedOut->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($optedOut, 'adminPanel');
    }

    #[Test]
    public function two_factor_still_applies_to_admins_who_did_not_opt_out(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');

        $optedIn = $this->newCreator(['two_factor_disabled' => false]);

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $optedIn->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertRedirect(route('np.two-factor.challenge'));

        $this->assertGuest('adminPanel');
    }

    #[Test]
    public function email_channel_failure_shows_email_specific_message_not_mobile(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');
        option_update('admin_mobile_number', '');

        $noEmail = $this->newCreator(['email' => null]);

        $component = Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $noEmail->username)
            ->set('password', 'secret1234')
            ->call('authenticate');

        $errors = $component->errors();

        $this->assertTrue($errors->has('username'));

        $message = (string) $errors->first('username');

        // پیام جدید باید دربارهٔ ایمیل باشد و از شماره/تنظیمات پیامک حرف نزند
        $this->assertStringContainsString('ایمیل', $message);
        $this->assertStringNotContainsString('admin_mobile_number', $message);
    }

    #[Test]
    public function admin_can_login_with_mobile_number(): void
    {
        option_update('admin_2fa_enabled', 'off');

        $admin = $this->newAdmin(['mobile' => '09121234567']);

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', '09121234567')
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($admin, 'adminPanel');
    }

    /* =====================================================================
     *  ۵) جداسازی شماره همراه از نام کاربری + 2FA در پروفایل
     * ===================================================================== */

    #[Test]
    public function managers_form_saves_mobile_separate_from_username(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(AdminIndex::class)
            ->call('openCreateModal')
            ->set('firstName', 'مدیر')
            ->set('lastName', 'تستی')
            ->set('username', 'manager-user-'.random_int(1000, 9999))
            ->set('mobile', '09121112233')
            ->set('password', 'secret1234')
            ->set('passwordConfirmation', 'secret1234')
            ->call('save')
            ->assertHasNoErrors();

        $admin = Admin::where('mobile', '09121112233')->first();

        $this->assertNotNull($admin);
        $this->assertStringStartsWith('manager-user-', $admin->username);
    }

    #[Test]
    public function profile_page_toggles_own_two_factor_and_saves_mobile(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(Profile::class)
            ->set('mobile', '09123334455')
            ->call('save')
            ->assertHasNoErrors()
            ->call('toggleTwoFactor', true)
            ->assertDispatched('toast');

        $creator->refresh();

        $this->assertSame('09123334455', $creator->mobile);
        $this->assertTrue((bool) $creator->two_factor_disabled);

        // خاموشی فردی ⇒ ورود بدون چالش (2FA سراسری روشن است)
        option_update('admin_2fa_enabled', 'on');
        option_update('admin_2fa_channel', 'email');

        Livewire::test(Login::class)
            ->set('renderedAt', (string) now()->subSeconds(10)->getTimestamp())
            ->set('username', $creator->username)
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($creator, 'adminPanel');
    }

    /* =====================================================================
     *  ۶) گزینهٔ «بن اکانت» فقط برای سازنده در مودال بلاک
     * ===================================================================== */

    #[Test]
    public function account_ban_option_is_visible_only_to_creator(): void
    {
        $this->allowAdminGates();

        $creator = $this->newCreator();
        $manager = $this->newAdmin();
        $managerSession = $this->sessionRow($manager);

        // سازنده: مودال بلاک نشست مدیر — گزینهٔ بن اکانت باید دیده شود
        $this->actingAs($creator, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $managerSession->id)
            ->assertSee('بن اکانت');

        // مدیر عادی: مودال بلاک نشست دیگرش — بدون گزینهٔ بن اکانت
        $this->actingAs($manager, 'adminPanel');
        $ownSession = $this->sessionRow($manager);

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $ownSession->id)
            ->assertDontSee('بن اکانت');
    }

    /* =====================================================================
     *  ۷) فاز ۷۳ — مدت بلاک: پیش‌فرض ۱۲ ساعت و مدت سفارشی (مقدار + واحد)
     * ===================================================================== */

    #[Test]
    public function block_supports_twelve_hours_preset(): void
    {
        $this->allowAdminGates();

        $blocker = $this->newAdmin();
        $target = $this->newAdmin();
        $session = $this->sessionRow($target);

        $this->actingAs($blocker, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $session->id)
            ->set('blockType', 'ip')
            ->set('blockDuration', '12hours')
            ->call('blockSession')
            ->assertHasNoErrors();

        $record = BlockedDevice::where('block_type', 'ip')->latest('id')->first();

        $this->assertNotNull($record);
        $this->assertFalse((bool) $record->is_permanent);
        $this->assertNotNull($record->blocked_until);
        $this->assertTrue(
            $record->blocked_until->between(now()->addHours(12)->subMinutes(5), now()->addHours(12)->addMinutes(5)),
            'انقضا باید ۱۲ ساعت بعد باشد'
        );
    }

    #[Test]
    public function block_supports_custom_duration_with_amount_and_unit(): void
    {
        $this->allowAdminGates();

        $blocker = $this->newAdmin();
        $target = $this->newAdmin();
        $session = $this->sessionRow($target);

        $this->actingAs($blocker, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $session->id)
            ->set('blockType', 'ip')
            ->set('blockDuration', 'custom')
            ->set('blockCustomAmount', '3')
            ->set('blockCustomUnit', 'days')
            ->call('blockSession')
            ->assertHasNoErrors();

        $record = BlockedDevice::where('block_type', 'ip')->latest('id')->first();

        $this->assertNotNull($record);
        $this->assertFalse((bool) $record->is_permanent);
        $this->assertNotNull($record->blocked_until);
        $this->assertTrue(
            $record->blocked_until->between(now()->addDays(3)->subMinutes(5), now()->addDays(3)->addMinutes(5)),
            'انقضای سفارشی باید ۳ روز بعد باشد'
        );

        // واحد ساعت هم باید درست محاسبه شود
        $secondSession = $this->sessionRow($target);

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $secondSession->id)
            ->set('blockType', 'ip')
            ->set('blockDuration', 'custom')
            ->set('blockCustomAmount', '6')
            ->set('blockCustomUnit', 'hours')
            ->call('blockSession')
            ->assertHasNoErrors();

        $hoursRecord = BlockedDevice::where('block_type', 'ip')->latest('id')->first();

        $this->assertTrue(
            $hoursRecord->blocked_until->between(now()->addHours(6)->subMinutes(5), now()->addHours(6)->addMinutes(5)),
            'انقضای سفارشی باید ۶ ساعت بعد باشد'
        );
    }

    #[Test]
    public function custom_duration_requires_amount_and_valid_unit(): void
    {
        $this->allowAdminGates();

        $blocker = $this->newAdmin();
        $target = $this->newAdmin();
        $session = $this->sessionRow($target);

        $this->actingAs($blocker, 'adminPanel');

        // بدون مقدار → خطای اعتبارسنجی و بدون رکورد
        Livewire::test(SessionIndex::class)
            ->call('openBlock', $session->id)
            ->set('blockType', 'ip')
            ->set('blockDuration', 'custom')
            ->set('blockCustomAmount', '')
            ->call('blockSession')
            ->assertHasErrors(['blockCustomAmount']);

        $this->assertSame(0, BlockedDevice::where('block_type', 'ip')->count());

        // مقدار صفر → خطا
        Livewire::test(SessionIndex::class)
            ->call('openBlock', $session->id)
            ->set('blockType', 'ip')
            ->set('blockDuration', 'custom')
            ->set('blockCustomAmount', '0')
            ->call('blockSession')
            ->assertHasErrors(['blockCustomAmount']);

        $this->assertSame(0, BlockedDevice::where('block_type', 'ip')->count());
    }

    #[Test]
    public function block_modal_shows_new_duration_tiles(): void
    {
        $this->allowAdminGates();

        $blocker = $this->newAdmin();
        $target = $this->newAdmin();
        $session = $this->sessionRow($target);

        $this->actingAs($blocker, 'adminPanel');

        Livewire::test(SessionIndex::class)
            ->call('openBlock', $session->id)
            ->assertSee('۱۲ ساعت')
            ->assertSee('سفارشی');
    }
}
