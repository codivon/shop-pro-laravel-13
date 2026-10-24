<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\BotTrap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۷۱ — کلید اصلی تلهٔ ربات (تنظیمات برنامه‌نویس) + صفحهٔ زیبای مسدودسازی
 *
 * گزارش کاربر:
 *  ۱) «رکورد bot_traps را حذف کردم ولی Forbidden برطرف نشد» → ریشه: کش ۱۰ دقیقه‌ای لیست آی‌پی‌ها
 *  ۲) «صفحهٔ Forbidden باید ظاهر زیبا داشته باشد و بگوید بلاک شده است»
 *  ۳) «آیا می‌شود این مورد را در تنظیمات برنامه‌نویس غیرفعال کرد؟ اگر نمی‌شود اضافه‌اش کن»
 *
 * پوشش تست:
 *  - کلید خاموش ⇒ حتی مسدودی‌های موجود هم بی‌اثر (رفع فوری قفل)
 *  - کلید خاموش ⇒ هیچ تله‌گذاری جدیدی ثبت نمی‌شود (ولی مسیرهای طعمه همچنان ۴۰۳)
 *  - صفحهٔ مسدودی فارسی و زیبا (عنوان + انقضای خودکار + نمایش آی‌پی)
 *  - ذخیرهٔ کلید از تنظیمات برنامه‌نویس + رفع مسدودی تک‌آی‌پی و همه از همان‌جا (با پاک‌کردن کش)
 */
class BotTrapSettingsTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    protected function tearDown(): void
    {
        BotTrap::query()->delete();
        Cache::forget(BotTrap::CACHE_KEY);
        parent::tearDown();
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'تنظیمات ربات‌گیر',
            'username'   => 'trapset-'.random_int(10000, 99999),
            'email'      => 'trapset-'.random_int(10000, 99999).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /** درج مستقیم تله (بدون پاس گارد کلید) + پاک‌کردن کش — شبیه رکورد واقعی مسدود */
    private function seedTrap(string $ip = '127.0.0.1'): void
    {
        BotTrap::query()->create([
            'ip_address'  => $ip,
            'user_agent'  => 'Scanner/1.0',
            'trap_path'   => '/wp-admin',
            'hit_count'   => 3,
            'last_hit_at' => now(),
        ]);
        BotTrap::flushCache();
    }

    /* =====================================================================
     *  ۱) کلید اصلی — مسدودسازی
     * ===================================================================== */

    #[Test]
    public function master_switch_off_never_blocks_anyone_even_with_existing_traps(): void
    {
        $this->seedTrap();
        $this->assertSame(403, $this->get('/')->status(), 'با کلید روشن، آی‌پی مسدود ۴۰۳ می‌گیرد');

        option_update('security_bot_trap_enabled', 'off');

        $this->assertNotSame(
            403,
            $this->get('/')->status(),
            'با کلید خاموش، حتی مسدودی‌های موجود هم بی‌اثر می‌شوند (رفع فوری قفل بدون دست‌کاری دیتابیس)'
        );
    }

    #[Test]
    public function master_switch_off_stops_recording_new_traps_but_decoys_stay_403(): void
    {
        option_update('security_bot_trap_enabled', 'off');

        $this->assertSame(403, $this->get('/wp-login.php')->status(), 'مسیرهای طعمه همیشه ۴۰۳ می‌مانند');
        $this->assertSame(0, BotTrap::query()->count(), 'با کلید خاموش، هیچ آی‌پی‌ای در تله ثبت نمی‌شود');
    }

    #[Test]
    public function master_switch_on_blocks_globally_after_decoy_hit(): void
    {
        $this->assertNotSame(403, $this->get('/')->status(), 'پیش‌فرض: کلید روشن است و دسترسی عادی برقرار است');

        $this->get('/wp-admin'); // ضربهٔ تازه به طعمه
        $this->assertTrue(BotTrap::isBlocked('127.0.0.1'), 'تله‌گذاری عادی باید کار کند');

        $this->assertSame(403, $this->get('/')->status(), 'کل سایت برای آی‌پی تازه‌مسدود ۴۰۳ است');
    }

    /* =====================================================================
     *  ۲) صفحهٔ زیبای مسدودسازی (گزارش کاربر)
     * ===================================================================== */

    #[Test]
    public function blocked_visitor_gets_beautiful_persian_block_page(): void
    {
        $this->seedTrap();

        $response = $this->get('/');

        $this->assertSame(403, $response->status());
        $response->assertSee('دسترسی شما مسدود شده است'); // به کاربر بگوید بلاک شده
        $response->assertSee('خودکار');                    // توضیح انقضای خودکار
        $response->assertSee('127.0.0.1', false);          // نمایش آی‌پی مسدودشده
    }

    #[Test]
    public function blocked_api_client_gets_json_403(): void
    {
        $this->seedTrap();

        $response = $this->getJson('/api/some-endpoint');

        $this->assertSame(403, $response->status());
        $response->assertJsonPath('message', 'دسترسی شما به‌صورت موقت مسدود شده است.');
    }

    #[Test]
    public function decoy_path_without_recording_shows_forbidden_page(): void
    {
        option_update('security_bot_trap_enabled', 'off');

        $response = $this->get('/.env');

        $this->assertSame(403, $response->status());
        $response->assertSee('دسترسی به این مسیر مجاز نیست');
        $response->assertDontSee('دسترسی شما مسدود شده است'); // مسدود نشده — فقط مسیر ممنوع است
    }

    /* =====================================================================
     *  ۳) تنظیمات برنامه‌نویس — کلید + رفع مسدودی از UI
     * ===================================================================== */

    #[Test]
    public function developer_can_toggle_bot_trap_from_settings(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        Livewire::test(Developer::class)
            ->assertSet('securityBotTrapEnabled', true)
            ->set('securityBotTrapEnabled', false)
            ->call('saveSecurity')
            ->assertHasNoErrors();

        $this->assertSame('off', option('security_bot_trap_enabled'), 'کلید باید ذخیره شده باشد');
    }

    #[Test]
    public function developer_can_unblock_single_ip_and_all_from_settings(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        BotTrap::catch('203.0.113.5', 'Bot/1.0', '/.env');
        BotTrap::catch('203.0.113.9', 'Bot/2.0', '/wp-admin');
        $this->assertSame(2, BotTrap::query()->count());

        // رفع مسدودی یک آی‌پی — بقیه می‌مانند + کش پاک می‌شود
        $firstId = (int) BotTrap::query()->where('ip_address', '203.0.113.5')->value('id');

        Livewire::test(Developer::class)
            ->call('unblockIp', $firstId)
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame(1, BotTrap::query()->count(), 'فقط همان رکورد حذف می‌شود');
        $this->assertFalse(BotTrap::isBlocked('203.0.113.5'), 'آی‌پی آزادشده نباید در کشِ مسدودی باشد');
        $this->assertTrue(BotTrap::isBlocked('203.0.113.9'), 'بقیهٔ مسدودی‌ها باید برقرار بمانند');

        // رفع مسدودی همه
        Livewire::test(Developer::class)
            ->call('unblockAllIps')
            ->assertHasNoErrors();

        $this->assertSame(0, BotTrap::query()->count());
        $this->assertFalse(BotTrap::isBlocked('203.0.113.9'), 'بعد از رفع مسدودی همه، کش هم باید خالی باشد');
    }

    #[Test]
    public function active_traps_computed_property_lists_recent_traps_only(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        BotTrap::catch('203.0.113.5', 'Bot/1.0', '/.env');
        BotTrap::query()->create([
            'ip_address'  => '203.0.113.99',
            'user_agent'  => 'Old/1.0',
            'trap_path'   => '/wp-login.php',
            'hit_count'   => 1,
            'last_hit_at' => now()->subHours(30), // از سقف ۲۴ ساعت گذشته
        ]);

        $traps = Livewire::test(Developer::class)->instance()->activeTraps;

        $this->assertCount(1, $traps, 'فقط تله‌های ۲۴ ساعت اخیر نمایش داده می‌شوند');
        $this->assertSame('203.0.113.5', $traps[0]['ip_address']);
    }
}
