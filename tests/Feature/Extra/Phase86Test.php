<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\InstalledModule;
use App\Models\PackageCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۶ — هفت درخواست کاربر:
 *
 *  ۱) جدول «ماژول‌های نصب‌شده» عنوان ماژول را نشان دهد (نه نام فنی):
 *     زنجیرهٔ حل: title داخل module.json خود پکیج → نام کش بازار → نام فنی
 *  ۲) پکیج ادغامی درگاه‌ها به‌عنوان پکیج جدید «PayYar (پرداخت‌یار)» ساخته شد (فاز ۸۷)
 *  ۳) توضیحات بازار HTML کارد-محور + تصویر شاخص برای پرداخت‌یار
 *  ۴) بازطراحی مدرن همهٔ صفحات ادمین مارکتینگ (دیزاین‌سیستم mm-)
 *  ۵) رفع ارور «Unclosed '(' does not match '}'» در create کمپین
 *     (اکوی تودرتوی blade — متغیرهای الگو باید بیرون اکو ساخته شوند)
 *  ۶) منوی «راهنما و آموزش» در هر دو پکیج جدید (روت + ویو + آیتم منو)
 *  ۷) فعال‌ماندن آیتم منوی پکیج در صفحات افزودن/ویرایش/جزئیات
 *     (ModuleMenuExtractor: عینِ مسیر یا زیرمجموعهٔ سگمنت‌محور)
 *
 * فلسفهٔ اجرا (الگوی فاز ۸۵): روی میزبانی بدون ماژول‌ها با پیام روشن Skip
 * می‌شود؛ روی میزبانِ نصب‌شده راستی‌آزمایی کامل است.
 */
class Phase86Test extends TestCase
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

    private function mmDir(): ?string
    {
        $path = base_path('Modules/MarketingMessaging');

        return is_dir($path.'/app') ? $path : null;
    }

    private function pyDir(): ?string
    {
        $path = base_path('Modules/PayYar');

        return is_dir($path.'/app') ? $path : null;
    }

    private function requireMM(): string
    {
        $path = $this->mmDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول MarketingMessaging روی این میزبان نصب نیست — تست فاز ۸۶ رد شد.');
        }

        return $path;
    }

    private function requirePayYar(): string
    {
        $path = $this->pyDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول PayYar روی این میزبان نصب نیست — تست فاز ۸۶ رد شد.');
        }

        return $path;
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۸۶',
            'username'   => 'feat86-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat86-'.\Illuminate\Support\Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /** بستن Request با مسیر دلخواه در کانتینر (برای محاسبهٔ active سایدبار) */
    private function setRequestPath(string $path): void
    {
        app()->instance('request', Request::create($path, 'GET'));
    }

    /** فراخوانی متد محافظت‌شدهٔ static آیتمِ فعال (بازتاب) */
    private function callUrlIsActive(string $url): bool
    {
        $method = new \ReflectionMethod(\App\Services\ModuleMenuExtractor::class, 'urlIsActive');
        $method->setAccessible(true);

        return (bool) $method->invoke(null, $url);
    }

    /** کامپایل blade یک ویو + lint نتیجه با php -l؛ خروجی [compiled, exitCode] */
    private function compileAndLint(string $viewName): array
    {
        $path     = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'mm86-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return [$compiled, $exitCode];
    }

    /* ================= ۱) عنوان ماژول در جدول نصب‌شده‌ها ================= */

    #[Test]
    #[TestDox('بند ۱ — زنجیرهٔ عنوان: module.json پکیج PayYar → «پرداخت‌یار» (پیش از کش بازار)')]
    public function display_name_prefers_module_json_title(): void
    {
        $this->requirePayYar();

        // رکورد نصب (همان کاری که نصب‌کنندهٔ بازار می‌کند) — firstOrCreate تا تست تخریب نکند
        $module = InstalledModule::firstOrCreate(
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

        // کلید title داخل module.json پکیج = هویت رسمی داخل خود پکیج
        $this->assertSame('پرداخت‌یار', InstalledModule::moduleJsonTitle('PayYar'));
        $this->assertSame('پرداخت‌یار', $module->displayName());

        // حتی اگر کش بازار نام دیگری داشته باشد، عنوانِ داخل پکیج مقدم است
        $cache = PackageCache::updateOrCreate(
            ['slug' => 'payyar'],
            ['name' => 'نام کهنهٔ بازار', 'latest_version' => '1.0.0', 'fetched_at' => now()]
        );

        $this->assertSame('پرداخت‌یار', $module->fresh()->displayName());

        $cache->delete();
    }

    #[Test]
    #[TestDox('بند ۱ — زنجیرهٔ عنوان برای پکیج بدون کلید title: کش بازار → نام فنی')]
    public function display_name_falls_back_to_cache_then_name(): void
    {
        $cache = PackageCache::updateOrCreate(
            ['slug' => 'checkout-turbo'],
            ['name' => 'چک‌اوت توربو', 'latest_version' => '1.0.0', 'fetched_at' => now()]
        );

        $withCache = InstalledModule::firstOrCreate(
            ['slug' => 'checkout-turbo'],
            [
                'name'         => 'CheckoutTurbo',
                'version'      => '1.0.0',
                'installed_at' => now(),
                'status'       => 'installed',
                'is_active'    => true,
                'license_expires_at' => null,
            ]
        );

        // پکیجی بدون کلید title در module.json → نام کش بازار
        $this->assertNull(InstalledModule::moduleJsonTitle('CheckoutTurbo'));
        $this->assertSame('چک‌اوت توربو', $withCache->displayName());

        $withCache->delete();
        $cache->delete();

        // نه module.json نه کش → نام فنی (رفتار قدیمی حفظ می‌شود)
        $bare = InstalledModule::create([
            'slug'         => 'unknown-module-x',
            'name'         => 'UnknownModuleX',
            'version'      => '0.0.0',
            'installed_at' => now(),
            'status'       => 'installed',
            'is_active'    => true,
            'license_expires_at' => null,
        ]);

        $this->assertSame('UnknownModuleX', $bare->displayName());
    }

    #[Test]
    #[TestDox('بند ۱ — هر دو جدول نصب‌شده‌ها (پنل جدید و قدیمی) displayName را نمایش می‌دهند')]
    public function installed_tables_render_display_name(): void
    {
        $newPanel = (string) file_get_contents(
            resource_path('views/admin-panel/livewire/admin/packages/installed.blade.php')
        );
        $oldPanel = (string) file_get_contents(
            resource_path('views/back/packages/installed.blade.php')
        );

        $this->assertStringContainsString('$module->displayName()', $newPanel);
        $this->assertStringContainsString('$module->displayName()', $oldPanel);
    }

    /* ================= ۷) فعال‌ماندن منو در صفحات افزودن/ویرایش ================= */

    #[Test]
    #[TestDox('بند ۷ — آیتم منو در زیرصفحه‌ها (create/show/refresh) هم فعال می‌ماند')]
    public function menu_item_stays_active_on_subpages(): void
    {
        $item = '/admin/admin/marketing/campaigns';

        // عینِ مسیر
        $this->setRequestPath($item);
        $this->assertTrue($this->callUrlIsActive($item));

        // صفحهٔ افزودن (درخواست کاربر)
        $this->setRequestPath($item.'/create');
        $this->assertTrue($this->callUrlIsActive($item));

        // صفحهٔ جزئیات با شناسه
        $this->setRequestPath($item.'/5');
        $this->assertTrue($this->callUrlIsActive($item));

        // مسیرهای تو در توی اجرای عملیات
        $this->setRequestPath($item.'/5/send');
        $this->assertTrue($this->callUrlIsActive($item));
    }

    #[Test]
    #[TestDox('بند ۷ — مرز سگمنت رعایت می‌شود (آیتم‌های هم‌پیشوند اشتباهی فعال نمی‌شوند)')]
    public function segment_boundary_is_respected(): void
    {
        $this->setRequestPath('/admin/admin/marketing/campaigns-other');
        $this->assertFalse($this->callUrlIsActive('/admin/admin/marketing/campaigns'));

        $this->setRequestPath('/admin/admin/marketing/campaignsarchive/list');
        $this->assertFalse($this->callUrlIsActive('/admin/admin/marketing/campaigns'));

        // و برعکس: آیتمِ عمیق‌تر روی مسیر والد فعال نیست
        $this->setRequestPath('/admin/admin/marketing/campaigns');
        $this->assertFalse($this->callUrlIsActive('/admin/admin/marketing/campaigns/create'));

        // آیتم‌های پکیج دیگر روی صفحات این پکیج فعال نمی‌شوند
        $this->setRequestPath('/admin/admin/marketing/campaigns/create');
        $this->assertFalse($this->callUrlIsActive('/admin/admin/digipay/settings'));
    }

    /* ================= ۵) رفع ارور blade (اکوی تودرتو) ================= */

    #[Test]
    #[TestDox('بند ۵ — همهٔ ویوهای back پکیج مارکتینگ بدون خطای پارس PHP کامپایل می‌شوند')]
    public function all_marketing_views_compile_without_php_parse_errors(): void
    {
        $this->requireMM();

        $views = [
            'marketing-messaging::back.campaigns.index',
            'marketing-messaging::back.campaigns.create',
            'marketing-messaging::back.campaigns.show',
            'marketing-messaging::back.segments.index',
            'marketing-messaging::back.segments.create',
            'marketing-messaging::back.abandoned.index',
            'marketing-messaging::back.settings.index',
            'marketing-messaging::back.guide.index',
            'marketing-messaging::back.partials.menu',
        ];

        foreach ($views as $view) {
            [, $exitCode] = $this->compileAndLint($view);

            $this->assertSame(
                0,
                $exitCode,
                "کامپایل/پارس ویو {$view} شکست خورد — احتمال اکوی تودرتوی blade."
            );
        }
    }

    #[Test]
    #[TestDox('بند ۵ — فرم ساخت کمپین (نسخهٔ پترن‌محور فاز ۸۷ب): کامپایل سالم + فیلد کد پترن بدون اکوی تودرتوی الگو')]
    public function template_variables_survive_blade_compilation(): void
    {
        $this->requireMM();

        [$compiled, $exitCode] = $this->compileAndLint('marketing-messaging::back.campaigns.create');
        $this->assertSame(0, $exitCode, 'کامپایل/پارس فرم ساخت کمپین شکست خورد.');

        // فاز ۸۷ب — به‌جای «متن مرجع پیام»، فیلد «کد پترن پیامک» آمده است
        $this->assertStringContainsString('pattern_code', $compiled);

        // نشانهٔ قطع‌شدن تطبیق (اکوی نیمه‌کاره) نباید در خروجی باشد
        $this->assertStringNotContainsString("?? 'مثال:", $compiled);
    }

    #[Test]
    #[TestDox('بند ۵ — هارنس تست خودش الگوی معیوب قدیمی را می‌گیرد (تست جهش)')]
    public function test_harness_catches_the_original_broken_pattern(): void
    {
        // عین الگوی ویوی معیوب قدیمی — باید پارس PHP شکست بخورد
        $broken = 'placeholder="<?php echo e(old(\'message\') ?? \'مثال: {{name); ?>';

        $tmp = tempnam(sys_get_temp_dir(), 'mm86-broken-').'.php';
        file_put_contents($tmp, $broken);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        $this->assertNotSame(0, $exitCode, 'الگوی معیوب باید توسط php -l رد شود.');
    }

    /* ================= ۴) بازطراحی مدرن صفحات ادمین مارکتینگ ================= */

    #[Test]
    #[TestDox('بند ۴ — دیزاین‌سیستم mm- (CSS اسکوپ‌شده + سلف‌هاست آیکون) داخل خود پکیج است')]
    public function marketing_design_system_assets_exist(): void
    {
        $dir = $this->requireMM();

        $this->assertFileExists($dir.'/resources/assets/css/mm-admin.css');
        $this->assertFileExists($dir.'/resources/assets/css/compat.css');
        $this->assertFileExists($dir.'/resources/assets/css/compat-fa.css');
        $this->assertFileExists($dir.'/resources/assets/css/compat-dark-base.css');
        $this->assertFileExists($dir.'/resources/assets/webfonts/fa-solid-900.woff2');

        $css = (string) file_get_contents($dir.'/resources/assets/css/mm-admin.css');
        $this->assertStringContainsString('.mm-scope', $css);
        $this->assertStringContainsString('html.dark .mm-scope', $css); // دارک‌مود

        // ویوها CSS و ریشهٔ اسکوپ را لود می‌کنند
        foreach (['campaigns/index', 'campaigns/create', 'campaigns/show', 'segments/index', 'segments/create', 'abandoned/index', 'settings/index'] as $rel) {
            $src = (string) file_get_contents($dir.'/resources/views/back/'.$rel.'.blade.php');
            $this->assertStringContainsString("module_asset('MarketingMessaging', 'css/mm-admin.css')", $src, $rel);
            $this->assertStringContainsString('mm-scope', $src, $rel);
        }
    }

    /* ================= ۶) منوی «راهنما و آموزش» ================= */

    #[Test]
    #[TestDox('بند ۶ — روت + ویو + آیتم منوی «راهنما و آموزش» در هر دو پکیج ثبت است')]
    public function guide_route_view_and_menu_exist_in_both_packages(): void
    {
        $this->requireMM();
        $this->requirePayYar();

        $this->assertTrue(Route::has('admin.marketing.guide'), 'روت راهنمای مارکتینگ ثبت نشده است.');
        $this->assertTrue(Route::has('admin.payyar.guide'), 'روت راهنمای پرداخت‌یار ثبت نشده است.');

        $this->assertTrue(view()->exists('marketing-messaging::back.guide.index'));
        $this->assertTrue(view()->exists('payyar::back.guide.index'));

        $mmMenu = (string) file_get_contents($this->mmDir().'/resources/views/back/partials/menu.blade.php');
        $pyMenu = (string) file_get_contents($this->pyDir().'/resources/views/back/partials/menu.blade.php');

        $this->assertStringContainsString('راهنما و آموزش', $mmMenu);
        $this->assertStringContainsString('admin.marketing.guide', $mmMenu);
        $this->assertStringContainsString('راهنما و آموزش', $pyMenu);
        $this->assertStringContainsString('admin.payyar.guide', $pyMenu);
    }

    #[Test]
    #[TestDox('بند ۶ — صفحهٔ راهنمای مارکتینگ رندر می‌شود: «این پکیج چه‌کاری انجام می‌دهد؟»')]
    public function marketing_guide_page_renders(): void
    {
        $this->requireMM();

        // creator پایهٔ گیت پرمیژن نیست — گیتِ منو/روت برای تست باز می‌شود
        \Illuminate\Support\Facades\Gate::define('marketing', fn ($user = null) => true);

        $this->actingAs($this->newCreator(), 'adminPanel');

        $response = $this->get(route('admin.marketing.guide'));

        $response->assertOk();
        $response->assertSee('این پکیج چه‌کاری انجام می‌دهد؟');
        $response->assertSee('شروع سریع');
        $response->assertSee('سوالات پرتکرار');
    }

    #[Test]
    #[TestDox('بند ۶ — صفحهٔ راهنمای پرداخت‌یار (پکیج PayYar) رندر می‌شود با برند جدید')]
    public function payyar_guide_page_renders(): void
    {
        $this->requirePayYar();

        $this->actingAs($this->newCreator(), 'adminPanel');

        $response = $this->get(route('admin.payyar.guide'));

        $response->assertOk();
        $response->assertSee('پرداخت‌یار');
        $response->assertSee('شروع سریع');
    }

    /* ================= ۲) نام و عنوان جدید پکیج ادغامی ================= */

    #[Test]
    #[TestDox('بند ۲ — پرداخت‌یار (پکیج جدید PayYar): module.json (title + نسخهٔ ۱.۰) و برند در سربرگ‌ها و منو')]
    public function payyar_brand_is_applied_across_digipay_package(): void
    {
        $dir = $this->requirePayYar();

        $manifest = json_decode((string) file_get_contents($dir.'/module.json'), true);
        $this->assertSame('پرداخت‌یار', $manifest['title'] ?? null, 'کلید title در module.json.');
        $this->assertSame('1.0.0', $manifest['version'] ?? null);
        // نام فنی ماژول جدید = PayYar (بدون نام پکیج قدیمی)
        $this->assertSame('PayYar', $manifest['name'] ?? null);

        $menu = (string) file_get_contents($dir.'/resources/views/back/partials/menu.blade.php');
        $this->assertStringContainsString('پرداخت‌یار | درگاه‌های پرداخت', $menu);

        $settings = (string) file_get_contents($dir.'/resources/views/back/settings/index.blade.php');
        $this->assertStringContainsString('تنظیمات پرداخت‌یار', $settings);

        $transactions = (string) file_get_contents($dir.'/resources/views/back/transactions/index.blade.php');
        $this->assertStringContainsString('تراکنش‌های پرداخت‌یار', $transactions);
    }

    /* ================= ۳) توضیحات بازار + تصویر شاخص ================= */

    #[Test]
    #[TestDox('بند ۳ — توضیحات بازار HTML کارد-محور + تصویر شاخص پرداخت‌یار داخل پکیج')]
    public function payyar_market_description_and_featured_image_exist(): void
    {
        $dir = $this->requirePayYar();

        $this->assertFileExists($dir.'/docs/market-description.html');
        $this->assertFileExists($dir.'/featured-payyar.png');
        $this->assertGreaterThan(20 * 1024, filesize($dir.'/featured-payyar.png'), 'تصویر شاخص خیلی کوچک است.');

        $html = (string) file_get_contents($dir.'/docs/market-description.html');

        $this->assertStringContainsString('پرداخت‌یار', $html);
        $this->assertStringContainsString('dir="rtl"', $html);           // RTL
        $this->assertStringContainsString('اسنپ‌پی', $html);              // هر دو درگاه
        $this->assertStringContainsString('linear-gradient', $html);      // کارت هیرو گرادیانی
        $this->assertStringNotContainsString('<script', $html);           // CKEditor-safe
        // همهٔ استایل‌ها inline (بدون وابستگی به فایل/تگ استایل خارجی)
        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringNotContainsString('<style', $html);
    }

    /* ================= ۶-مکمل) نسخهٔ پکیج مارکتینگ ================= */

    #[Test]
    #[TestDox('پکیج مارکتینگ: نسخهٔ ۱.۳.۰ با هویت ثابت (فاز ۸۷ب/۸۸ — پترن‌محور شدن)')]
    public function marketing_version_bumped(): void
    {
        $dir = $this->requireMM();

        $manifest = json_decode((string) file_get_contents($dir.'/module.json'), true);
        $this->assertGreaterThanOrEqual('1.3.0', $manifest['version'] ?? null, 'نسخهٔ پکیج مارکتینگ باید ۱.۳.۰ یا بالاتر باشد.');
        $this->assertSame('MarketingMessaging', $manifest['name'] ?? null);
        $this->assertSame('مارکتینگ و پیام‌رسانی', $manifest['title'] ?? null);
    }

    #[Test]
    #[TestDox('باگ مرورگری — ایمپورت Models\Segment در SegmentBuilder (هینت تابع calculate)')]
    public function segment_builder_imports_the_model_segment(): void
    {
        $dir = $this->requireMM();

        $source = (string) file_get_contents($dir.'/app/Services/SegmentBuilder.php');

        // بدون این ایمپورت، هینتِ «Segment» به Services\Segment (ناموجود) resolve
        // می‌شود و ساخت سگمنت با TypeError 500 می‌شکند (کشف راستی‌آزمایی مرورگری).
        $this->assertStringContainsString('use Modules\MarketingMessaging\Models\Segment;', $source);

        // راستی‌آزمایی رفتاری: ساخت سگمنت واقعی + شمارش
        $segment = \Modules\MarketingMessaging\Models\Segment::create([
            'name'     => 'تست فاز ۸۶',
            'criteria' => ['no_order_within_days' => 60],
        ]);

        $count = \Modules\MarketingMessaging\Services\SegmentBuilder::calculate($segment);

        $this->assertIsInt($count);
        $this->assertSame($count, $segment->fresh()->customers_count);
    }

    #[Test]
    #[TestDox('باگ مرورگری — verifyLicense با کلید خالی: پاسخ نامعتبر به‌جای TypeError')]
    public function verify_license_with_empty_key_returns_invalid_instead_of_type_error(): void
    {
        $result = app(\App\Services\PackageApiService::class)->verifyLicense('digipay', null);

        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('message', $result);

        // رکورد بدون کلید — همان مسیر TypeError قبلی (LicenseGuard پکیج‌ها)
        $module = InstalledModule::create([
            'slug'         => 'probe-nokey',
            'name'         => 'ProbeNoKey',
            'version'      => '1.0.0',
            'installed_at' => now(),
            'status'       => 'installed',
            'is_active'    => true,
            'license_expires_at' => null,
        ]);

        $this->assertSame('', (string) $module->license_key);
        $result2 = app(\App\Services\PackageApiService::class)->verifyLicense($module->slug, $module->license_key);
        $this->assertFalse($result2['valid']);
    }
}
