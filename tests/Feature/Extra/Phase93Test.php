<?php

namespace Tests\Feature\Extra;

use App\Events\OrderPaid;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Permission;
use App\Services\ModuleMenuRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Accounting\Models\AccountingConnection;
use Modules\Accounting\Models\AccountingDocument;
use Modules\Accounting\Models\AccountingSetting;
use Modules\Accounting\Models\AccountingSyncLog;
use Modules\Accounting\Services\AccountingManager;
use Modules\Accounting\Services\Drivers\AccountingDriver;
use Modules\Accounting\Services\Drivers\HesabfaDriver;
use Modules\Accounting\Services\SyncService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۳ — پکیج «حسابداری» (Accounting v1.0.0)
 * --------------------------------------------------------------------------
 * بستن فاز ۸۳ نقشهٔ راه: همگام‌سازی حسابفا/سپیدار/هلو — سند فروش و موجودی.
 *
 *  • سند فروش: OrderPaid → درایور نرم‌افزار فعال → accounting_documents
 *    (idempotent با یگانهٔ order_id+provider؛ خطا هرگز سفارش را نمی‌شکند)
 *  • موجودی: کشش از نرم‌افزار → تطبیق با products.product_id → به‌روزرسانی
 *    prices.stock (فقط تک‌قیمت)
 *  • قرارداد فاز ۸۶+: هویت، license_skip، منوی دو-پنلی، راهنمای ادمین،
 *    InstallPermissionsCommand قراردادی + seedDefaults idempotent.
 *  • الزام شخصی‌سازی: هر امکان کلید مستقل فعال/غیرفعال دارد.
 */
class Phase93Test extends TestCase
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

    /* ================= کمکی‌ها ================= */

    private function requireAccounting(): void
    {
        if (! is_dir(base_path('Modules/Accounting/app'))) {
            $this->markTestSkipped('ماژول Accounting (حسابداری) روی این میزبان نصب نیست — تست فاز ۹۳ رد شد.');
        }
    }

    private function read(string $relative): string
    {
        $path = base_path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /** فعال‌سازی پیش‌فرض‌ها برای هر تست */
    private function accountingDefaults(array $overrides = []): void
    {
        AccountingSetting::set('accounting_enabled', '1');
        AccountingSetting::set('auto_invoice_enabled', '1');
        AccountingSetting::set('inventory_sync_enabled', '1');
        AccountingSetting::set('include_shipping_enabled', '1');
        AccountingSetting::set('provider', 'hesabfa');
        AccountingSetting::set('hesabfa_enabled', '1');
        AccountingSetting::set('sepidar_enabled', '0');
        AccountingSetting::set('holoo_enabled', '0');

        foreach ($overrides as $key => $value) {
            AccountingSetting::set($key, (string) $value);
        }

        AccountingConnection::ensureSeeded();
    }

    /**
     * درایور فیک حسابداری — بدون HTTP واقعی؛ رفتار قابل برنامه‌ریزی.
     */
    private function bindFakeDriver(array $stocks = [], ?\Throwable $throw = null, string $number = 'INV-1001'): void
    {
        app()->bind(AccountingDriver::class.'.fake', fn () => new class($stocks, $throw, $number) implements AccountingDriver
        {
            public function __construct(
                protected array $stocks,
                protected ?\Throwable $throw,
                protected string $number,
            ) {}

            public function key(): string
            {
                return 'hesabfa';
            }

            public function title(): string
            {
                return 'حسابفا (فیک)';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function createSalesInvoice(Order $order, array $items, float $total): array
            {
                if ($this->throw) {
                    throw $this->throw;
                }

                return ['remote_id' => $this->number, 'number' => $this->number, 'raw' => ['fake' => true, 'items' => count($items), 'total' => $total]];
            }

            public function fetchStocks(): array
            {
                if ($this->throw) {
                    throw $this->throw;
                }

                return $this->stocks;
            }

            public function ping(): array
            {
                if ($this->throw) {
                    throw $this->throw;
                }

                return ['ok' => true, 'message' => 'اتصال فیک سالم است.'];
            }
        });
    }

    private function bindFakeDriverIntoManager(): void
    {
        // Manager درایور را از container می‌گیرد — فیک را جای درایور واقعی می‌نشانیم
        app()->bind(HesabfaDriver::class, function () {
            return app(AccountingDriver::class.'.fake');
        });
    }

    private function newCreator(): Admin
    {
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۹۳',
            'username' => 'feat93-'.Str::random(6),
            'email' => 'feat93-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    private function compileAndLint(string $viewName): int
    {
        $path = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'ac93-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return $exitCode;
    }

    /* ================= ۱) هویت پکیج ================= */

    #[Test]
    #[TestDox('بند ۱ — هویت پکیج: module.json (title فارسی + v1.0.0) + composer.json + قرارداد license_skip + کلیدهای پیش‌فرض config')]
    public function package_identity_and_license_skip_contract(): void
    {
        $this->requireAccounting();

        $module = json_decode((string) file_get_contents(base_path('Modules/Accounting/module.json')), true);
        $this->assertSame('Accounting', $module['name']);
        $this->assertSame('accounting', $module['alias']);
        $this->assertSame('حسابداری', $module['title']);
        $this->assertSame('1.0.1', $module['version']); // فاز ۱۰۵ — تب‌بندی تنظیمات در مخزن مستقل (bump نسخه)
        $this->assertStringContainsString('AccountingServiceProvider', $module['providers'][0]);

        $composer = json_decode((string) file_get_contents(base_path('Modules/Accounting/composer.json')), true);
        $this->assertSame('modules/accounting', $composer['name']);

        // قرارداد license_skip — الگوی PayYar/MarketingMessaging/CustomerClub/Logistics
        $guard = $this->read('Modules/Accounting/app/Services/LicenseGuard.php');
        $this->assertStringContainsString("AccountingSetting::get('license_skip'", $guard);
        $this->assertStringContainsString("config('accounting.license_skip'", $guard);

        $config = $this->read('Modules/Accounting/config/config.php');
        foreach ([
            "'accounting_enabled'", "'auto_invoice_enabled'", "'inventory_sync_enabled'",
            "'include_shipping_enabled'", "'provider'", "'hesabfa_enabled'",
            "'sepidar_enabled'", "'holoo_enabled'", "'inventory_interval_minutes'",
            "'invoice_title_prefix'", "'license_skip'",
        ] as $key) {
            $this->assertStringContainsString($key, $config, "کلید {$key} در پیش‌فرض‌های config نیست.");
        }

        // درایورهای نقشهٔ راه — سه نرم‌افزار
        foreach (['HesabfaDriver', 'SepidarDriver', 'HolooDriver'] as $driver) {
            $this->assertFileExists(base_path("Modules/Accounting/app/Services/Drivers/{$driver}.php"));
        }
    }

    #[Test]
    #[TestDox('بند ۲ — InstallPermissionsCommand با قرارداد نام + seedDefaults (تنظیمات + ۳ اتصال) idempotent')]
    public function install_permissions_command_contract_and_seeded_defaults(): void
    {
        $this->requireAccounting();

        // قرارداد نام کلاس/امضا برای نصب‌کنندهٔ هاست
        $this->assertFileExists(base_path('Modules/Accounting/app/Console/Commands/InstallPermissionsCommand.php'));
        $cmd = $this->read('Modules/Accounting/app/Console/Commands/InstallPermissionsCommand.php');
        $this->assertStringContainsString('accounting:install-permissions {--remove}', $cmd);
        $this->assertStringContainsString('seedDefaults', $cmd);

        // seedDefaults: تنظیمات + اتصال‌ها موجودند (کامند واقعاً در سندباکس اجرا شده)
        $this->assertSame('1', (string) AccountingSetting::get('accounting_enabled'));
        $this->assertSame('hesabfa', (string) AccountingSetting::get('provider'));

        foreach (AccountingConnection::PROVIDERS as $provider => $title) {
            $this->assertNotNull(AccountingConnection::for($provider), "اتصال {$title} seed نشده است.");
        }

        // idempotency: اجرای دوباره مقدار ادمین را بازنویسی نمی‌کند
        AccountingSetting::set('provider', 'holoo');
        Artisan::call('accounting:install-permissions');
        $this->assertSame('holoo', (string) AccountingSetting::get('provider'), 'اجرای مجدد نباید مقدار ادمین را بازنویسی کند.');
        AccountingSetting::set('provider', 'hesabfa');
    }

    /* ================= ۲) سند فروش ================= */

    #[Test]
    #[TestDox('بند ۳ — سند فروش خودکار با درایور فیک: OrderPaid → سند + لاگ موفق + idempotent با dispatch دوباره')]
    public function auto_sales_invoice_on_order_paid_with_fake_driver(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();
        $this->bindFakeDriver();
        $this->bindFakeDriverIntoManager();

        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product, ['price' => 60000], ['quantity' => 2]);

        OrderPaid::dispatch($order);

        $document = AccountingDocument::query()->where('order_id', $order->id)->where('provider', 'hesabfa')->first();
        $this->assertNotNull($document, 'سند فروش باید ساخته شود.');
        $this->assertSame(AccountingDocument::STATUS_SYNCED, $document->status);
        $this->assertSame('INV-1001', $document->remote_number);
        $this->assertGreaterThan(0, $document->items_count, 'اقلام سند باید پر شده باشد.');
        $this->assertNotNull($document->synced_at);

        // اقلام سند: کد کالا از SKU محصول + هزینهٔ ارسال
        $itemCodes = collect($document->meta['items'])->pluck('code')->all();
        $this->assertContains($product->product_id, $itemCodes, 'کد کالای محصول باید در اقلام سند باشد.');

        // لاگ موفق ثبت شده
        $this->assertSame(1, AccountingSyncLog::query()
            ->where('action', AccountingSyncLog::ACTION_INVOICE)
            ->where('status', 'success')
            ->where('provider', 'hesabfa')
            ->count());

        // dispatch دوباره — idempotent
        OrderPaid::dispatch($order);
        $this->assertSame(1, AccountingDocument::query()->where('order_id', $order->id)->where('provider', 'hesabfa')->count(), 'dispatch دوباره نباید سند دوباره بسازد.');
    }

    #[Test]
    #[TestDox('بند ۴ — گاردهای سند فروش: کلیدها خاموش → هیچ؛ provider خاموش → خطا؛ خطای درایور → لاگ failed و عدم شکست سفارش')]
    public function sales_invoice_guards_and_failure_safety(): void
    {
        $this->requireAccounting();
        $user = $this->newUser();
        $product = $this->newProduct();

        // ۱) کلید اصلی خاموش — هیچ اتفاقی نمی‌افتد (حتی تماس به درایور)
        $this->accountingDefaults(['accounting_enabled' => '0']);
        $this->bindFakeDriver();
        $this->bindFakeDriverIntoManager();
        [$order] = $this->newPaidOrder($user, $product);
        OrderPaid::dispatch($order);
        $this->assertSame(0, AccountingDocument::query()->count(), 'با خاموشی پکیج نباید سندی ساخته شود.');

        // ۲) auto_invoice خاموش — خودکار هیچ؛ دستی بله
        $this->accountingDefaults(['auto_invoice_enabled' => '0']);
        [$order2] = $this->newPaidOrder($user, $product);
        OrderPaid::dispatch($order2);
        $this->assertSame(0, AccountingDocument::query()->where('order_id', $order2->id)->count(), 'سند خودکار باید خاموش باشد.');

        $sync = app(SyncService::class);
        $result = $sync->createInvoiceForOrder($order2, manual: true);
        $this->assertTrue($result['ok'], 'سند دستی باید حتی با خاموشی auto کار کند.');
        $this->assertSame(1, AccountingDocument::query()->where('order_id', $order2->id)->count());

        // ۳) خطای درایور — لاگ failed + هیچ استثنا به بیرون
        $this->accountingDefaults();
        $this->bindFakeDriver([], new \RuntimeException('API در دسترس نیست'));
        $this->bindFakeDriverIntoManager();
        [$order3] = $this->newPaidOrder($user, $product);

        OrderPaid::dispatch($order3); // نباید exception بدهد

        $failed = AccountingDocument::query()->where('order_id', $order3->id)->first();
        $this->assertNotNull($failed, 'سند ناموفق باید برای تلاش مجدد ثبت شود.');
        $this->assertSame(AccountingDocument::STATUS_FAILED, $failed->status);
        $this->assertStringContainsString('API در دسترس نیست', (string) $failed->error_message);
        $this->assertSame(1, AccountingSyncLog::query()
            ->where('action', AccountingSyncLog::ACTION_INVOICE)
            ->where('status', 'failed')
            ->where('provider', 'hesabfa')
            ->count());
    }

    /* ================= ۳) موجودی انبار ================= */

    #[Test]
    #[TestDox('بند ۵ — همگام‌سازی موجودی: کشش درایور فیک → تک‌قیمت‌ها به‌روزرسانی، چند‌متغیره رد، کد ناشناس شمرده می‌شود')]
    public function inventory_sync_with_fake_driver(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();

        // محصولات: تک‌قیمت تطبیقی + تک‌قیمت ناشناس + چند‌متغیره
        $matched = $this->newProduct(['product_id' => 'SKU-100']);
        $unknown = $this->newProduct(['product_id' => 'SKU-999']);
        $multiPrice = $this->newProduct(['product_id' => 'SKU-MULTI', 'price_type' => 'attribute-price']);

        // چند‌متغیره: دو قیمت
        $multiPrice->prices()->create([
            'price' => 10000, 'sale_price' => 9000, 'stock' => 50,
        ]);

        $matched->prices()->first()->update(['stock' => 0]);

        $this->bindFakeDriver([
            ['code' => 'SKU-100', 'title' => 'کالای حسابداری', 'stock' => 27],
            ['code' => 'SKU-NOPE', 'title' => 'ناشناس', 'stock' => 5],
            ['code' => 'SKU-MULTI', 'title' => 'چندمتغیره', 'stock' => 9],
        ]);
        $this->bindFakeDriverIntoManager();

        $result = app(SyncService::class)->syncInventory();

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame(1, $result['updated'], 'فقط تک‌قیمت تطبیقی باید به‌روزرسانی شود.');
        $this->assertSame(1, $result['skipped'], 'چند‌متغیره باید رد شود.');
        $this->assertSame(1, $result['unknown'], 'کد ناشناس باید شمرده شود.');

        // موجودی واقعاً به‌روزرسانی شده
        $this->assertSame(27, (int) $matched->prices()->first()->stock);

        // لاگ موفق موجودی با جزئیات
        $log = AccountingSyncLog::query()
            ->where('action', AccountingSyncLog::ACTION_INVENTORY)
            ->where('direction', AccountingSyncLog::DIRECTION_PULL)
            ->where('status', 'success')
            ->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame(1, $log->meta['updated']);
    }

    #[Test]
    #[TestDox('بند ۶ — گاردهای موجودی: inventory_sync خاموش → هیچ؛ inventoryDue با فاصلهٔ حداقلی + کامند با گاردها')]
    public function inventory_guards_and_scheduled_command(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();

        $sync = app(SyncService::class);

        // ۱) خاموشی کلید → inventoryDue false
        AccountingSetting::set('inventory_sync_enabled', '0');
        $this->assertFalse($sync->inventoryDue());

        // ۲) روشن + بدون همگام‌سازی قبلی → نوبت است
        AccountingSetting::set('inventory_sync_enabled', '1');
        $this->assertTrue($sync->inventoryDue());

        // ۳) کامند با گاردها: خاموشی کلید → بدون عملیات
        AccountingSetting::set('inventory_sync_enabled', '0');
        Artisan::call('accounting:sync-inventory');
        $this->assertStringContainsString('غیرفعال', trim(Artisan::output()));

        // ۴) کامند force حتی خارج از نوبت اجرا می‌کند
        AccountingSetting::set('inventory_sync_enabled', '1');
        AccountingSetting::set('accounting_enabled', '0');
        Artisan::call('accounting:sync-inventory', ['--force' => true]);
        $this->assertStringContainsString('غیرفعال', trim(Artisan::output()));
    }

    /* ================= ۴) صفحات ادمین ================= */

    #[Test]
    #[TestDox('بند ۷ — رندر واقعی صفحات ادمین: داشبورد/اسناد/موجودی/لاگ‌ها/تنظیمات/راهنما + POST تنظیمات + سند دستی')]
    public function admin_pages_render_and_post_actions_work(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();
        $this->newCreator();

        // رندر ۶ صفحه
        $this->get(route('admin.accounting.dashboard'))->assertOk();
        $this->get(route('admin.accounting.documents.index'))->assertOk();
        $this->get(route('admin.accounting.inventory.index'))->assertOk();
        $this->get(route('admin.accounting.logs.index'))->assertOk();
        $this->get(route('admin.accounting.settings.edit'))->assertOk();
        $this->get(route('admin.accounting.guide'))->assertOk();

        // تنظیمات شامل فیلدهای کلیدی
        $settingsPage = $this->get(route('admin.accounting.settings.edit'));
        foreach ([
            'accounting_enabled', 'auto_invoice_enabled', 'inventory_sync_enabled',
            'include_shipping_enabled', 'provider', 'hesabfa_enabled',
            'sepidar_enabled', 'holoo_enabled', 'inventory_interval_minutes',
            'invoice_title_prefix',
        ] as $field) {
            $settingsPage->assertSee('name="'.$field.'"', false);
        }

        // ذخیرهٔ POST تنظیمات + اعتبارنامه
        $this->post(route('admin.accounting.settings.update'), [
            'accounting_enabled' => '1',
            'auto_invoice_enabled' => '1',
            'inventory_sync_enabled' => '1',
            'include_shipping_enabled' => '0',
            'hesabfa_enabled' => '1',
            'sepidar_enabled' => '1',
            'holoo_enabled' => '0',
            'provider' => 'sepidar',
            'inventory_interval_minutes' => '90',
            'invoice_title_prefix' => 'فروشگاه تست',
            'credentials' => [
                [
                    'provider' => 'sepidar',
                    'base_url' => 'https://api.sepidar.example/api/v1',
                    'username' => 'sepidar-admin',
                    'password' => 'secret',
                    'default_contact_code' => '55',
                ],
            ],
        ])->assertRedirect(route('admin.accounting.settings.edit'));

        $this->assertSame('sepidar', AccountingSetting::get('provider'));
        $this->assertSame('90', AccountingSetting::get('inventory_interval_minutes'));
        $this->assertSame('فروشگاه تست', AccountingSetting::get('invoice_title_prefix'));
        $this->assertSame('0', AccountingSetting::get('include_shipping_enabled'));

        $connection = AccountingConnection::for('sepidar');
        $this->assertTrue((bool) $connection->enabled);
        $this->assertSame('https://api.sepidar.example/api/v1', $connection->base_url);
        $this->assertSame('sepidar-admin', $connection->credential('username'));
        $this->assertSame('55', $connection->credential('default_contact_code'));

        // سند دستی سفارش — با درایور فیک
        $this->accountingDefaults(['provider' => 'hesabfa']);
        $this->bindFakeDriver(['number' => 'MANUAL-7'], null, 'MANUAL-7');
        $this->bindFakeDriverIntoManager();
        $user = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($user, $product);

        $this->post(route('admin.accounting.documents.store'), ['order_id' => $order->id])
            ->assertRedirect();

        $this->assertSame(1, AccountingDocument::query()->where('order_id', $order->id)->count());
        $this->assertSame(AccountingDocument::STATUS_MANUAL, AccountingDocument::query()->where('order_id', $order->id)->first()->status);

        // گزارش همگام‌سازی با لاگ‌ها
        $this->get(route('admin.accounting.logs.index').'?action=invoice&status=success')->assertOk();
    }

    /* ================= ۵) منو و قرارداد فاز ۸۶ ================= */

    #[Test]
    #[TestDox('بند ۸ — منوی دو-پنلی: ثبت در رجیستری + کلید accounting_enabled + آیتم‌ها با گیت پرمیژن')]
    public function dual_panel_menu_registered_and_toggled(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();
        $this->newCreator(); // برای گیت @can پنل قدیم

        $menus = ModuleMenuRegistry::all();
        $this->assertContains('accounting::back.partials.menu', $menus, 'منوی ادمین حسابداری در رجیستری نیست.');

        // رندر پارشال پنل قدیم — با و بدون کلید اصلی (آیتم داشبورد)
        $menuOn = view('accounting::back.partials.menu', ['apPanel' => 'old'])->render();
        $this->assertStringContainsString('حسابداری', $menuOn);
        $this->assertStringContainsString('داشبورد حسابداری', $menuOn);

        AccountingSetting::set('accounting_enabled', '0');
        $menuOff = view('accounting::back.partials.menu', ['apPanel' => 'old'])->render();
        $this->assertStringNotContainsString('داشبورد حسابداری', $menuOff, 'با خاموشی کلید اصلی، منو باید پنهان شود.');
    }

    #[Test]
    #[TestDox('بند ۹ — قرارداد فاز ۸۶: راهنمای ادمین + امضای کامند + بدون روت فرانت (تصمیم طراحی) + کامپایل ۸ blade')]
    public function phase86_contract_and_blade_compilation(): void
    {
        $this->requireAccounting();

        // راهنمای ادمین روت دارد
        $routes = $this->read('Modules/Accounting/routes/admin.php');
        $this->assertStringContainsString('guide', $routes);

        // حسابداری روت فرانت ندارد (تصمیم طراحی فاز ۹۳ — مستند در RouteServiceProvider)
        $this->assertFileDoesNotExist(base_path('Modules/Accounting/routes/web.php'), 'حسابداری روت فرانت ندارد.');
        $sp = $this->read('Modules/Accounting/app/Providers/AccountingServiceProvider.php');
        $this->assertStringNotContainsString('FrontModuleMenuRegistry', $sp, 'حسابداری منوی مشتری ندارد.');

        // کامپایل و لینت همهٔ bladeها
        foreach ([
            'accounting::back.dashboard.index',
            'accounting::back.documents.index',
            'accounting::back.inventory.index',
            'accounting::back.logs.index',
            'accounting::back.settings.index',
            'accounting::back.guide.index',
            'accounting::back.partials.menu',
            'accounting::errors.license_invalid',
        ] as $view) {
            $this->assertSame(0, $this->compileAndLint($view), "بلید {$view} باید بدون خطای syntax کامپایل شود.");
        }
    }

    #[Test]
    #[TestDox('بند ۱۰ — Manager: resolve نرم‌افزار فعال + گارد کلیدهای {provider}_enabled + عناوین فارسی')]
    public function manager_resolves_active_driver_with_guards(): void
    {
        $this->requireAccounting();
        $this->accountingDefaults();

        $manager = app(AccountingManager::class);

        // فعال پیش‌فرض: hesabfa
        $driver = $manager->driver();
        $this->assertSame('hesabfa', $driver->key());
        $this->assertSame('حسابفا', $manager->titleOf('hesabfa'));

        // کلید خاموش → RuntimeException
        AccountingSetting::set('hesabfa_enabled', '0');
        $this->expectException(\RuntimeException::class);
        $manager->driver();
    }
}
