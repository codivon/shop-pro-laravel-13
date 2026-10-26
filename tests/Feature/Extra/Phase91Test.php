<?php

namespace Tests\Feature\Extra;

use App\Events\OrderPaid;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Permission;
use App\Models\User;
use App\Services\FrontModuleMenuRegistry;
use App\Services\ModuleMenuRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Logistics\Models\CarrierConfig;
use Modules\Logistics\Models\LogisticsSetting;
use Modules\Logistics\Models\Shipment;
use Modules\Logistics\Models\ShipmentEvent;
use Modules\Logistics\Services\Drivers\TipaxDriver;
use Modules\Logistics\Services\LogisticsManager;
use Modules\Logistics\Services\ShipmentService;
use Modules\Logistics\Services\TrackingService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۱ — پکیج «لجستیک و باربری» (Logistics v1.0.0)
 * --------------------------------------------------------------------------
 * اتصال به API باربری‌ها (تیپاکس/ماهکس/پست) + بارنامه و مرسوله با تایم‌لاین
 * + پیگیری خودکار زمان‌بندی‌شده + همگام‌سازی کد رهگیری/وضعیت ارسال با هسته
 * + پیامک وضعیت (تایپ‌های جدید هستهٔ همین فاز).
 *
 * الزام کاربر: هر امکان با کلید مستقل فعال/غیرفعال — در بند ۸ گاردها راستی‌آزمایی می‌شود.
 * قرارداد فاز ۸۶+: هویت پکیج، license_skip، منو بدون گیت لایسنس، راهنمای
 * درون‌صفحه‌ای (نه در منوی فرانت — درس فاز ۸۹)، InstallPermissionsCommand
 * + seedDefaults، رندر واقعی صفحات دو پنل.
 */
class Phase91Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Cache::flush();
        LogisticsSetting::flushCache();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    /* ================= کمکی‌ها ================= */

    private function lgDir(): ?string
    {
        $path = base_path('Modules/Logistics');

        return is_dir($path.'/app') ? $path : null;
    }

    private function requireLogistics(): string
    {
        $path = $this->lgDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول Logistics (لجستیک و باربری) روی این میزبان نصب نیست — تست فاز ۹۱ رد شد.');
        }

        return $path;
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۹۱',
            'username'   => 'feat91-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat91-'.\Illuminate\Support\Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    private function read(string $relative): string
    {
        $path = base_path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /** کامپایل blade + lint؛ خروجی exitCode */
    private function compileAndLint(string $viewName): int
    {
        $path     = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'lg91-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return $exitCode;
    }

    private function grantCreatorPermissions(): Admin
    {
        // گیت‌های پرمیژن هسته در CLI ثبت نمی‌شوند (AuthServiceProvider فقط
        // خارج از console) — عین کلوزر پروداکشن اینجا تعریف می‌کنیم.
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission->name, function ($user = null) use ($permission) {
                $admin = $user instanceof \App\Models\Admin
                    ? $user
                    : \Illuminate\Support\Facades\Auth::guard('adminPanel')->user();

                return $admin instanceof \App\Models\Admin && $admin->level == 'creator';
            });
        }

        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /** درایور فیک تیپاکس — بدون HTTP واقعی (قرارداد تست‌پذیری پکیج) */
    private function fakeTipax(array $events = []): void
    {
        $driver = new class extends TipaxDriver
        {
            public array $registered = [];

            public function isConfigured(): bool
            {
                return true;
            }

            public function register(\Modules\Logistics\Models\Shipment $shipment): array
            {
                $waybill = 'TPX-'.random_int(10000, 99999);
                $this->registered[] = $waybill;

                return ['waybill' => $waybill, 'raw' => ['fake' => true]];
            }

            public function track(\Modules\Logistics\Models\Shipment $shipment): array
            {
                return $this->events;
            }
        };
        $driver->events = $events;

        $this->app->instance(TipaxDriver::class, $driver);
    }

    private function paidOrder(User $user, array $attrs = []): array
    {
        $product = $this->newProduct();

        return $this->newPaidOrder($user, $product, $attrs);
    }

    /* ================= ۱) هویت پکیج ================= */

    #[Test]
    #[TestDox('بند ۱ — هویت پکیج: module.json (title فارسی + v1.0.0) + composer.json + قرارداد license_skip در LicenseGuard و config')]
    public function package_identity_and_license_skip_contract(): void
    {
        $dir = $this->requireLogistics();

        $module = json_decode((string) file_get_contents($dir.'/module.json'), true);
        $this->assertSame('Logistics', $module['name']);
        $this->assertSame('logistics', $module['alias']);
        $this->assertSame('لجستیک و باربری', $module['title']);
        $this->assertSame('1.0.0', $module['version']);
        $this->assertStringContainsString('LogisticsServiceProvider', $module['providers'][0]);

        $composer = json_decode((string) file_get_contents($dir.'/composer.json'), true);
        $this->assertSame('modules/logistics', $composer['name']);

        // قرارداد license_skip — الگوی PayYar/MarketingMessaging/CustomerClub
        $guard = $this->read('Modules/Logistics/app/Services/LicenseGuard.php');
        $this->assertStringContainsString("LogisticsSetting::get('license_skip'", $guard);
        $this->assertStringContainsString("config('logistics.license_skip'", $guard);

        $config = $this->read('Modules/Logistics/config/config.php');
        $this->assertStringContainsString("'license_skip'", $config);
    }

    #[Test]
    #[TestDox('بند ۲ — InstallPermissionsCommand با قرارداد نام + seedDefaults (تنظیمات + ۳ پیکربندی باربری) idempotent')]
    public function install_permissions_command_contract_and_seeded_defaults(): void
    {
        $this->requireLogistics();

        // قرارداد نام کلاس/امضا برای نصب‌کنندهٔ هاست
        $this->assertFileExists(base_path('Modules/Logistics/app/Console/Commands/InstallPermissionsCommand.php'));
        $cmd = $this->read('Modules/Logistics/app/Console/Commands/InstallPermissionsCommand.php');
        $this->assertStringContainsString("logistics:install-permissions {--remove}", $cmd);
        $this->assertStringContainsString('seedDefaults', $cmd);

        // seedDefaults: تنظیمات و پیکربندی ۳ باربری موجودند
        $this->assertSame('1', (string) LogisticsSetting::get('logistics_enabled'));
        $this->assertGreaterThan(0, (int) LogisticsSetting::get('track_interval_minutes'));
        $this->assertSame(3, CarrierConfig::query()->count(), '۳ باربری باید seed شده باشد.');

        // هم‌راستایی پرچم فعال هر باربری با کلید تنظیمات
        foreach (CarrierConfig::DRIVERS as $driver => $title) {
            $config = CarrierConfig::for($driver);
            $this->assertNotNull($config, "پیکربندی {$driver} seed نشده است.");
            $this->assertSame(
                LogisticsSetting::enabled($driver.'_enabled'),
                (bool) $config->is_active,
                "پرچم فعال {$driver} با تنظیمات هم‌راستا نیست."
            );
        }

        // idempotency: اجرای دوباره مقدارها را بازنویسی نمی‌کند
        LogisticsSetting::set('track_interval_minutes', '77');
        \Illuminate\Support\Facades\Artisan::call('logistics:install-permissions');
        $this->assertSame('77', (string) LogisticsSetting::get('track_interval_minutes'), 'اجرای مجدد نباید مقدار ادمین را بازنویسی کند.');
        LogisticsSetting::set('track_interval_minutes', '30');

        // idempotency پرمیژن — دوباره اجرا نمی‌سازد
        $countBefore = Permission::query()->where('name', 'like', 'logistics%')->count();
        \Illuminate\Support\Facades\Artisan::call('logistics:install-permissions');
        $this->assertSame($countBefore, Permission::query()->where('name', 'like', 'logistics%')->count());
    }

    /* ================= ۲) موتور مرسوله ================= */

    #[Test]
    #[TestDox('بند ۳ — ساخت مرسوله از سفارش: پیش‌نویس با داده‌های گیرنده + وزن تخمینی + گاردهای سفارش پرداخت‌نشده/فیزیکی/تکراری')]
    public function create_shipment_from_order_with_guards(): void
    {
        $this->requireLogistics();
        $service = app(ShipmentService::class);

        $user = $this->newUser();
        [$order] = $this->paidOrder($user, ['status' => 'unpaid']);

        // گارد سفارش پرداخت‌نشده
        try {
            $service->createFromOrder($order);
            $this->fail('سفارش پرداخت‌نشده نباید مرسوله بگیرد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('پرداخت', $e->getMessage());
        }

        $order->update(['status' => 'paid']);
        $order->refresh();

        $shipment = $service->createFromOrder($order);

        $this->assertSame('draft', $shipment->status);
        $this->assertSame($order->id, (int) $shipment->order_id);
        $this->assertSame($order->name, $shipment->receiver_name);
        $this->assertSame($order->mobile, $shipment->receiver_mobile);
        $this->assertSame($order->address, $shipment->address);
        $this->assertSame($order->postal_code, $shipment->postal_code);
        $this->assertNull($shipment->waybill_number);
        $this->assertGreaterThan(0, (int) $shipment->weight, 'وزن باید از محصولات تخمین زده شود.');
        $this->assertArrayHasKey('items', $shipment->meta ?? []);

        // گارد مرسولهٔ فعال تکراری
        try {
            $service->createFromOrder($order);
            $this->fail('مرسولهٔ فعال تکراری نباید ساخته شود.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('از قبل', $e->getMessage());
        }
    }

    #[Test]
    #[TestDox('بند ۴ — ثبت بارنامه دستی: وضعیت registered + رویداد + همگام‌سازی کد رهگیری با هسته (گارد کلید)')]
    public function manual_register_syncs_tracking_code_with_core(): void
    {
        $this->requireLogistics();
        $service = app(ShipmentService::class);
        $user = $this->newUser();
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order);

        // همگام‌سازی روشن (پیش‌فرض) — کد رهگیری دستی در هسته هم می‌نشیند
        $service->manualRegister($shipment, '247385910123456789012345');
        $shipment->refresh();

        $this->assertSame('registered', $shipment->status);
        $this->assertSame('247385910123456789012345', $shipment->waybill_number);
        $order->refresh();
        $this->assertSame($shipment->waybill_number, $order->tracking_code, 'کد رهگیری باید با هسته همگام شود.');

        // رویداد ثبت باید در تایم‌لاین باشد
        $this->assertTrue(
            ShipmentEvent::query()->where('shipment_id', $shipment->id)->where('code', 'registered')->exists()
        );

        // کلید خاموش → همگام‌سازی نمی‌شود
        LogisticsSetting::set('sync_tracking_code_enabled', '0');
        [$order2] = $this->paidOrder($user);
        $shipment2 = $service->createFromOrder($order2);
        $service->manualRegister($shipment2, '247385910123456789099999');
        $order2->refresh();
        $this->assertNull($order2->tracking_code, 'با کلید خاموش، کد رهگیری نباید در هسته بنشیند.');
        LogisticsSetting::set('sync_tracking_code_enabled', '1');
    }

    #[Test]
    #[TestDox('بند ۵ — ثبت بارنامه با درایور فیک: کد از API + رویداد + همگام‌سازی (بدون HTTP واقعی)')]
    public function register_via_fake_driver(): void
    {
        $this->requireLogistics();
        $service = app(ShipmentService::class);
        $user = $this->newUser();
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order, 'tipax');

        // تیپاکس پیش‌فرض خاموش است — فعال می‌کنیم (الگوی کلید مستقل هر باربری)
        LogisticsSetting::set('tipax_enabled', '1');

        $this->fakeTipax();

        $service->registerViaDriver($shipment, 'tipax');
        $shipment->refresh();

        $this->assertSame('registered', $shipment->status);
        $this->assertMatchesRegularExpression('/^TPX-\d+$/', (string) $shipment->waybill_number);
        $order->refresh();
        $this->assertSame($shipment->waybill_number, $order->tracking_code);
        $this->assertArrayHasKey('register_raw', $shipment->meta ?? []);
    }

    #[Test]
    #[TestDox('بند ۶ — پیگیری: رویدادهای جدید + ضدرتکرار + گذار وضعیت (در مسیر → سفارش «ارسال شد» + تحویل)')]
    public function tracking_records_events_and_transitions(): void
    {
        $this->requireLogistics();
        $service = app(ShipmentService::class);
        $tracking = app(TrackingService::class);
        $user = $this->newUser();
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order, 'post');
        $service->manualRegister($shipment, '247385910123456789012345');

        // رویداد حرکت → in_transit + سفارش «sent»
        $new = $tracking->recordEvents($shipment, [
            ['code' => 'picked_up', 'title' => 'تحویل به مأمور پست', 'happened_at' => '2026-09-02 10:00:00', 'hub' => 'تهران'],
            ['code' => 'in_transit', 'title' => 'در مسیر', 'happened_at' => '2026-09-02 12:00:00', 'hub' => 'تهران'],
        ]);
        $this->assertSame(2, $new);
        $shipment->refresh();
        $this->assertSame('in_transit', $shipment->status);
        $this->assertNotNull($shipment->sent_at);
        $order->refresh();
        $this->assertSame('sent', $order->shipping_status, 'با حرکت مرسوله، سفارش هسته باید «ارسال شد» شود.');

        // dispatch دوباره همان رویدادها → صفر تازه (ضدرتکرار)
        $this->assertSame(0, $tracking->recordEvents($shipment, [
            ['code' => 'picked_up', 'title' => 'تحویل به مأمور پست', 'happened_at' => '2026-09-02 10:00:00', 'hub' => 'تهران'],
            ['code' => 'in_transit', 'title' => 'در مسیر', 'happened_at' => '2026-09-02 12:00:00', 'hub' => 'تهران'],
        ]));

        // تحویل → delivered + تاریخ تحویل
        $tracking->recordEvents($shipment, [
            ['code' => 'delivered', 'title' => 'تحویل شد', 'happened_at' => '2026-09-03 09:30:00', 'hub' => 'شیراز'],
        ]);
        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status);
        $this->assertNotNull($shipment->delivered_at);

        // گذار سفارش با کلید خاموش کار نمی‌کند
        LogisticsSetting::set('sync_order_status_enabled', '0');
        [$order2] = $this->paidOrder($user);
        $shipment2 = $service->createFromOrder($order2, 'post');
        $service->manualRegister($shipment2, '247385910123456789011111');
        $tracking->recordEvents($shipment2, [
            ['code' => 'in_transit', 'title' => 'در مسیر', 'happened_at' => '2026-09-02 15:00:00'],
        ]);
        $order2->refresh();
        $this->assertNotSame('sent', $order2->shipping_status, 'با کلید خاموش، وضعیت سفارش نباید عوض شود.');
        LogisticsSetting::set('sync_order_status_enabled', '1');
    }

    #[Test]
    #[TestDox('بند ۷ — پیگیری خودکار: گارد فاصلهٔ last_tracked_at + کلید auto_tracking_enabled=0 → هیچ')]
    public function track_due_respects_interval_and_toggles(): void
    {
        $this->requireLogistics();
        $service = app(ShipmentService::class);
        $tracking = app(TrackingService::class);
        $user = $this->newUser();
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order, 'post');
        $service->manualRegister($shipment, '247385910123456789012345');

        // هرگز پیگیری نشده → در صف است
        $this->assertSame(1, $tracking->due(10)->count());

        // کلید پیگیری خودکار خاموش → صف خالی
        LogisticsSetting::set('auto_tracking_enabled', '0');
        $this->assertSame(0, $tracking->due(10)->count());
        [$tracked, $newEvents] = $tracking->trackDue(10);
        $this->assertSame(0, $tracked);
        LogisticsSetting::set('auto_tracking_enabled', '1');

        // کلید اصلی خاموش → صف خالی (گارد کلید اصلی)
        LogisticsSetting::set('logistics_enabled', '0');
        $this->assertSame(0, $tracking->due(10)->count());
        LogisticsSetting::set('logistics_enabled', '1');

        // تازه پیگیری‌شده → خارج از فاصله → صف خالی
        $shipment->forceFill(['last_tracked_at' => now()])->save();
        $this->assertSame(0, $tracking->due(10)->count(), 'مرسولهٔ تازه‌پیگیری‌شده نباید دوباره پیگیری شود.');

        // مرسولهٔ دستی (manual) پیگیری API نمی‌گیرد
        $shipment->forceFill(['last_tracked_at' => now()->subHours(3)])->save();
        $shipment->update(['driver' => 'manual']);
        $this->assertSame(0, $tracking->due(10)->count());
    }

    #[Test]
    #[TestDox('بند ۸ — گاردهای کلیدها: logistics_enabled=0 → فرانت ریدایرکت + درایور غیرفعال exception + ساخت مرسوله ممنوع')]
    public function settings_toggles_enforcement(): void
    {
        $this->requireLogistics();
        $user = $this->newUser();
        $this->actingAs($user);

        LogisticsSetting::set('logistics_enabled', '0');

        // فرانت ریدایرکت می‌شود (کلید اصلی خاموش)
        $response = $this->get(route('front.logistics.index'));
        $response->assertRedirect('/');

        // منوی فرانت پنهان است
        $menu = $this->read('Modules/Logistics/resources/views/front/partials/menu.blade.php');
        $this->assertStringContainsString("enabled('logistics_enabled')", $menu);

        // ساخت مرسوله ممنوع
        [$order] = $this->paidOrder($user);
        try {
            app(ShipmentService::class)->createFromOrder($order);
            $this->fail('با کلید اصلی خاموش، ساخت مرسوله نباید ممکن باشد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('غیرفعال', $e->getMessage());
        }

        // درایور غیرفعال → exception از Manager
        $this->expectException(\RuntimeException::class);
        app(LogisticsManager::class)->driver('tipax'); // tipax پیش‌فرض خاموش است
    }

    #[Test]
    #[TestDox('بند ۹ — ساخت خودکار پس از پرداخت: Listener روی OrderPaid با گارد auto_shipment_enabled + idempotent')]
    public function auto_shipment_listener_on_order_paid(): void
    {
        $this->requireLogistics();
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        // Listener واقعاً روی Dispatcher نشسته (درس فاز ۹۰ — register نه singleton).
        // نکتهٔ فنی: Dispatcher کلاس Listener را در Closure با متغیر captured
        // «listener» می‌پیچد — با ReflectionFunction نامش را بیرون می‌کشیم.
        $found = collect(Event::getListeners(OrderPaid::class))->contains(function ($listener) {
            if (!$listener instanceof \Closure) {
                return str_contains((string) $listener, 'CreateShipmentOnOrderPaid');
            }

            $uses = (new \ReflectionFunction($listener))->getClosureUsedVariables();

            return str_contains((string) ($uses['listener'] ?? ''), 'CreateShipmentOnOrderPaid');
        });
        $this->assertTrue($found, 'Listener ساخت خودکار مرسوله باید روی OrderPaid ثبت شده باشد.');

        OrderPaid::dispatch($order);

        $this->assertSame(1, Shipment::query()->where('order_id', $order->id)->count(), 'پس از پرداخت باید یک مرسولهٔ پیش‌نویس ساخته شود.');
        $this->assertSame('draft', Shipment::query()->where('order_id', $order->id)->first()->status);

        // dispatch دوباره — idempotent (گارد مرسولهٔ فعال تکراری)
        OrderPaid::dispatch($order);
        $this->assertSame(1, Shipment::query()->where('order_id', $order->id)->count());

        // کلید خاموش → هیچ
        LogisticsSetting::set('auto_shipment_enabled', '0');
        [$order2] = $this->newPaidOrder($user, $product);
        OrderPaid::dispatch($order2);
        $this->assertSame(0, Shipment::query()->where('order_id', $order2->id)->count(), 'با کلید خاموش، مرسولهٔ خودکار نباید ساخته شود.');
        LogisticsSetting::set('auto_shipment_enabled', '1');
    }

    /* ================= ۳) پنل‌ها ================= */

    #[Test]
    #[TestDox('بند ۱۰ — رندر واقعی: ۴ صفحهٔ ادمین + ۲ صفحهٔ فرانت + گیت مالکیت مرسوله در فرانت')]
    public function renders_admin_and_front_pages(): void
    {
        $this->requireLogistics();
        $admin = $this->grantCreatorPermissions();

        $this->get(route('admin.logistics.dashboard'))->assertOk()->assertSee('لجستیک');
        $this->get(route('admin.logistics.shipments.index'))->assertOk();
        $this->get(route('admin.logistics.settings.edit'))->assertOk()->assertSee('logistics_enabled');
        $this->get(route('admin.logistics.guide'))->assertOk();

        // فرانت — کاربر واقعی. نکتهٔ فنی: SessionGuardهای هسته کلید نشست مشترک
        // دارند → خروج ادمین پیش از ورود کاربر الزامی است (وگرنه گارد adminPanel
        // با User یافت‌شده از نشست مشترک، در CheckBlockedDevice تایپ‌ارور می‌دهد).
        $this->app['auth']->guard('adminPanel')->logout();
        $user = $this->newUser();
        $this->actingAs($user, 'web');
        $this->get(route('front.logistics.index'))->assertOk()->assertSee('مرسولات من');
        $this->get(route('front.logistics.guide'))->assertOk();

        // مرسولهٔ شخصی + مالکیت
        $service = app(ShipmentService::class);
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order);
        $this->get(route('front.logistics.show', $shipment))->assertOk();

        // مرسولهٔ کاربر دیگر → 404 (گیت مالکیت)
        $other = $this->newUser();
        [$otherOrder] = $this->paidOrder($other);
        $otherShipment = app(ShipmentService::class)->createFromOrder($otherOrder);
        $this->get(route('front.logistics.show', $otherShipment))->assertNotFound();
    }

    #[Test]
    #[TestDox('بند ۱۱ — اکشن‌های POST: ذخیرهٔ تنظیمات + ثبت دستی بارنامه + ابطال مرسوله')]
    public function post_actions_work(): void
    {
        $this->requireLogistics();
        $this->grantCreatorPermissions();
        $service = app(ShipmentService::class);
        $user = $this->newUser();
        [$order] = $this->paidOrder($user);
        $shipment = $service->createFromOrder($order);

        // ذخیرهٔ تنظیمات — خاموش/روشن + فاصلهٔ پیگیری
        $this->post(route('admin.logistics.settings.update'), [
            'logistics_enabled'          => '1',
            'auto_shipment_enabled'      => '1',
            'auto_tracking_enabled'      => '0',
            'sync_tracking_code_enabled' => '1',
            'sync_order_status_enabled'  => '1',
            'sms_on_status_enabled'      => '1',
            'customer_panel_enabled'     => '1',
            'tipax_enabled'              => '1',
            'mahex_enabled'              => '0',
            'post_enabled'               => '1',
            'track_interval_minutes'     => '45',
            'credentials'                => [
                'tipax' => ['driver' => 'tipax', 'api_key' => 'KEY-123', 'base_url' => '', 'sandbox' => '1'],
            ],
        ])->assertRedirect();

        $this->assertSame('0', (string) LogisticsSetting::get('auto_tracking_enabled'));
        $this->assertSame('1', (string) LogisticsSetting::get('tipax_enabled'));
        $this->assertSame('45', (string) LogisticsSetting::get('track_interval_minutes'));
        $this->assertSame('KEY-123', CarrierConfig::for('tipax')->credential('api_key'));
        $this->assertTrue((bool) CarrierConfig::for('tipax')->sandbox, 'حالت آزمایشی باید ذخیره شده باشد.');

        // ثبت دستی بارنامه
        $this->post(route('admin.logistics.shipments.manual', $shipment), [
            'waybill' => 'MANUAL-123456',
        ])->assertRedirect();

        $shipment->refresh();
        $this->assertSame('MANUAL-123456', $shipment->waybill_number);
        $this->assertSame('registered', $shipment->status);

        // ابطال
        $this->post(route('admin.logistics.shipments.cancel', $shipment), [
            'reason' => 'تست ابطال',
        ])->assertRedirect();

        $shipment->refresh();
        $this->assertSame('canceled', $shipment->status);
    }

    /* ================= ۴) قراردادها ================= */

    #[Test]
    #[TestDox('بند ۱۲ — کامپایل ۱۲ ویوی Blade پکیج (بدون ارور syntax)')]
    public function all_blade_views_compile(): void
    {
        $this->requireLogistics();

        $views = [
            'logistics::back.dashboard.index',
            'logistics::back.shipments.index',
            'logistics::back.shipments.show',
            'logistics::back.settings.index',
            'logistics::back.guide.index',
            'logistics::back.partials.menu',
            'logistics::front.index',
            'logistics::front.show',
            'logistics::front.guide.index',
            'logistics::front.partials.menu',
            'logistics::front.partials.guide_cta',
            'logistics::errors.license_invalid',
        ];

        foreach ($views as $view) {
            $this->assertSame(0, $this->compileAndLint($view), "کامپایل ویوی {$view} شکست خورد.");
        }
    }

    #[Test]
    #[TestDox('بند ۱۳ — قرارداد فاز ۸۶: مستندات داخل پکیج + CSS اسکوپ lg- + راهنمای درون‌صفحه‌ای (نه منوی فرانت) + منوها با گیت تنظیمات')]
    public function phase86_package_contract(): void
    {
        $dir = $this->requireLogistics();

        // مستندات داخل خود پکیج (سیاست فاز ۸۵)
        foreach (['docs/USAGE.md', 'docs/WORK-LOG.md', 'docs/market-description.html', 'README.md', 'CHANGELOG.md'] as $doc) {
            $this->assertFileExists($dir.'/'.$doc, "فایل {$doc} باید داخل پکیج باشد.");
        }

        // CSS اسکوپ‌شده — همهٔ کلاس‌ها با پیشوند lg-
        $css = $this->read('Modules/Logistics/resources/assets/css/lg-admin.css');
        $this->assertStringContainsString('.lg-scope', $css);

        // درس فاز ۸۹: راهنما در منوی فرانت نیست — بنر درون‌صفحه‌ای هست
        $frontMenu = $this->read('Modules/Logistics/resources/views/front/partials/menu.blade.php');
        $this->assertStringNotContainsString('front.logistics.guide', $frontMenu, 'لینک راهنما نباید در منوی فرانت باشد.');

        $guideCta = $this->read('Modules/Logistics/resources/views/front/partials/guide_cta.blade.php');
        $this->assertStringContainsString('lgg-guide-banner', $guideCta);

        $frontIndex = $this->read('Modules/Logistics/resources/views/front/index.blade.php');
        $this->assertStringContainsString('guide_cta', $frontIndex, 'بنر راهنما باید در صفحهٔ «مرسولات من» باشد.');

        // منوی ادمین با گیت پرمیژن + کلید تنظیمات
        $backMenu = $this->read('Modules/Logistics/resources/views/back/partials/menu.blade.php');
        $this->assertStringContainsString('logistics_enabled', $backMenu);
        $this->assertStringContainsString("@can('logistics')", $backMenu);

        // رجیستری‌های منو — الگوی دو-پنلی
        $provider = $this->read('Modules/Logistics/app/Providers/LogisticsServiceProvider.php');
        $this->assertStringContainsString('ModuleMenuRegistry::add', $provider);
        $this->assertStringContainsString('FrontModuleMenuRegistry::add', $provider);
    }

    #[Test]
    #[TestDox('بند ۱۴ — پیامک وضعیت: تایپ‌های جدید هسته + متدهای ۵ پرووایدر + کلید پترن در تنظیمات پیامک')]
    public function core_sms_types_and_provider_methods(): void
    {
        $this->requireLogistics();

        // تایپ‌های جدید هستهٔ فاز ۹۱
        $this->assertArrayHasKey('LOGISTICS_SHIPMENT_SENT', \App\Models\Sms::TYPES);
        $this->assertArrayHasKey('LOGISTICS_SHIPMENT_DELIVERED', \App\Models\Sms::TYPES);
        $this->assertSame('shipment-sent', \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_SENT']['key']);
        $this->assertSame('shipment-delivered', \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_DELIVERED']['key']);
        $this->assertSame('shipmentSent', \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_SENT']['method']);
        $this->assertSame('shipmentDelivered', \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_DELIVERED']['method']);

        // متدها در هر ۵ پرووایدر (فراخوانی واقعی با data تست)
        $providers = [
            \App\Services\Sms\IppanelSms::class,
            \App\Services\Sms\KavenegarSms::class,
            \App\Services\Sms\MelipayamakSms::class,
            \App\Services\Sms\IdehPardazanSms::class,
            \App\Services\Sms\FarazSms::class,
        ];
        $data = ['name' => 'علی', 'order' => '123', 'code' => 'TPX-999'];

        foreach ($providers as $providerClass) {
            $provider = new $providerClass('09120000000', $data, \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_SENT']);

            $payload = $provider->shipmentSent();
            $this->assertNotEmpty($payload, "پرووایدر {$providerClass} باید payload برمی‌گرداند.");

            // هر ۵ ساختار (input_data / attributes / ParameterArray) باید هر ۳ متغیر را برگردانند
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $this->assertStringContainsString($data['name'], $json, "پرووایدر {$providerClass} باید متغیر name را برگرداند.");
            $this->assertStringContainsString((string) $data['order'], $json, "پرووایدر {$providerClass} باید شماره سفارش را برگرداند.");
            $this->assertStringContainsString((string) $data['code'], $json, "پرووایدر {$providerClass} باید کد رهگیری را برگرداند.");

            $provider = new $providerClass('09120000000', $data, \App\Models\Sms::TYPES['LOGISTICS_SHIPMENT_DELIVERED']);
            $payload = $provider->shipmentDelivered();
            $this->assertNotEmpty($payload);
        }

        // فیلدهای پترن در تنظیمات پیامک هسته (هر ۵ پنل)
        $defs = \App\Livewire\Admin\Settings\SmsSettings::patternDefinitions();
        foreach (['ippanel', 'kavenegar', 'melipayamak', 'idehpardazan', 'farazsms'] as $providerKey) {
            $this->assertArrayHasKey('shipment_sent_pattern_code', $defs[$providerKey] ?? [], "پترن ارسال برای {$providerKey} باید تعریف شده باشد.");
            $this->assertArrayHasKey('shipment_delivered_pattern_code', $defs[$providerKey] ?? [], "پترن تحویل برای {$providerKey} باید تعریف شده باشد.");
        }
    }
}
