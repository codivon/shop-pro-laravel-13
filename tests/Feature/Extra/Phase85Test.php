<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\InstalledModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\DigiPay\Services\LicenseGuard;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۵ — بازخورد کاربر دربارهٔ پکیج‌ها:
 *
 *  ۱) بازطراحی تنظیمات «درگاه‌های پرداخت و اقساطی»: دیجی‌پی و اسنپ‌پی دو سکشن
 *     کاملاً مستقل — هر کدام سوییچ فعال/غیرفعال و تنظیمات اختصاصی خودش
 *  ۲) تست اتصال مستقل اسنپ‌پی (endpoint جدید + گارد فعال‌بودن)
 *  ۳) ریشه‌یابی «پکیج‌های جدید بعد از نصب در لیست منوها نمی‌آیند»:
 *     • ثبت منو دیگر به گیت چک آنلاین لایسنس وابسته نیست (منو = نصب‌شده + فعال)
 *     • LicenseGuard رکورد ماژول را با تطبیق مقاوم (slug دقیق → نام کانونی
 *       → like) پیدا می‌کند تا مغایرت نام‌گذاری بازار گارد را ناکام نکند
 *  ۴) یک‌ساخت‌بودن پکیج‌ها: composer type/license + فایل LICENSE (MIT مشترک)
 *
 * فلسفهٔ اجرا: روی میزبانی بدون ماژول DigiPay با پیام روشن Skip می‌شود؛
 * روی میزبانِ نصب‌شده (و سندباکسِ با نصب موقت) راستی‌آزمایی کامل است.
 */
class Phase85Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    private function requireModule(): void
    {
        if (! is_dir(base_path('Modules/DigiPay/app'))) {
            $this->markTestSkipped('ماژول DigiPay روی این میزبان نصب نیست — تست فاز ۸۵ رد شد.');
        }
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۸۵',
            'username'   => 'feat85-'.Str::random(6),
            'email'      => 'feat85-'.Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /* ============ ۱) صفحهٔ تنظیمات: دو سکشن مستقل درگاه ============ */

    #[Test]
    #[TestDox('تنظیمات: دو سکشن مستقل دیجی‌پی/اسنپ‌پی با سوییچ فعال‌سازی هر درگاه رندر می‌شوند')]
    public function settings_page_renders_two_independent_gateway_sections(): void
    {
        $this->requireModule();
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('digipay.settings', fn ($user = null) => true);

        $response = $this->get(route('admin.digipay.settings'));

        $response->assertOk();
        $html = $response->getContent();

        // دو پوستهٔ مستقل با آی‌دی یکتا + سوییچ فعال‌سازی مجزا
        $this->assertStringContainsString('id="dps-gw-digipay"', $html);
        $this->assertStringContainsString('id="dps-gw-snappay"', $html);
        $this->assertSame(
            1,
            substr_count($html, 'name="enabled"'),
            'سوییچ فعال‌سازی دیجی‌پی باید فقط داخل سکشن دیجی‌پی باشد.'
        );
        $this->assertSame(
            1,
            substr_count($html, 'name="snappay_enabled"'),
            'سوییچ فعال‌سازی اسنپ‌پی باید فقط داخل سکشن اسنپ‌پی باشد.'
        );
        // بج وضعیت زندهٔ هر سکشن (با JS همگام می‌شود)
        $this->assertSame(2, substr_count($html, 'data-dps-state-label'));
        // نوار لایسنس مشترک
        $this->assertStringContainsString('وضعیت لایسنس ماژول', $html);
    }

    #[Test]
    #[TestDox('فیلدهای اسنپ‌پی فقط داخل سکشن اسنپ‌پی هستند و کلیدهای دیجی‌پی جدا می‌مانند')]
    public function snappay_fields_are_scoped_to_snappay_section(): void
    {
        $this->requireModule();
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('digipay.settings', fn ($user = null) => true);

        // هر دو درگاه فعال تا دکمه‌های «تست اتصال» هر سکشن هم رندر شوند
        \Modules\DigiPay\Models\DigiPaySetting::set('enabled', '1');
        \Modules\DigiPay\Models\DigiPaySetting::set('snappay_enabled', '1');

        $response = $this->get(route('admin.digipay.settings'));
        $html = $response->getContent();
        if ($response->status() === 500) {
            preg_match('/<title>([^<]+)<\/title>/', $html, $tm);
            preg_match('/<h1[^>]*>([\s\S]{0,300}?)<\/h1>/', $html, $h1);
            file_put_contents(storage_path('framework/_phase85_500.html'), $html);
            $this->fail('500 در رندر تنظیمات — HTML خام در storage/framework/_phase85_500.html ذخیره شد');
        }
        $this->assertTrue(
            str_contains($html, 'id="dps-gw-snappay"'),
            'صفحهٔ تنظیمات باید سکشن اسنپ‌پی داشته باشد — وضعیت: ' . $response->status()
        );

        // مرزبندی سکشن‌ها
        [$digipayPart, $snappayPart] = explode('id="dps-gw-snappay"', $html, 2);

        // فیلدهای دیجی‌پی فقط در بخش اول
        $this->assertStringContainsString('name="client_id"', $digipayPart);
        $this->assertStringContainsString('name="api_base_url"', $digipayPart);

        // فیلدهای اسنپ‌پی فقط در بخش دوم (بعد از شروع سکشن اسنپ‌پی)
        foreach (['snappay_client_id', 'snappay_client_secret', 'snappay_username', 'snappay_password',
                  'snappay_sandbox_client_id', 'snappay_api_base_url', 'snappay_sandbox_api_base_url',
                  'snappay_payment_method_type', 'snappay_gateway_title', 'snappay_gateway_key'] as $field) {
            $this->assertStringContainsString("name=\"{$field}\"", $snappayPart, "{$field} باید داخل سکشن اسنپ‌پی باشد.");
            $this->assertStringNotContainsString("name=\"{$field}\"", $digipayPart, "{$field} نباید در سکشن دیجی‌پی باشد.");
        }

        // تست اتصال مستقل هر دو درگاه
        $this->assertStringContainsString('id="test-connection-btn"', $digipayPart);
        $this->assertStringContainsString('id="test-snappay-btn"', $snappayPart);
    }

    #[Test]
    #[TestDox('تست اتصال مستقل اسنپ‌پی: درگاه غیرفعال → پاسخ راهنمای روشن')]
    public function snappay_test_connection_rejects_disabled_gateway(): void
    {
        $this->requireModule();
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('digipay.settings', fn ($user = null) => true);

        \Modules\DigiPay\Models\DigiPaySetting::set('snappay_enabled', '0');

        $response = $this->postJson(route('admin.digipay.snappay.test.connection'));

        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    /* ============ ۲) ریشهٔ «منوی پکیج جدید بعد از نصب نمی‌آید» ============ */

    #[Test]
    #[TestDox('LicenseGuard: رکورد با slug متفاوت بازار هم با نام کانونی ماژول پیدا می‌شود')]
    public function license_guard_resolves_module_with_mismatched_slug(): void
    {
        $this->requireModule();

        InstalledModule::create([
            'slug'        => 'shop-pro-digipay-gateway', // slug متفاوت بازار
            'name'        => 'DigiPay',                  // نام کانونی از module.json
            'version'     => '1.4.0',
            'status'      => 'installed',
            'installed_at'=> now(),
        ]);

        $method = new \ReflectionMethod(LicenseGuard::class, 'resolveModule');
        $method->setAccessible(true);

        $resolved = $method->invoke(null, 'digipay');

        $this->assertNotNull($resolved, 'گارد باید رکورد را با نام کانونی ماژول بیابد.');
        $this->assertSame('DigiPay', $resolved->name);
    }

    #[Test]
    #[TestDox('LicenseGuard: بدون هیچ رکورد نصب → resolveModule نتیجهٔ خالی می‌دهد (ماژول واقعاً نصب نیست)')]
    public function license_guard_resolver_returns_null_when_module_absent(): void
    {
        $this->requireModule();
        InstalledModule::query()->delete();

        $method = new \ReflectionMethod(LicenseGuard::class, 'resolveModule');
        $method->setAccessible(true);

        // در phpunit چکِ وبِ check() به‌خاطر runningInConsole دور زده می‌شود؛
        // پس هستهٔ منطق (resolveModule) مستقیم راستی‌آزمایی می‌شود.
        $this->assertNull($method->invoke(null, 'digipay'));
    }

    #[Test]
    #[TestDox('منوی دیجی‌پی در رجیستری منوها ثبت می‌شود (مستقل از چک آنلاین لایسنس)')]
    public function digipay_menu_is_registered_in_module_menu_registry(): void
    {
        $this->requireModule();

        $this->assertContains(
            'digipay::back.partials.menu',
            \App\Services\ModuleMenuRegistry::all(),
            'منوی پکیج باید بعد از نصب همیشه در سایدبار بیاید — بدون گیت چک آنلاین لایسنس.'
        );
    }

    /* ============ ۳) یک‌ساخت‌بودن پکیج‌ها (ساختار و لایسنس) ============ */

    #[Test]
    #[TestDox('ساختار و لایسنس DigiPay مثل بقیهٔ پکیج‌ها: composer + LICENSE + module.json')]
    public function digipay_package_structure_aligns_with_other_packages(): void
    {
        $this->requireModule();

        $composer = json_decode((string) file_get_contents(base_path('Modules/DigiPay/composer.json')), true);
        $this->assertSame('nwidart-module', $composer['type'] ?? null, 'type پکیج باید مثل بقیهٔ پکیج‌ها باشد.');
        $this->assertSame('MIT', $composer['license'] ?? null);
        $this->assertFileExists(base_path('Modules/DigiPay/LICENSE'), 'فایل LICENSE باید داخل پکیج باشد.');
        $this->assertFileExists(base_path('Modules/DigiPay/README.md'));

        $manifest = json_decode((string) file_get_contents(base_path('Modules/DigiPay/module.json')), true);
        $this->assertGreaterThanOrEqual('1.4.0', $manifest['version'] ?? '0', 'بازطراحی تنظیمات باید v1.4.0 یا بالاتر باشد.');
    }

    #[Test]
    #[TestDox('پکیج‌های جدید (CreditPay/MarketingMessaging): لایسنس MIT + LICENSE + منو بدون گیت')]
    public function new_packages_have_license_files_and_ungated_menus(): void
    {
        $checked = 0;

        foreach (['CreditPay', 'MarketingMessaging'] as $module) {
            $path = base_path("Modules/{$module}");

            if (! is_dir($path)) {
                continue; // روی این میزبان نصب نیست — الزام ساختار فقط برای نصب‌شده‌ها چک می‌شود
            }

            $composer = json_decode((string) file_get_contents($path.'/composer.json'), true);
            $this->assertSame('MIT', $composer['license'] ?? null, "composer.json پکیج {$module} باید license: MIT داشته باشد.");
            $this->assertFileExists($path.'/LICENSE', "پکیج {$module} باید فایل LICENSE داشته باشد.");

            // منو بدون گیت لایسنس: بلید منوی هر دو پکیج در رجیستری ثبت می‌شود
            $views = $module === 'CreditPay'
                ? ['credit-pay::back.partials.menu', 'credit-pay::back.partials.installment_menu']
                : ['marketing-messaging::back.partials.menu'];

            foreach ($views as $view) {
                $this->assertContains(
                    $view,
                    \App\Services\ModuleMenuRegistry::all(),
                    "منوی {$module} باید مستقل از لایسنس ثبت شده باشد."
                );
            }

            $checked++;
        }

        if ($checked === 0) {
            $this->addToAssertionCount(1); // هیچ پکیج جدیدی نصب نیست — بدون شکست رد می‌شود
        }
    }
}
