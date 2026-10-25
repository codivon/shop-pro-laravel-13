<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Settings\Others;
use App\Models\Admin;
use App\Models\PriceAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۲ — مهاجرت تنظیمات هشدار کاهش قیمت/پیش‌فروش به صفحهٔ «تنظیمات دیگر» سایت
 * + خودکفایی CSRF کال‌بک اسنپ‌پی در پکیج DigiPay.
 *
 * درخواست کاربر: «این تنظیمات مربوط به خود سایت هستند نه قالب — به صفحهٔ تنظیمات
 * دیگر اضافه شوند؛ تنظیمات پکیج هم باید در خود پکیج باشد.»
 *
 * پوشش:
 *  - تم دیگر مالک این فیلدها نیست (رفع مالکیت اشتباه فاز ۸۱)
 *  - صفحهٔ «تنظیمات دیگر» (پنل جدید): mount با پیش‌فرض‌ها + ذخیره با فرمت «1»/«0»
 *  - پنل قدیم هم فیلدها را دارد (سازگاری دو-پنلی)
 *  - گیت سراسری بعد از انتقال همچنان کار می‌کند (ذخیره از تنظیمات → بستن اشتراک)
 *  - مکانیزم withoutMiddleware واقعاً POST بدون توکن را از 419 نجات می‌دهد
 *    (آن چیزی که کال‌بک اسنپ‌پی در تم گم کرده بود)
 *  - اگر ماژول DigiPay نصب باشد: مسیر کال‌بک اسنپ‌پی ثبت شده و CSRF را مستثنی کرده
 */
class Phase82Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // دیتابیس تست ماندگار است — کلیدهای این قابلیت به حالت تمیز برمی‌گردند
        // (متن‌ها عمداً «خالی» می‌شوند تا پیش‌فرض‌های mount در تست راستی‌آزمایی شوند)
        foreach ([
            'dt_price_alert_status' => '1',
            'dt_price_alert_button_text' => '',
            'dt_price_alert_button_icon' => '',
            'dt_pre_order_status' => '1',
            'dt_pre_order_button_text' => '',
            'dt_pre_order_badge_text' => '',
        ] as $key => $value) {
            option_update($key, $value);
        }
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // برگرداندن کلیدهای سراسری به پیش‌فرض تا روی تست‌های دیگر اثر نگذارد
        option_update('dt_price_alert_status', '1');
        option_update('dt_pre_order_status', '1');
        Cache::flush();

        parent::tearDown();
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'قابلیت‌های فروشگاه',
            'username'   => 'feat82-'.Str::random(6),
            'email'      => 'feat82-'.Str::random(6).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /* ================= مالکیت تنظیمات: سایت نه قالب ================= */

    #[Test]
    public function theme_config_no_longer_owns_site_feature_fields(): void
    {
        $config = include base_path('themes/WeblakShop/config/general.php');

        $keys = collect($config['settings']['fields'])->pluck('key')->filter()->all();

        foreach ([
            'dt_price_alert_status',
            'dt_price_alert_button_text',
            'dt_price_alert_button_icon',
            'dt_pre_order_status',
            'dt_pre_order_button_text',
            'dt_pre_order_badge_text',
        ] as $key) {
            $this->assertNotContains(
                $key,
                $keys,
                "فیلد {$key} باید در «تنظیمات دیگر» سایت باشد نه تنظیمات قالب (فاز ۸۲)"
            );
        }

        $this->assertArrayNotHasKey('dt_price_alert_status', $config['settings']['rules']);
        $this->assertArrayNotHasKey('dt_pre_order_status', $config['settings']['rules']);
    }

    #[Test]
    public function others_page_mounts_with_defaults_and_saves_one_zero_storage(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        // پرمیشن‌های گیت فقط خارج از CLI تعریف می‌شوند (AuthServiceProvider) — در تست دستی تعریفش می‌کنیم
        Gate::define('settings.others', fn ($user = null) => true);

        Livewire::test(Others::class)
            ->assertSet('dt_price_alert_status', true)
            ->assertSet('dt_price_alert_button_text', 'هشدار کاهش قیمت')
            ->assertSet('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline')
            ->assertSet('dt_pre_order_status', true)
            ->assertSet('dt_pre_order_button_text', 'ثبت پیش‌فروش')
            ->assertSet('dt_pre_order_badge_text', 'پیش‌فروش')
            ->set('dt_price_alert_status', false)
            ->set('dt_price_alert_button_text', 'قیمت افتاد؟ خبرم کن')
            ->set('dt_price_alert_button_icon', 'mdi mdi-bell-outline')
            ->set('dt_pre_order_status', true)
            ->set('dt_pre_order_button_text', 'همین حالا پیش‌خرید کن')
            ->set('dt_pre_order_badge_text', 'پیش‌خرید')
            ->call('save')
            ->assertHasNoErrors();

        // سوییچ‌ها باید «1»/«0» ذخیره شوند (هسته با == '1' چک می‌کند نه true/false)
        $this->assertSame('0', (string) option('dt_price_alert_status'));
        $this->assertSame('1', (string) option('dt_pre_order_status'));

        $this->assertSame('قیمت افتاد؟ خبرم کن', (string) option('dt_price_alert_button_text'));
        $this->assertSame('mdi mdi-bell-outline', (string) option('dt_price_alert_button_icon'));
        $this->assertSame('همین حالا پیش‌خرید کن', (string) option('dt_pre_order_button_text'));
        $this->assertSame('پیش‌خرید', (string) option('dt_pre_order_badge_text'));

        // mount بعدی باید مقادیر ذخیره‌شده را بخواند (گردش کامل ذخیره/خواندن)
        Livewire::test(Others::class)
            ->assertSet('dt_price_alert_status', false)
            ->assertSet('dt_price_alert_button_text', 'قیمت افتاد؟ خبرم کن')
            ->assertSet('dt_pre_order_badge_text', 'پیش‌خرید');
    }

    #[Test]
    public function empty_text_fields_fall_back_to_defaults_on_save(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        Gate::define('settings.others', fn ($user = null) => true);

        Livewire::test(Others::class)
            ->set('dt_price_alert_button_text', '')
            ->set('dt_price_alert_button_icon', '  ')
            ->set('dt_pre_order_badge_text', '')
            ->call('save')
            ->assertHasNoErrors();

        // قاعدهٔ کاربر: «خالی بماند = پیش‌فرض» — هیچ رشتهٔ خالی ذخیره نمی‌شود
        $this->assertSame('هشدار کاهش قیمت', (string) option('dt_price_alert_button_text'));
        $this->assertSame('mdi mdi-bell-ring-outline', (string) option('dt_price_alert_button_icon'));
        $this->assertSame('ثبت پیش‌فروش', (string) option('dt_pre_order_button_text'));
        $this->assertSame('پیش‌فروش', (string) option('dt_pre_order_badge_text'));
    }

    #[Test]
    public function legacy_panel_others_view_carries_the_fields_too(): void
    {
        $view = file_get_contents(resource_path('views/back/settings/others.blade.php'));

        foreach ([
            'name="dt_price_alert_status"',
            'name="dt_price_alert_button_text"',
            'name="dt_price_alert_button_icon"',
            'name="dt_pre_order_status"',
            'name="dt_pre_order_button_text"',
            'name="dt_pre_order_badge_text"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $view, "پنل قدیم باید {$needle} را داشته باشد");
        }
    }

    #[Test]
    public function global_gate_still_blocks_price_alert_after_settings_move(): void
    {
        $product = $this->newProduct();

        // همان کلیدی که از «تنظیمات دیگر» ذخیره می‌شود، اشتراک را می‌بندد
        option_update('dt_price_alert_status', '0');
        Cache::flush();

        $this->post(route('front.price-alert.store'), [
            'product_id' => $product->id,
            'mobile'     => '09121234567',
        ])->assertNotFound();

        $this->assertEquals(0, PriceAlert::count());
    }

    /* ================= مکانیزم استثنای CSRF (کال‌بک اسنپ‌پی) ================= */

    #[Test]
    public function without_middleware_really_removes_csrf_from_route_stack(): void
    {
        RouteFacade::middlewareGroup('phase82-web-clone', [
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        RouteFacade::post('/phase82/with-csrf', fn () => 'ok')
            ->name('phase82.with-csrf')
            ->middleware('phase82-web-clone');

        // آزمون: همان گروه ولی با withoutMiddleware روی خود مسیر (کاری که پکیج DigiPay
        // برای کال‌بک POST اسنپ‌پی می‌کند)
        RouteFacade::post('/phase82/exempt', fn () => 'ok')
            ->name('phase82.exempt')
            ->middleware('phase82-web-clone')
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

        $router = app('router');
        // مسیرهای ران‌تایم بعد از add() نام‌گذاری می‌شوند → نام‌لیست باید تازه شود
        $router->getRoutes()->refreshNameLookups();

        $gathered = fn (string $name) => collect($router->gatherRouteMiddleware($router->getRoutes()->getByName($name)))
            ->map(fn ($m) => is_object($m) ? get_class($m) : $m);

        // مسیر کنترل: CSRF در استک میدلورها می‌ماند (در پروداکشن → 419)
        $this->assertContains(\App\Http\Middleware\VerifyCsrfToken::class, $gathered('phase82.with-csrf')->all());

        // مسیر مستثنی‌شده: CSRF حذف شده (همان چیزی که کال‌بک اسنپ‌پی لازم دارد)
        $this->assertNotContains(\App\Http\Middleware\VerifyCsrfToken::class, $gathered('phase82.exempt')->all());
    }

    #[Test]
    public function snappay_callback_route_is_registered_and_csrf_exempt_when_module_installed(): void
    {
        if (! is_dir(base_path('Modules/DigiPay'))) {
            $this->markTestSkipped('ماژول DigiPay روی این میزبان نصب نیست — تست CSRF اسنپ‌پی رد شد.');
        }

        $route = RouteFacade::getRoutes()->getByName('digipay.snappay-callback');

        $this->assertNotNull($route, 'مسیر POST کال‌بک اسنپ‌پی باید ثبت شده باشد');
        $this->assertEquals('digipay/snappay/callback', $route->uri());
        $this->assertContains(
            \App\Http\Middleware\VerifyCsrfToken::class,
            $route->excludedMiddleware() ?? [],
            'کال‌بک POST اسنپ‌پی باید بدون وابستگی به تم، CSRF را خودش مستثنی کند'
        );

        // دودتست: POST بدون توکن CSRF نباید 419 بدهد (تراکنش ناشناخته → پاس غیر 419)
        $response = $this->post('/digipay/snappay/callback', [
            'transactionId' => 'phase82-unknown',
            'state'         => 'FAILED',
            'amount'        => 1000,
        ]);

        $this->assertNotEquals(419, $response->status());
    }
}
