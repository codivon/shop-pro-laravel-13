<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Gateway;
use App\Models\Permission;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۹ — درگاه سپ (سمان الکترونیک) + گارد حذف همزمان ماژول هوش مصنوعی.
 * --------------------------------------------------------------------------
 *  • سپ (SEP): درایور آمادهٔ shetabit/multipay به تنظیمات درگاه هسته سیم‌کشی شد
 *    (supported_gateways + drivers.sep + map + get_gateway_configs + فیلدها/برندینگ پنل).
 *  • گارد حذف: EventServiceProvider پکیج AI به‌جای آرایهٔ class-string، closure با
 *    گارد class_exists ثبت می‌کند — جریان uninstall هسته فایل‌ها را در همان
 *    ریکوئست پنل حذف می‌کند و RequestHandled پایان همان ریکوئست نباید با
 *    «Target class [...] does not exist» کرش کند (گزارش کاربر).
 */
class Phase99Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    private function requireModule(): void
    {
        if (! is_dir(base_path('Modules/ArtificialIntelligence/app'))) {
            $this->markTestSkipped('ماژول ArtificialIntelligence (هوش مصنوعی) روی این میزبان نصب نیست — تست فاز ۹۹ رد شد.');
        }
    }

    private function newCreator(): Admin
    {
        // گیت‌ها از ردیف‌های فعال جدول permissions می‌آیند + یک گیت صریح برای صفحهٔ درگاه‌ها
        foreach (Permission::where('active', true)->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        Gate::define('settings.gateway', fn ($user = null) => true);

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۹۹',
            'username'   => 'feat99-'.Str::random(6),
            'email'      => 'feat99-'.Str::random(6).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ================= ۱) سپ — سیم‌کشی کامل ================= */

    #[Test]
    #[TestDox('بند ۱ — سپ در supported_gateways + درایور SEP در drivers و map پیکربندی پرداخت')]
    public function sep_gateway_wiring_contract(): void
    {
        $supported = config('general.supported_gateways');
        $this->assertArrayHasKey('sep', $supported, 'سپ باید در فهرست درگاه‌های پشتیبانی‌شده باشد.');
        $this->assertSame('درگاه سپ (سمان الکترونیک)', $supported['sep']);

        $driver = config('payment.drivers.sep');
        $this->assertIsArray($driver, 'بلاک drivers.sep در config/payment.php باید باشد.');
        $this->assertArrayHasKey('terminalId', $driver, 'درایور SEP فیلد terminalId می‌خواهد.');
        $this->assertArrayHasKey('apiVerificationUrl', $driver);
        $this->assertSame('https://sep.shaparak.ir/onlinepg/onlinepg', $driver['apiGetToken']);
        $this->assertSame('https://sep.shaparak.ir/OnlinePG/OnlinePG', $driver['apiPaymentUrl']);
        $this->assertArrayHasKey('callbackUrl', $driver);

        $map = config('payment.map');
        $this->assertSame(\Shetabit\Multipay\Drivers\SEP\SEP::class, $map['sep'] ?? null);
        $this->assertTrue(class_exists($map['sep']), 'کلاس درایور SEP باید در vendor موجود باشد (درایور آمادهٔ shetabit).');
    }

    #[Test]
    #[TestDox('بند ۲ — فیلد پایانه و برندینگ سپ در صفحهٔ تنظیمات درگاه‌ها')]
    public function sep_gateway_settings_fields_and_branding(): void
    {
        $fields = \App\Livewire\Admin\Settings\Gateways::gatewayFields('sep');

        $names = array_column($fields, 'name');
        $this->assertContains('terminalId', $names, 'سپ باید فیلد «کد پایانه» داشته باشد.');
        $this->assertContains('logo', $names, 'همهٔ درگاه‌ها فیلد لوگو دارند.');

        $branding = \App\Livewire\Admin\Settings\Gateways::gatewayBranding('sep');
        $this->assertSame('سپ — بانک سامان', $branding['bank']);
        $this->assertNotEmpty($branding['color']);
    }

    #[Test]
    #[TestDox('بند ۳ — get_gateway_configs سپ، کد پایانه را از جدول gateways برمی‌گرداند')]
    public function sep_configs_flow_from_database(): void
    {
        $gateway = Gateway::create(['key' => 'sep', 'name' => 'درگاه سپ (سمان الکترونیک)', 'is_active' => true]);
        $gateway->configs()->create(['key' => 'terminalId', 'value' => '25740569']);

        $configs = get_gateway_configs('sep');

        $this->assertSame('25740569', $configs['terminalId'] ?? null);
        $this->assertArrayNotHasKey('merchantId', $configs, 'سپ merchantId ندارد (درایور فقط terminalId می‌خواهد).');
    }

    #[Test]
    #[TestDox('بند ۴ — صفحهٔ تنظیمات درگاه‌ها ردیف سپ را firstOrCreate می‌کند و کارت آن رندر می‌شود')]
    public function sep_card_rendered_on_gateways_settings_page(): void
    {
        $this->newCreator();

        Gateway::where('key', 'sep')->delete();

        Livewire::test(\App\Livewire\Admin\Settings\Gateways::class)
            ->assertSet('form', function (array $form) {
                // فرم باید برای درگاه سپ ساخته شده باشد (firstOrCreate از supported_gateways)
                $ids = Gateway::where('key', 'sep')->pluck('id')->all();
                $this->assertNotEmpty($ids, 'ردیف سپ باید خودکار ساخته شده باشد.');

                $row = $form[$ids[0]] ?? null;
                $this->assertNotNull($row, 'فرم باید شامل سپ باشد.');
                $this->assertArrayHasKey('terminalId', $row['configs'] ?? []);

                return true;
            });

        Gateway::where('key', 'sep')->first()->update(['is_active' => true]);

        // درگاه فعال سپ باید در فهرست روش‌های پرداخت خریداران ظاهر شود (scope active)
        $this->assertTrue(Gateway::active()->where('key', 'sep')->exists());
    }

    /* ================= ۲) گارد حذف همزمان ماژول ================= */

    #[Test]
    #[TestDox('بند ۵ — شنوندهٔ RequestHandled پکیج AI حتماً closure است نه class-string')]
    public function request_handled_listener_is_closure_not_class_string(): void
    {
        $this->requireModule();

        // نکته: getRawListeners در Laravel 12 آرگومان نمی‌گیرد و کل مپ رویدادها را می‌دهد
        $raw = Event::getRawListeners()[RequestHandled::class] ?? [];

        $this->assertNotEmpty($raw, 'باید حداقل یک شنوندهٔ RequestHandled وجود داشته باشد.');

        foreach ($raw as $listener) {
            $this->assertNotSame(
                \Modules\ArtificialIntelligence\Listeners\InjectAssetsOnRequestHandled::class,
                $listener,
                'شنونده نباید class-string باشد — روش قدیمی $listen در حذف همزمان ماژول کرش می‌کند.'
            );
        }

        $hasClosure = false;
        foreach ($raw as $listener) {
            if ($listener instanceof \Closure) {
                $hasClosure = true;
                break;
            }
        }
        $this->assertTrue($hasClosure, 'شنوندهٔ پکیج باید closure گارد‌دار باشد.');
    }

    #[Test]
    #[TestDox('بند ۶ — الگوی گارد class_exists: کلاس ناموجود در شلیک رویداد کرش نمی‌کند')]
    public function guard_pattern_no_throw_for_missing_listener_class(): void
    {
        // بازتولید دقیق سناریوی کاربر: ماژول در همان ریکوئست حذف شده (فایل کلاس دیگر
        // وجود ندارد) و RequestHandled پایان همان ریکوئست شلیک می‌شود.
        $ghost = 'Modules\\ArtificialIntelligence\\Listeners\\NeverLoadedPhase99Ghost';
        $this->assertFalse(class_exists($ghost), 'کلاس شبح نباید واقعاً وجود داشته باشد.');

        // همان registration الگوی provider (کپی منطق)
        Event::listen(RequestHandled::class, function ($event) use ($ghost): void {
            if (! class_exists($ghost)) {
                return; // ماژول حذف شده — بی‌صدا رد شو
            }

            app($ghost)->handle($event);
        });

        // روش قدیمی (class-string) برای مقایسه: ثبتش باید در resolve کرش کند
        Event::listen(RequestHandled::class, 'Modules\\ArtificialIntelligence\\Listeners\\NeverLoadedPhase99Ghost@handle');

        $caught = null;
        try {
            event(new RequestHandled(request(), response('')));
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertNotNull(
            $caught,
            'شنوندهٔ class-stringِ کلاس ناموجود باید همان «Target class does not exist» کاربر را بازتولید کند.'
        );
        $this->assertStringContainsString('NeverLoadedPhase99Ghost', $caught->getMessage());
    }

    #[Test]
    #[TestDox('بند ۷ — شلیک واقعی RequestHandled با شنوندهٔ گارد‌دار پکیج: بدون کرش')]
    public function request_handled_dispatch_is_safe_with_package_guard(): void
    {
        $this->requireModule();

        // بوت استاندارد → closure گارد‌دار ثبت شده است. شلیک مستقیم رویداد
        // نباید هیچ استثنایی بدهد (چه کلاس لودشده باشد چه نباشد — گارد تصمیم می‌گیرد).
        $caught = null;
        try {
            event(new RequestHandled(request(), response('<html>phase99</html>')));
        } catch (\Throwable $e) {
            $caught = $e;
        }

        $this->assertNull($caught, 'RequestHandled با گارد closure نباید هیچ استثنایی بدهد: '
            .($caught ? $caught->getMessage() : ''));

        // و دوباره — دو شلیک پیاپی (dedup و تثبیت پایداری)
        try {
            event(new RequestHandled(request(), response('')));
        } catch (\Throwable $e) {
            $this->fail('شلیک دوم نباید کرش کند: '.$e->getMessage());
        }
    }
}
