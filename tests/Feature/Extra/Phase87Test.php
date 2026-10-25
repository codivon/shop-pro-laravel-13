<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\InstalledModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۷ — تولد پکیج مستقل «پرداخت‌یار» (PayYar) + بازگرداندن پکیج قدیمی DigiPay
 * --------------------------------------------------------------------------
 * درخواست‌های کاربر:
 *  ۱) پکیج ترکیب‌شدهٔ درگاه‌ها به‌عنوان یک پکیج «کاملاً جدید» به نام PayYar ساخته شود
 *     — هیچ نامی از DigiPay در آن نباشد، حتی سرویس‌پروایدر (PayYarServiceProvider).
 *  ۲) ریپوی قدیمی DigiPay به «روز قبل» برگردد (برند پرداخت‌یار از آن برداشته شود).
 *  ۳) تصویر شاخص + توضیحات HTML کارد-محور برای MarketingMessaging (CKEditor).
 *
 * این تست‌ها هویت پکیج جدید، مسیرها، جدول‌ها، منو، مهاجرت دادهٔ گاردشده و
 * سازگاری تم (نگاشت payyar در چک‌اوت) را راستی‌آزمایی می‌کنند.
 */
class Phase87Test extends TestCase
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

    private function pyDir(): ?string
    {
        $path = base_path('Modules/PayYar');

        return is_dir($path.'/app') ? $path : null;
    }

    private function requirePayYar(): string
    {
        $path = $this->pyDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول PayYar روی این میزبان نصب نیست — تست فاز ۸۷ رد شد.');
        }

        return $path;
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۸۷',
            'username'   => 'feat87-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat87-'.\Illuminate\Support\Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /* ================= ۱) هویت پکیج جدید PayYar ================= */

    #[Test]
    #[TestDox('بند ۱ — module.json: نام فنی PayYar + عنوان پرداخت‌یار + سرویس‌پروایدر PayYarServiceProvider')]
    public function payyar_manifest_is_fully_renamed(): void
    {
        $dir = $this->requirePayYar();

        $manifest = json_decode((string) file_get_contents($dir.'/module.json'), true);

        $this->assertSame('PayYar', $manifest['name'] ?? null);
        $this->assertSame('payyar', $manifest['alias'] ?? null);
        $this->assertSame('پرداخت‌یار', $manifest['title'] ?? null);
        $this->assertSame('1.0.0', $manifest['version'] ?? null);
        $this->assertSame(
            ['Modules\\PayYar\\Providers\\PayYarServiceProvider'],
            $manifest['providers'] ?? null
        );

        // هیچ نامی از پکیج قبلی در مانیفست نباشد
        $raw = (string) file_get_contents($dir.'/module.json');
        $this->assertStringNotContainsString('DigiPay', $raw);
        $this->assertStringNotContainsString('دیجی‌پی', $raw);
    }

    #[Test]
    #[TestDox('بند ۱ — هیچ ارجاع DigiPay در هویت پکیج جدید نیست (فقط آدرس‌های API و مایگریشن مهاجرت داده مجاز)')]
    public function payyar_codebase_has_no_legacy_references(): void
    {
        $dir = $this->requirePayYar();

        $allowedUrlHosts = ['api.mydigipay.com', 'sandbox.api.mydigipay.com'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        $checked = 0;

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path  = $file->getPathname();
            $ext   = strtolower($file->getExtension());
            $human = ['featured-', '.png', '.woff2'];

            // فایل‌های باینری/تصویر بررسی نمی‌شوند
            if ($ext === 'png' || $ext === 'woff2' || str_starts_with(basename($path), 'featured-')) {
                continue;
            }

            /*
             * استثناهای موجه:
             *  - مایگریشن مهاجرت داده: باید جدول‌های قدیمی digipay_* را بخواند (وظیفه‌اش همین است)
             *  - مستندات (md): روایت تاریخی تولد پکیج از پکیج قبلی
             */
            if (str_contains($path, 'migrate_legacy') || $ext === 'md') {
                continue;
            }

            $content = (string) file_get_contents($path);
            $checked++;

            if (preg_match_all('/digipay/i', $content, $m)) {
                // هر نمونه باید داخل یک آدرس API مجاز باشد
                foreach ($m[0] as $hit) {
                    $pos = stripos($content, $hit);
                    $window = substr($content, max(0, $pos - 60), 140);

                    $ok = false;
                    foreach ($allowedUrlHosts as $host) {
                        if (stripos($window, $host) !== false) {
                            $ok = true;
                            break;
                        }
                    }

                    $this->assertTrue(
                        $ok,
                        'ارجاع غیرمجاز به نام قبلی در: '.$path.' → '.$window
                    );
                }
            }
        }

        $this->assertGreaterThan(30, $checked, 'تعداد فایل‌های بررسی‌شده خیلی کم است.');
    }

    #[Test]
    #[TestDox('بند ۱ — کلاس‌ها/جدول‌ها/مسیرها/دستورها همگی با هویت payyar هستند')]
    public function payyar_structures_use_new_identity(): void
    {
        $dir = $this->requirePayYar();

        // کلاس‌ها
        $this->assertFileExists($dir.'/app/Providers/PayYarServiceProvider.php');
        $this->assertFileExists($dir.'/app/Services/PayYarService.php');
        $this->assertFileExists($dir.'/app/Models/PayYarSetting.php');
        $this->assertFileExists($dir.'/app/Models/PayYarTransaction.php');
        $this->assertFileExists($dir.'/app/Http/Controllers/PayYarController.php');
        $this->assertFileExists($dir.'/app/Http/Controllers/Back/PayYarSettingsController.php');

        // جدول‌ها در مایگریشن‌های ساخت — مایگریشن مهاجرت داده استثناست (وظیفه‌اش خواندن جدول‌های قدیمی است)
        $createMigrations = '';
        foreach (glob($dir.'/database/migrations/*.php') ?: [] as $f) {
            if (! str_contains($f, 'migrate_legacy')) {
                $createMigrations .= (string) file_get_contents($f)."\n";
            }
        }
        $this->assertStringContainsString('payyar_settings', $createMigrations);
        $this->assertStringContainsString('payyar_transactions', $createMigrations);
        $this->assertStringNotContainsString('digipay_', $createMigrations);

        // روت‌ها — پیشوند مسیر و پیشوند نام‌گذاری با هویت جدید
        $routes = (string) file_get_contents($dir.'/routes/admin.php');
        $this->assertStringContainsString("'/payyar'", $routes);
        $this->assertStringContainsString("name('admin.payyar.')", $routes);
        $this->assertStringContainsString("'guide'", $routes);

        // دستورها
        $commands = (string) file_get_contents($dir.'/app/Console/Commands/InstallPermissionsCommand.php');
        $this->assertStringContainsString('payyar:install-permissions', $commands);
    }

    #[Test]
    #[TestDox('بند ۱ — بوت واقعی ماژول: ۱۴ روت payyar + کانفیگ + ویوها + منو ثبت شده‌اند')]
    public function payyar_module_boots_with_routes_views_menu(): void
    {
        $this->requirePayYar();

        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($r) => str_contains($r->getName() ?? '', 'payyar'));
        $this->assertSame(14, $routes->count(), 'روت‌های پکیج PayYar کامل ثبت نشده‌اند.');
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.payyar.guide'));

        $this->assertSame('PayYar', config('payyar.name'));
        $this->assertTrue(view()->exists('payyar::back.settings.index'));
        $this->assertTrue(view()->exists('payyar::back.partials.menu'));
        $this->assertTrue(view()->exists('payyar::back.guide.index'));

        // منو در رجیستری سراسری ثبت شده باشد
        $this->assertContains('payyar::back.partials.menu', \App\Services\ModuleMenuRegistry::all());
    }

    /* ================= ۲) مهاجرت دادهٔ گاردشده از پکیج قدیمی ================= */

    #[Test]
    #[TestDox('بند ۲ — مهاجرت گاردشده: تنظیمات و تراکنش‌های پکیج قدیمی به payyar منتقل و idempotent است')]
    public function legacy_data_migration_copies_and_is_idempotent(): void
    {
        $this->requirePayYar();

        if (! \Illuminate\Support\Facades\Schema::hasTable('payyar_settings')) {
            $this->markTestSkipped('جدول payyar_settings ساخته نشده — مایگریشن‌ها اجرا نشده‌اند.');
        }

        // شبیه‌سازی نصب قدیمی: جدول‌های digipay_* با داده
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS digipay_settings');
        \Illuminate\Support\Facades\DB::statement(
            'CREATE TABLE digipay_settings (id INT AUTO_INCREMENT PRIMARY KEY, `key` VARCHAR(191) NOT NULL, `value` TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)'
        );
        \Illuminate\Support\Facades\DB::table('digipay_settings')->insert([
            ['key' => 'client_id', 'value' => 'legacy-cid-87', 'created_at' => now(), 'updated_at' => now()],
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('payyar_transactions')) {
            \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS digipay_transactions');
            \Illuminate\Support\Facades\DB::statement('CREATE TABLE digipay_transactions LIKE payyar_transactions');
            \Illuminate\Support\Facades\DB::table('digipay_transactions')->insert([
                'track_id'   => 'TRK-87-LEGACY',
                'amount'     => 123000,
                'status'     => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // پاک‌سازی مقصد + رکورد مایگریشن تا دوباره اجرا شود
        \Illuminate\Support\Facades\DB::table('payyar_settings')->where('key', 'client_id')->delete();
        \Illuminate\Support\Facades\DB::table('payyar_transactions')->where('track_id', 'TRK-87-LEGACY')->delete();
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%migrate_legacy%')->delete();

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        // ۱) کپی شده
        $this->assertSame(
            'legacy-cid-87',
            \Modules\PayYar\Models\PayYarSetting::get('client_id'),
            'تنظیمات پکیج قدیمی باید به payyar_settings منتقل شده باشد.'
        );

        if (\Illuminate\Support\Facades\Schema::hasTable('payyar_transactions')) {
            $this->assertSame(
                1,
                \Illuminate\Support\Facades\DB::table('payyar_transactions')->where('track_id', 'TRK-87-LEGACY')->count(),
                'تراکنش قدیمی باید کپی شده باشد.'
            );
        }

        // ۲) idempotent — اجرای مجدد دوباره‌کاری نمی‌کند
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%migrate_legacy%')->delete();
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        $this->assertSame(
            1,
            \Illuminate\Support\Facades\DB::table('payyar_settings')->where('key', 'client_id')->count(),
            'اجرای مجدد مایگریشن نباید ردیف تکراری بسازد.'
        );

        // پاک‌سازی (توجه: DDL در MySQL باعث implicit commit می‌شود؛ رکوردهای
        // مایگریشن جدول‌های قدیمی هم حذف می‌شوند تا migrate بعدی دوباره بسازد)
        \Illuminate\Support\Facades\DB::table('payyar_settings')->where('key', 'client_id')->delete();
        \Illuminate\Support\Facades\DB::table('payyar_transactions')->where('track_id', 'TRK-87-LEGACY')->delete();
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS digipay_settings');
        \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS digipay_transactions');
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%create_digipay_%')->delete();
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%digipay_transactions_table%')->delete();
    }

    /* ================= ۳) صفحات ادمین + منو ================= */

    #[Test]
    #[TestDox('بند ۳ — صفحهٔ تنظیمات پرداخت‌یار در پنل جدید رندر می‌شود و برند در سربرگ است')]
    public function payyar_settings_page_renders(): void
    {
        $dir = $this->requirePayYar();

        $this->actingAs($this->newCreator(), 'adminPanel');

        // در تست (runningInConsole=true) گیت‌های پرمیژن از DB تعریف نمی‌شوند — مثل الگوی Phase85 باز می‌شوند
        \Illuminate\Support\Facades\Gate::define('payyar', fn ($user = null) => true);
        \Illuminate\Support\Facades\Gate::define('payyar.settings', fn ($user = null) => true);
        \Illuminate\Support\Facades\Gate::define('payyar.transactions', fn ($user = null) => true);

        $response = $this->get(route('admin.payyar.settings'));
        $response->assertOk();

        $html = (string) $response->getContent();
        $this->assertStringContainsString('تنظیمات پرداخت‌یار', $html);
        $this->assertStringContainsString('اسنپ‌پی', $html);
        $this->assertStringContainsString('pys-scope', $html);

        // برند منو (دو-پنلی) داخل خود پکیج
        $menu = (string) file_get_contents($dir.'/resources/views/back/partials/menu.blade.php');
        $this->assertStringContainsString('پرداخت‌یار | درگاه‌های پرداخت', $menu);
        $this->assertStringContainsString('راهنما و آموزش', $menu);
    }

    #[Test]
    #[TestDox('بند ۳ — گروه منوی «پکیج‌ها» در سایدبار پنل جدید شامل پرداخت‌یار است')]
    public function payyar_menu_group_appears_in_new_panel_sidebar(): void
    {
        $this->requirePayYar();

        // رکورد نصب (همان کاری که نصب‌کنندهٔ بازار می‌کند) — گیت لایسنس در وب بدون این رکورد منو را ثبت نمی‌کند
        InstalledModule::firstOrCreate(
            ['slug' => 'payyar'],
            [
                'name'         => 'PayYar',
                'version'      => '1.0.0',
                'installed_at' => now(),
                'status'       => 'installed',
                'is_active'    => true,
                'license_expires_at' => null,
            ]
        );

        $this->actingAs($this->newCreator(), 'adminPanel');

        // در تست گیت‌ها از DB تعریف نمی‌شوند (runningInConsole) — مثل الگوی Phase85 باز می‌شوند
        \Illuminate\Support\Facades\Gate::define('packages', fn ($user = null) => true);
        \Illuminate\Support\Facades\Gate::define('payyar', fn ($user = null) => true);
        \Illuminate\Support\Facades\Gate::define('payyar.settings', fn ($user = null) => true);
        \Illuminate\Support\Facades\Gate::define('payyar.transactions', fn ($user = null) => true);

        $response = $this->get(route('np.dashboard'));
        $html = (string) $response->getContent();

        $this->assertStringContainsString('پرداخت‌یار | درگاه‌های پرداخت', $html, 'گروه منوی پرداخت‌یار در سایدبار پنل جدید رندر نشده است.');
        $this->assertStringContainsString('admin/admin/payyar/settings', $html);
        $this->assertStringContainsString('admin/admin/payyar/transactions', $html);
        $this->assertStringContainsString('admin/admin/payyar/guide', $html);
    }

    #[Test]
    #[TestDox('بند ۳ — منوی پرداخت‌یار در صفحهٔ تنظیمات خودش (زیرصفحهٔ هم‌خانواده) فعال می‌ماند')]
    public function payyar_menu_active_state_on_subpages(): void
    {
        $this->requirePayYar();

        $method = new \ReflectionMethod(\App\Services\ModuleMenuExtractor::class, 'urlIsActive');
        $method->setAccessible(true);

        // زیرصفحهٔ create/ویرایش — آیتم والد باید فعال بماند (درخواست فاز ۸۶)
        app()->instance('request', \Illuminate\Http\Request::create('/admin/admin/payyar/settings/create', 'GET'));
        $this->assertTrue(
            $method->invoke(null, 'http://x.test/admin/admin/payyar/settings'),
            'آیتم منو در زیرصفحهٔ create باید فعال بماند.'
        );

        // عینِ مسیر
        app()->instance('request', \Illuminate\Http\Request::create('/admin/admin/payyar/settings', 'GET'));
        $this->assertTrue($method->invoke(null, 'http://x.test/admin/admin/payyar/settings'));

        // مرز سگمنت: صفحهٔ پکیج دیگر آیتم پرداخت‌یار را روشن نمی‌کند
        app()->instance('request', \Illuminate\Http\Request::create('/admin/admin/marketing/campaigns/create', 'GET'));
        $this->assertFalse($method->invoke(null, 'http://x.test/admin/admin/payyar/settings'));
    }

    /* ================= ۴) پکیج قدیمی DigiPay به روز قبل برگشته ================= */

    #[Test]
    #[TestDox('بند ۴ — پکیج قدیمی DigiPay (v1.4.0) بدون برند پرداخت‌یار است و ساختار قبلی خودش را دارد')]
    public function legacy_digipay_package_is_reverted(): void
    {
        $path = base_path('Modules/DigiPay');

        if (! is_dir($path.'/app')) {
            $this->markTestSkipped('ماژول DigiPay روی این میزبان نصب نیست — تست فاز ۸۷ رد شد.');
        }

        $manifest = json_decode((string) file_get_contents($path.'/module.json'), true);

        // نسخهٔ برگشتی = 1.4.0 (روز قبل از برندینگ پرداخت‌یار)
        $this->assertSame('1.4.0', $manifest['version'] ?? null, 'پکیج DigiPay باید به نسخهٔ 1.4.0 برگردانده شود.');
        $this->assertSame('DigiPay', $manifest['name'] ?? null);
        // کلید title (برند پرداخت‌یار) از فاز ۸۶ باید حذف شده باشد
        $this->assertArrayNotHasKey('title', $manifest, 'برند پرداخت‌یار باید از پکیج قدیمی برداشته شود.');
        $this->assertStringNotContainsString('پرداخت‌یار', (string) file_get_contents($path.'/module.json'));
    }

    /* ================= ۵) سازگاری تم (نگاشت چک‌اوت) ================= */

    #[Test]
    #[TestDox('بند ۵ — تم شاپ‌پرو نگاشت payyar→PayYarController را در پرداخت ماژولار دارد (کنار digipay قدیمی)')]
    public function theme_payment_gateway_map_includes_payyar(): void
    {
        $controller = (string) file_get_contents(
            base_path('themes/WeblakShop/src/Controllers/OrderController.php')
        );

        $this->assertStringContainsString("'payyar'", $controller);
        $this->assertStringContainsString("\Modules\PayYar\Http\Controllers\PayYarController::class", $controller);
        $this->assertStringContainsString("\Modules\PayYar\Http\Controllers\SnappPayController::class", $controller);
        // سازگاری قدیمی‌ها حفظ شده
        $this->assertStringContainsString("'digipay' =>", $controller);

        $request = (string) file_get_contents(
            base_path('themes/WeblakShop/src/Requests/StoreOrderRequest.php')
        );
        $this->assertStringContainsString("module_is_active('PayYar')", $request);
        $this->assertStringContainsString("'payyar'", $request);
    }

    /* ================= ۶) تصویر شاخص + توضیحات بازار مارکتینگ ================= */

    #[Test]
    #[TestDox('بند ۶ — MarketingMessaging: توضیحات بازار کارد-محور (CKEditor-safe) + تصویر شاخص داخل پکیج')]
    public function marketing_market_description_and_featured_image_exist(): void
    {
        $dir = base_path('Modules/MarketingMessaging');

        if (! is_dir($dir.'/app')) {
            $this->markTestSkipped('ماژول MarketingMessaging روی این میزبان نصب نیست — تست فاز ۸۷ رد شد.');
        }

        $this->assertFileExists($dir.'/docs/market-description.html');

        $featured = glob($dir.'/featured*');
        $featured = array_values(array_filter($featured, fn ($f) => is_file($f) && preg_match('/\.(png|jpe?g|webp)$/i', $f)));
        $this->assertNotEmpty($featured, 'تصویر شاخص در ریشهٔ پکیج مارکتینگ نیست.');
        $this->assertGreaterThan(20 * 1024, filesize($featured[0]), 'تصویر شاخص خیلی کوچک است.');

        $html = (string) file_get_contents($dir.'/docs/market-description.html');

        $this->assertStringContainsString('مارکتینگ و پیام‌رسانی', $html);
        $this->assertStringContainsString('dir="rtl"', $html);            // RTL
        $this->assertStringContainsString('linear-gradient', $html);      // کارت هیرو گرادیانی
        $this->assertStringNotContainsString('<script', $html);           // CKEditor-safe
        $this->assertStringNotContainsString('<link', $html);             // بدون فایل خارجی
        $this->assertStringNotContainsString('<style', $html);            // همهٔ استایل‌ها inline

        // قابلیت‌های کلیدی پکیج در توضیحات آمده باشد
        $this->assertStringContainsString('سبد رهاشده', $html);
        $this->assertStringContainsString('کمپین', $html);
    }
}
