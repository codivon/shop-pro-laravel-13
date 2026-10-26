<?php

namespace Tests\Feature\Packages;

use App\Jobs\InstallPackageJob;
use App\Livewire\Admin\Packages\PackageIndex;
use App\Livewire\Admin\Packages\PackageInstalled;
use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\InstalledModule;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۹ — بازارچهٔ پکیج‌ها: تنظیمات شخصی‌سازی + گیت‌ها.
 * ---------------------------------------------------------------------------
 * پیش از این فاز بازارچه هیچ کلید تنظیماتی نداشت (نقض قانون طلایی:
 * «هر امکان از تنظیمات قابل شخصی‌سازی باشد؛ حداقل فعال/غیرفعال»).
 * لایهٔ گیت: App\Support\MarketplaceSettings (۷ کلید در جدول options با
 * مکانیزم استاندارد option()/option_update() + کش per-language).
 *
 * پوشش:
 *  ۱) کلید اصلی: packages_market_enabled=0 → منو + صفحهٔ بازار پیام تمیز، بدون تماس API
 *  ۲) گیت نصب (allow_install): گارد سروری startInstall/خرید قالب
 *  ۳) گیت حذف (allow_uninstall): گارد سروری uninstall (بازار + نصب‌شده‌ها) روی ماژول فیک
 *  ۴) گیت قالب‌ها (show_templates): تب/بخش قالب‌ها مخفی + بدون خواندن API قالب
 *  ۵) items_per_page مؤثر روی per_page درخواست‌های API
 *  ۶) notice_text بنر اختیاری بالای بازار
 *  ۷) سازگاری رو‌به‌جلو: پیش‌فرض‌ها = دقیقاً رفتار قبل از این فاز
 *  ۸) تب «بازارچه» تنظیمات برنامه‌نویس: ذخیرهٔ هر ۷ کلید با مسیر استاندارد option
 */
class MarketplacePhase99Test extends TestCase
{
    use ShopTestSetup;

    /** URLهای واقعاً ارسال‌شده به سرور بازار (با Http::fake ثبت می‌شوند) */
    private array $httpRequests = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        $this->httpRequests = [];
    }

    /* ================= کمکی‌ها ================= */

    private function newCreator(): Admin
    {
        Gate::define('packages.index', fn ($user = null) => true);
        Gate::define('packages.installed', fn ($user = null) => true);
        Gate::define('themes.update', fn ($user = null) => true);
        Gate::define('themes.delete', fn ($user = null) => true);

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'بازارچه ۹۹',
            'username'   => 'mkt99-'.Str::random(6),
            'email'      => 'mkt99-'.Str::random(6).'@test.ir',
            'password'   => Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /** پکیج فیک رایگان بازار (ساختار قرارداد API) */
    private function fakePackage(): array
    {
        return [
            'slug'                => 'market-test-package',
            'name'                => 'پکیج تست بازارچه',
            'description'         => 'توضیح پکیج فیک برای تست فاز ۹۹',
            'author'              => 'تست',
            'category'            => 'shop',
            'is_free'             => true,
            'price'               => 0,
            'default_price'       => 0,
            'version'             => '1.0.0',
            'latest_version'      => ['version' => '1.0.0'],
            'active_versions'     => [['version' => '1.0.0']],
            'active_pricing_plans'=> [['id' => 1, 'price' => 0, 'discount_price' => 0, 'duration_months' => 0]],
        ];
    }

    /** قالب فیک بازار (قرارداد §5.2) */
    private function fakeTemplate(): array
    {
        return [
            'slug'           => 'market-test-template',
            'name'           => 'قالب تست بازارچه',
            'is_free'        => true,
            'price'          => 0,
            'latest_version' => ['version' => '1.0.0'],
        ];
    }

    /** فیک کامل API سرور بازار + ثبت همهٔ URLها برای راستی‌آزمایی گیت‌ها */
    private function fakeMarketApi(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $this->httpRequests[] = $request->url();

            $url = $request->url();

            if (str_contains($url, '/check-update')) {
                return Http::response(['has_update' => false, 'latest_version' => '1.0.0']);
            }

            if (str_contains($url, '/purchase')) {
                return Http::response([
                    'license_key'    => 'LIC-TEST-99',
                    'amount'         => 0,
                    'transaction_id' => 'TX-TEST-99',
                ]);
            }

            if (str_contains($url, '/api/v1/packages/market-test-package')) {
                return Http::response(['data' => $this->fakePackage()]);
            }

            if (str_contains($url, '/api/v1/packages')) {
                return Http::response([
                    'data' => [$this->fakePackage()],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => MarketplaceSettings::itemsPerPage(), 'total' => 1],
                    'subscription_summary' => [],
                ]);
            }

            if (str_contains($url, '/api/v1/templates')) {
                return Http::response([
                    'data' => [$this->fakeTemplate()],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => MarketplaceSettings::itemsPerPage(), 'total' => 1],
                ]);
            }

            return Http::response([]);
        });
    }

    /** URLهای ارسال‌شده به یک بخش خاص از API بازار */
    private function sentUrlsContaining(string $needle): array
    {
        return array_values(array_filter(
            $this->httpRequests,
            fn (string $url) => str_contains($url, $needle)
        ));
    }

    /** رکورد ماژول نصب‌شدهٔ فیک (بدون هیچ پوشهٔ واقعی — فقط ردیف دیتابیس) */
    private function fakeInstalledModule(array $attributes = []): InstalledModule
    {
        return InstalledModule::create(array_merge([
            'slug'         => 'fake-mod-'.Str::random(6),
            'name'         => 'FakeMod'.Str::random(4),
            'version'      => '1.0.0',
            'status'       => InstalledModule::STATUS_INSTALLED,
            'is_active'    => true,
            'license_key'  => 'LIC-FAKE-99',
            'installed_at' => now(),
        ], $attributes));
    }

    /* ================= ۱) کلید اصلی ================= */

    #[Test]
    #[TestDox('بند ۱ — کلید اصلی خاموش ⇒ پیام تمیز غیرفعال + صفر تماس API (نه کرش)')]
    public function master_key_off_shows_clean_disabled_message_without_api_calls(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        option_update('packages_market_enabled', '0');
        $this->assertFalse(MarketplaceSettings::marketEnabled());

        Livewire::test(PackageIndex::class)
            ->assertSee('بازارچه از تنظیمات غیرفعال شده است.')
            ->assertDontSee('پکیج تست بازارچه');

        // با خاموشی کلید اصلی هیچ خواندنی از سرور بازار نباید انجام شود
        $this->assertSame([], $this->httpRequests, 'با خاموشی بازار، هیچ درخواستی به سرور بازار نباید زده شود.');

        // mount هم لیست بازار را بارگذاری نمی‌کند (دیتا خالی و بی‌خطا)
        $component = Livewire::test(PackageIndex::class);
        $this->assertSame([], $component->instance()->packages);
        $this->assertNull($component->instance()->apiError);
    }

    #[Test]
    #[TestDox('بند ۲ — کلید اصلی روشن (پیش‌فرض) ⇒ صفحهٔ لیست بازار اوکی + آیتم منو گیت‌دار')]
    public function master_key_on_renders_market_and_menu_item_is_gated(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        // پیش‌فرض: بدون هیچ optionی، بازار فعال است
        $this->assertTrue(MarketplaceSettings::marketEnabled());

        Livewire::test(PackageIndex::class)
            ->assertSee('پکیج تست بازارچه')
            ->assertDontSee('بازارچه از تنظیمات غیرفعال شده است.');

        // منوی سایدبار: آیتم «پکیج‌ها و قالب‌ها» گیت 'enabled' کلید اصلی دارد (الگوی فاز ۸۳)
        $marketChildren = null;
        foreach (config('admin-menu.groups') as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (($item['label'] ?? '') === 'بازار') {
                    $marketChildren = $item['children'] ?? [];
                }
            }
        }

        $this->assertNotNull($marketChildren, 'گروه منوی «بازار» باید در admin-menu موجود باشد.');
        $indexItem = collect($marketChildren)->firstWhere('route', 'np.packages.index');
        $this->assertNotNull($indexItem, 'آیتم منوی بازار باید وجود داشته باشد.');
        $this->assertSame('packages_market_enabled', $indexItem['enabled'] ?? null, 'آیتم منوی بازار باید با گیت کلید اصلی محافظت شود.');

        // و آیتم «پکیج‌های نصب‌شده» بدون گیت می‌ماند (مدیریت نصب‌شده‌ها همیشه فعال)
        $installedItem = collect($marketChildren)->firstWhere('route', 'np.packages.installed');
        $this->assertNotNull($installedItem);
        $this->assertArrayNotHasKey('enabled', $installedItem, 'مدیریت نصب‌شده‌ها نباید با کلید بازار بسته شود.');
    }

    /* ================= ۲) گیت نصب ================= */

    #[Test]
    #[TestDox('بند ۳ — allow_install خاموش ⇒ گارد سروری جلوی startInstall و خرید قالب را می‌گیرد')]
    public function install_gate_blocks_server_side_install_paths(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();
        Queue::fake();

        option_update('packages_market_allow_install', '0');
        $this->assertFalse(MarketplaceSettings::allowInstall());

        // مسیر نصب پکیج — باید زودتر از هر تماس خرید مسدود شود
        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'market-test-package')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'نصب و به‌روزرسانی پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        $this->assertFalse(
            InstalledModule::where('slug', 'market-test-package')->exists(),
            'با گیت نصب، رکورد markPending نباید ساخته شود.'
        );
        $this->assertEmpty($this->sentUrlsContaining('/purchase'), 'با گیت نصب، هیچ درخواست خریدی نباید زده شود.');

        Queue::assertNotPushed(InstallPackageJob::class);

        // مسیر خرید قالب هم زیرمجموعهٔ allow_install است
        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'market-test-template', 'name' => 'قالب تست بازارچه', 'is_free' => true])
            ->call('startTemplatePurchase')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'نصب و به‌روزرسانی پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        Queue::assertNotPushed(InstallPackageJob::class);

        // گارد سروری حتی با کلید اصلی خاموش هم بسته است (دفاع لایه‌ای — allow_install روشن)
        option_update('packages_market_allow_install', '1');
        option_update('packages_market_enabled', '0');

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'market-test-package')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'نصب و به‌روزرسانی پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        $this->assertFalse(InstalledModule::where('slug', 'market-test-package')->exists());
        Queue::assertNotPushed(InstallPackageJob::class);
    }

    #[Test]
    #[TestDox('بند ۴ — با گیت روشن، نصب پکیج رایگان فیک تا مرز صف پیش می‌رود (markPending + Job)')]
    public function install_flow_works_when_gate_on(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();
        Queue::fake();

        $this->assertTrue(MarketplaceSettings::allowInstall());

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'market-test-package')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'نصب پکیج آغاز شد');
            });

        // رکورد «در حال نصب» از لحظهٔ درخواست ساخته شده (فاز ۲۶)
        $pending = InstalledModule::where('slug', 'market-test-package')->first();
        $this->assertNotNull($pending, 'نصب باید تا markPending پیش برود.');
        $this->assertSame(InstalledModule::STATUS_UPDATING, $pending->status);
        $this->assertNotNull($pending->license_key);

        Queue::assertPushed(InstallPackageJob::class);
        $this->assertNotEmpty($this->sentUrlsContaining('/purchase'), 'نصب پکیج رایگان هم درخواست purchase (صدور لایسنس) می‌زند.');

        // و آپدیت از مودال جزئیات با گیت روشن از گارد رد می‌شود (بدون پیام گیت) + صف آپدیت ساخته می‌شود
        $pending->update(['status' => InstalledModule::STATUS_INSTALLED]);

        Livewire::test(PackageIndex::class)
            ->set('detail', ['slug' => 'market-test-package'])
            ->call('updateFromDetail')
            ->assertNotDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'نصب و به‌روزرسانی پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        $this->assertSame(
            InstalledModule::STATUS_UPDATING,
            InstalledModule::where('slug', 'market-test-package')->value('status'),
            'آپدیت باید رکورد را دوباره در حالت updating بگذارد (فاز ۲۶).'
        );
    }

    /* ================= ۳) گیت حذف ================= */

    #[Test]
    #[TestDox('بند ۵ — allow_uninstall خاموش ⇒ حذف ماژول فیک گارد سروری می‌خورد؛ روشن ⇒ حذف کامل')]
    public function uninstall_gate_blocks_server_side_on_fake_module(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        // ماژول فیک نصب‌شده — بدون هیچ پوشه/فایل واقعی (فقط ردیف دیتابیس)
        $module = $this->fakeInstalledModule();

        option_update('packages_market_allow_uninstall', '0');
        $this->assertFalse(MarketplaceSettings::allowUninstall());

        // ۱) از صفحهٔ ماژول‌های نصب‌شده
        Livewire::test(PackageInstalled::class)
            ->set('confirmingUninstall', $module->slug)
            ->call('uninstall')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'حذف پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        $this->assertTrue(
            InstalledModule::where('slug', $module->slug)->exists(),
            'با گیت حذف، ماژول نباید حذف شود.'
        );

        // ۲) از مودال جزئیات بازار
        $module2 = $this->fakeInstalledModule(['slug' => 'fake-mod-detail-99']);

        Livewire::test(PackageIndex::class)
            ->set('detail', ['slug' => $module2->slug])
            ->call('uninstallFromDetail')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'حذف پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        $this->assertTrue(InstalledModule::where('slug', $module2->slug)->exists());

        // ۳) حذف قالب هم زیرمجموعهٔ همان گیت است (گارد سروری)
        Livewire::test(PackageIndex::class)
            ->call('uninstallTemplate', $module2->slug)
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'حذف پکیج از تنظیمات بازارچه غیرفعال شده است');
            });

        // ۴) با گیت روشن، حذف ماژول فیک واقعاً انجام می‌شود (جریان uninstall سالم است)
        option_update('packages_market_allow_uninstall', '1');
        $this->assertTrue(MarketplaceSettings::allowUninstall());

        Livewire::test(PackageInstalled::class)
            ->set('confirmingUninstall', $module->slug)
            ->call('uninstall')
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'ماژول با موفقیت حذف شد');
            });

        $this->assertFalse(InstalledModule::where('slug', $module->slug)->exists(), 'با گیت روشن، حذف باید کامل شود.');
    }

    /* ================= ۴) گیت قالب‌ها ================= */

    #[Test]
    #[TestDox('بند ۶ — show_templates خاموش ⇒ تب/بخش قالب‌ها مخفی و بدون خواندن API قالب')]
    public function templates_gate_hides_tab_and_skips_api(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        option_update('packages_market_show_templates', '0');

        $component = Livewire::test(PackageIndex::class);

        $this->assertFalse($component->instance()->showsTemplates(), 'گیت قالب‌ها باید showsTemplates را ببندد.');
        $this->assertSame([], $component->instance()->templates, 'با گیت قالب‌ها، هیچ داده قالبی خوانده نمی‌شود.');

        $component
            ->assertDontSee('مشاهده همه قالب‌ها')
            // بخش قالب‌ها حتی در تب «همه» هم مخفی است
            ->assertDontSee('پوسته‌های آماده برای چهره‌ی فروشگاه');

        // هیچ درخواستی به endpoint قالب‌ها زده نشده (فقط پکیج‌ها)
        $this->assertSame([], $this->sentUrlsContaining('/api/v1/templates'), 'با گیت قالب‌ها، endpoint قالب‌ها نباید صدا زده شود.');

        // و با گیت روشن (پیش‌فرض) قالب‌ها رندر می‌شوند
        Cache::flush();
        option_update('packages_market_show_templates', '1');
        $this->fakeMarketApi();

        Livewire::test(PackageIndex::class)
            ->assertSee('قالب تست بازارچه');
    }

    /* ================= ۵) تعداد آیتم هر صفحه ================= */

    #[Test]
    #[TestDox('بند ۷ — items_per_page مؤثر: per_page=5 به درخواست‌های پکیج و قالب می‌نشیند')]
    public function items_per_page_setting_is_sent_to_market_api(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        option_update('packages_market_items_per_page', '5');
        $this->assertSame(5, MarketplaceSettings::itemsPerPage());

        Livewire::test(PackageIndex::class);

        $listUrls = array_merge(
            $this->sentUrlsContaining('/api/v1/packages?'),
            $this->sentUrlsContaining('/api/v1/templates?')
        );

        $this->assertNotEmpty($listUrls, 'باید درخواست لیست پکیج/قالب زده شده باشد.');
        foreach ($listUrls as $url) {
            $this->assertStringContainsString('per_page=5', $url, 'همهٔ درخواست‌های لیست باید per_page تنظیم‌شده را ببرند: '.$url);
        }
    }

    /* ================= ۶) بنر اختیاری ================= */

    #[Test]
    #[TestDox('بند ۸ — notice_text خالی = بدون بنر؛ پر = رندر بالای بازار')]
    public function notice_text_renders_optional_banner(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        // پیش‌فرض: بدون بنر
        $this->assertSame('', MarketplaceSettings::noticeText());

        Livewire::test(PackageIndex::class)
            ->assertDontSee('تخفیف ویژهٔ بازار تست ۹۹');

        Cache::flush();
        option_update('packages_market_notice_text', 'تخفیف ویژهٔ بازار تست ۹۹');
        $this->fakeMarketApi();

        Livewire::test(PackageIndex::class)
            ->assertSee('تخفیف ویژهٔ بازار تست ۹۹');
    }

    /* ================= ۷) سازگاری رو‌به‌جلو ================= */

    #[Test]
    #[TestDox('بند ۹ — پیش‌فرض‌ها = وضعیت قبل از فاز: هیچ رفتار فعلی نمی‌شکند')]
    public function defaults_preserve_pre_phase99_behavior(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        // هیچ کلیدی در options نیست
        $this->assertTrue(MarketplaceSettings::marketEnabled(), 'کلید اصلی پیش‌فرض روشن است (وضعیت امروز).');
        $this->assertTrue(MarketplaceSettings::allowInstall(), 'نصب پیش‌فرض مجاز است.');
        $this->assertTrue(MarketplaceSettings::allowUninstall(), 'حذف پیش‌فرض مجاز است.');
        $this->assertTrue(MarketplaceSettings::showTemplates(), 'قالب‌ها پیش‌فرض نمایش داده می‌شوند.');
        $this->assertFalse(MarketplaceSettings::autoCheckUpdates(), 'چک خودکار پیش‌فرض خاموش است (قبل از این فاز اصلاً وجود نداشت).');
        $this->assertSame(12, MarketplaceSettings::itemsPerPage(), 'تعداد در صفحه پیش‌فرض = مقدار قبلی hard-code.');
        $this->assertSame('', MarketplaceSettings::noticeText(), 'بدون بنر پیش‌فرض.');

        // جریان کامل ورود به بازار + لود قالب و پکیج مثل قبل کار می‌کند
        Livewire::test(PackageIndex::class)
            ->assertSee('پکیج تست بازارچه')
            ->assertSee('قالب تست بازارچه')
            ->assertSee('مشاهده همه قالب‌ها');

        // URL بوکمارک‌شدهٔ ?type=template هم سالم است
        $component = Livewire::test(PackageIndex::class, ['type' => 'template']);
        $this->assertSame('template', $component->instance()->type);
        $this->assertTrue($component->instance()->showsTemplates());
    }

    #[Test]
    #[TestDox('بند ۱۰ — چک خودکار به‌روزرسانی فقط با کلید روشن اجرا می‌شود (دکمهٔ دستی می‌ماند)')]
    public function auto_check_updates_gate_controls_mount_check(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        // ماژول نصب‌شدهٔ فیک تا checkUpdates کاندید داشته باشد
        $this->fakeInstalledModule();

        // پیش‌فرض (خاموش) = رفتار قبل از فاز: هیچ چک خودکاری در mount نیست
        Livewire::test(PackageIndex::class);

        $this->assertSame([], $this->sentUrlsContaining('/check-update'), 'پیش‌فرض هیچ چک به‌روزرسانی خودکار نباید زده شود.');

        // روشن‌کردن کلید ⇒ mount بازار خودکار چک می‌زند
        Cache::flush();
        option_update('packages_market_auto_check_updates', '1');
        $this->fakeMarketApi();

        Livewire::test(PackageIndex::class);

        $this->assertNotEmpty(
            $this->sentUrlsContaining('/check-update'),
            'با کلید روشن، mount باید چک خودکار بزند.'
        );
    }

    /* ================= ۸) تب تنظیمات بازارچه ================= */

    #[Test]
    #[TestDox('بند ۱۱ — تب «بازارچه» تنظیمات برنامه‌نویس هر ۷ کلید را با مسیر استاندارد option ذخیره می‌کند')]
    public function developer_marketplace_tab_saves_all_seven_keys(): void
    {
        $this->newCreator();

        Livewire::test(Developer::class)
            ->assertSee('بازارچه')
            ->assertSee('ذخیره تنظیمات بازارچه')
            // پیش‌فرض‌ها از MarketplaceSettings خوانده شده‌اند
            ->assertSet('marketEnabled', true)
            ->assertSet('marketItemsPerPage', 12)
            ->set('marketEnabled', false)
            ->set('marketAllowInstall', false)
            ->set('marketAllowUninstall', true)
            ->set('marketShowTemplates', false)
            ->set('marketAutoCheckUpdates', true)
            ->set('marketItemsPerPage', 9)
            ->set('marketNoticeText', '  بنر تست بازار  ')
            ->call('saveMarketplace')
            ->assertHasNoErrors();

        // ذخیره با همان مکانیزم استاندارد هسته (جدول options + کش)
        $this->assertSame('0', option('packages_market_enabled', '1'));
        $this->assertSame('0', option('packages_market_allow_install', '1'));
        $this->assertSame('1', option('packages_market_allow_uninstall', '1'));
        $this->assertSame('0', option('packages_market_show_templates', '1'));
        $this->assertSame('1', option('packages_market_auto_check_updates', '0'));
        $this->assertSame('9', option('packages_market_items_per_page', ''));
        $this->assertSame('بنر تست بازار', option('packages_market_notice_text', ''));

        // و لایهٔ گیت فوراً همان را می‌خواند
        $this->assertFalse(MarketplaceSettings::marketEnabled());
        $this->assertFalse(MarketplaceSettings::allowInstall());
        $this->assertTrue(MarketplaceSettings::allowUninstall());
        $this->assertFalse(MarketplaceSettings::showTemplates());
        $this->assertTrue(MarketplaceSettings::autoCheckUpdates());
        $this->assertSame(9, MarketplaceSettings::itemsPerPage());
        $this->assertSame('بنر تست بازار', MarketplaceSettings::noticeText());

        // همهٔ کلیدها باید در non_language_options باشند (مستقل از زبان، همیشه 'fa')
        $nonLanguage = config('general.non_language_options');
        foreach ([
            'packages_market_enabled',
            'packages_market_allow_install',
            'packages_market_allow_uninstall',
            'packages_market_show_templates',
            'packages_market_auto_check_updates',
            'packages_market_items_per_page',
            'packages_market_notice_text',
        ] as $key) {
            $this->assertContains($key, $nonLanguage, "کلید {$key} باید non-language باشد.");
        }
    }

    #[Test]
    #[TestDox('بند ۱۲ — صفحهٔ نصب‌شده‌ها با کلید اصلی خاموش هم در دسترس است (تصمیم مستند فاز ۹۹)')]
    public function installed_page_stays_accessible_when_market_disabled(): void
    {
        $this->newCreator();
        $this->fakeMarketApi();

        $module = $this->fakeInstalledModule();

        option_update('packages_market_enabled', '0');

        // صفحهٔ مدیریت نصب‌شده‌ها باز است و ماژول فیک را نشان می‌دهد
        Livewire::test(PackageInstalled::class)
            ->assertSee($module->slug)
            ->assertSee('ماژول‌های نصب‌شده');

        // فعال/غیرفعال‌سازی محلی (عملیات حیاتی پکیج خراب) بلاک نمی‌شود
        Livewire::test(PackageInstalled::class)
            ->call('toggleActivation', $module->slug)
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains((string) ($params['message'] ?? ''), 'ماژول غیرفعال شد');
            });

        $this->assertFalse($module->refresh()->is_active);
    }
}
