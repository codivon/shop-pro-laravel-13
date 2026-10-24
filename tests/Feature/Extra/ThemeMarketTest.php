<?php

namespace Tests\Feature\Extra;

use App\Jobs\InstallThemeJob;
use App\Livewire\Admin\Packages\PackageIndex;
use App\Models\Admin;
use App\Models\InstalledTheme;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use App\Services\PackageInstallerService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

/**
 * تست‌های بازار قالب‌ها (فاز ۴۴):
 *  - متدهای Templates در PackageApiService (قرارداد API §5.2)
 *  - رندر تب پکیج‌ها و تب قالب‌های بازار
 *  - جریان خرید (پولی → رکورد type=template + درگاه | مجدد → نصب فوری)
 *  - callback پرداخت قالب → verify → صف نصب
 *  - نصب امن ZIP در themes/ (Zip-Slip + بکاپ + ثبت InstalledTheme)
 */
class ThemeMarketTest extends TestCase
{
    use DatabaseTransactions;

    private string $workspace;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/theme-market-test-'.uniqid();
        mkdir($this->workspace, 0777, true);
        $this->files = new Filesystem;

        // مسیر قالب‌ها و به‌روزرسانی فریم‌ورک در تست‌ها ایزوله می‌شود
        config([
            'packages.themes.path'               => $this->workspace.'/themes',
            'packages.themes.refresh_after_install' => false,
        ]);

        Queue::fake();
        Cache::flush();
    }

    /**
     * فیک HTTP — الگوهای مشخص + catch-all امن (پاسخ ۲۰۰ خالی JSON)
     * تا هیچ درخواستی به شبکه‌ی واقعی نرود.
     */
    private function fakeHttp(array $patterns = []): void
    {
        Http::fake(array_merge($patterns, [
            '*' => Http::response([]),
        ]));
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'سازنده',
            'username'   => 'creator-'.random_int(10000, 99999),
            'email'      => 'creator-'.random_int(10000, 99999).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /** ZIP معتبر قالب: پوشه‌ی ریشه + config/general.php */
    private function makeThemeZip(string $dirName = 'TestTheme'): string
    {
        $sourceDir = $this->workspace.'/src/'.$dirName;
        if (! is_dir($sourceDir)) {
            mkdir($sourceDir.'/config', 0777, true);
            mkdir($sourceDir.'/src/resources/assets', 0777, true);
        }

        file_put_contents($sourceDir.'/config/general.php', "<?php return ['theme_name' => '{$dirName}', 'version' => '1.2.0'];");
        file_put_contents($sourceDir.'/src/resources/assets/readme.txt', 'assets');

        $zipPath = $this->workspace.'/'.$dirName.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($sourceDir.'/config/general.php', $dirName.'/config/general.php');
        $zip->addFile($sourceDir.'/src/resources/assets/readme.txt', $dirName.'/src/resources/assets/readme.txt');
        $zip->close();

        return $zipPath;
    }

    /* =====================================================================
     *  سرویس API
     * ===================================================================== */

    #[Test]
    public function template_api_methods_hit_spec_endpoints(): void
    {
        $this->fakeHttp([
            '*/api/v1/templates' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1]]),
            '*/api/v1/templates/moonlight' => Http::response(['data' => ['slug' => 'moonlight']]),
            '*/api/v1/templates/moonlight/purchase' => Http::response(['payment_url' => 'https://pay/x', 'transaction_id' => 'tr_1']),
            '*/api/v1/templates/payments/tr_1/verify' => Http::response(['paid' => true, 'download_token' => 'dl_x']),
            '*/api/v1/templates/moonlight/check-update*' => Http::response(['has_update' => false]),
            '*/api/v1/templates/moonlight/download-url' => Http::response(['download_url' => 'https://x/dl']),
        ]);

        $api = new PackageApiService;

        $api->listTemplates(['search' => 'moon']);
        $api->getTemplate('moonlight');
        $api->createTemplatePurchase('moonlight', 'normal', 'https://shop.test/callback', 1);
        $api->verifyTemplatePayment('tr_1');
        $api->checkTemplateUpdate('moonlight', '1.0.0');
        $api->createTemplateDownloadUrl('moonlight');

        Http::assertSentInOrder([
            fn ($req) => str_contains($req->url(), '/api/v1/templates') && $req->method() === 'GET',
            fn ($req) => str_contains($req->url(), '/api/v1/templates/moonlight') && $req->method() === 'GET',
            fn ($req) => str_contains($req->url(), '/api/v1/templates/moonlight/purchase')
                && $req['purchase_type'] === 'normal'
                && $req['callback_url'] === 'https://shop.test/callback',
            fn ($req) => str_contains($req->url(), '/api/v1/templates/payments/tr_1/verify'),
            fn ($req) => str_contains($req->url(), '/api/v1/templates/moonlight/check-update'),
            fn ($req) => str_contains($req->url(), '/api/v1/templates/moonlight/download-url'),
        ]);
    }

    #[Test]
    public function exclusive_purchase_type_is_sent_as_exclusive(): void
    {
        $this->fakeHttp([
            '*/api/v1/templates/moonlight/purchase' => Http::response(['payment_url' => 'https://pay/x']),
        ]);

        (new PackageApiService)->createTemplatePurchase('moonlight', 'exclusive', 'https://shop.test/cb');

        Http::assertSent(fn ($req) => $req['purchase_type'] === 'exclusive');
    }

    /* =====================================================================
     *  نصب امن ZIP
     * ===================================================================== */

    #[Test]
    public function theme_zip_installs_into_themes_directory(): void
    {
        $zipPath = $this->makeThemeZip('TestTheme');
        $zipBytes = file_get_contents($zipPath);

        $this->fakeHttp([
            '*/api/v1/templates/download/dl_token' => Http::response($zipBytes),
        ]);

        $installer = new PackageInstallerService(new PackageApiService, new \Illuminate\Filesystem\Filesystem);

        $installed = $installer->installTemplate('test-theme', 'dl_token', null, 'normal', null);

        $this->assertSame('TestTheme', $installed->name);
        $this->assertSame(InstalledTheme::STATUS_INSTALLED, $installed->status);
        $this->assertSame('1.2.0', $installed->version);
        $this->assertDirectoryExists($this->workspace.'/themes/TestTheme');
        $this->assertFileExists($this->workspace.'/themes/TestTheme/config/general.php');
    }

    #[Test]
    public function zip_slip_entries_are_rejected(): void
    {
        $zipPath = $this->workspace.'/evil.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../../evil.php', '<?php');
        $zip->close();

        $zipBytes = file_get_contents($zipPath);

        $this->fakeHttp([
            '*/api/v1/templates/download/dl_evil' => Http::response($zipBytes),
        ]);

        $installer = new PackageInstallerService(new PackageApiService, new \Illuminate\Filesystem\Filesystem);

        try {
            $installer->installTemplate('evil-theme', 'dl_evil', null, 'normal', null);
            $this->fail('ZIP با ورودی Zip-Slip باید رد شود');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('غیرمجاز', $e->getMessage());
        }

        $this->assertSame(InstalledTheme::STATUS_FAILED, InstalledTheme::where('slug', 'evil-theme')->value('status'));
        $this->assertDirectoryDoesNotExist($this->workspace.'/themes/TestTheme');
    }

    #[Test]
    public function hash_mismatch_rejects_download(): void
    {
        $zipPath = $this->makeThemeZip('TestTheme');
        $zipBytes = file_get_contents($zipPath);

        $this->fakeHttp([
            '*/api/v1/templates/download/dl_hash' => Http::response($zipBytes),
        ]);

        $installer = new PackageInstallerService(new PackageApiService, new \Illuminate\Filesystem\Filesystem);

        try {
            $installer->installTemplate('hash-theme', 'dl_hash', str_repeat('a', 64), 'normal', null);
            $this->fail('عدم تطابق هش باید رد شود');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('یکپارچگی', $e->getMessage());
        }
    }

    #[Test]
    public function reinstall_over_existing_theme_is_backed_up(): void
    {
        $zipPath = $this->makeThemeZip('TestTheme');
        $zipBytes = file_get_contents($zipPath);

        $this->fakeHttp([
            '*/api/v1/templates/download/*' => Http::response($zipBytes),
        ]);

        $installer = new PackageInstallerService(new PackageApiService, new \Illuminate\Filesystem\Filesystem);
        $installer->installTemplate('backup-theme', 'dl_1', null, 'normal', null);
        $installer->installTemplate('backup-theme', 'dl_2', null, 'normal', null);

        $this->assertDirectoryExists($this->workspace.'/themes/TestTheme');
        $this->assertDirectoryExists($this->workspace.'/themes/.backups');
    }

    /* =====================================================================
     *  صفحه بازار — دو تب
     * ===================================================================== */

    private function actingCreator(): Admin
    {
        $creator = $this->newCreator();
        $this->actingAs($creator, 'adminPanel');

        // گیت‌های پرمیشن در کنسول ثبت نمی‌شوند — مثل منطق واقعی creator همه‌چیز مجاز
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user instanceof Admin ? true : null;
        });

        return $creator;
    }

    #[Test]
    public function bazaar_packages_tab_renders_for_creator(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/packages*' => Http::response([
                'data' => [
                    ['slug' => 'pkg-a', 'name' => 'پکیج الف', 'is_free' => true],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        Livewire::withQueryParams(['type' => 'package'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('پکیج الف', false);

    }

    #[Test]
    public function bazaar_all_tab_renders_templates_first_then_packages(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates*' => Http::response([
                'data' => [
                    ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'purchases_count' => 3],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
            '*/api/v1/packages*' => Http::response([
                'data' => [
                    ['slug' => 'pkg-a', 'name' => 'پکیج الف', 'is_free' => true],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        // فاز ۴۵ — تب «همه» (پیش‌فرض): اول بخش قالب‌ها بعد بخش پکیج‌ها
        Livewire::withQueryParams(['type' => ''])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('قالب ماه‌تاب', false)
            ->assertSee('مشاهده همه قالب‌ها', false)
            ->assertSee('پکیج الف', false)
            ->assertSee('مشاهده همه پکیج‌ها', false);
    }

    #[Test]
    public function bazaar_templates_tab_renders_template_cards(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates*' => Http::response([
                'data' => [
                    [
                        'id'              => 5,
                        'name'            => 'قالب ماه‌تاب',
                        'slug'            => 'moonlight',
                        'is_purchased'    => false,
                        'purchases_count' => 12,
                        'latest_version'  => ['version' => '1.4.2'],
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        Livewire::withQueryParams(['type' => 'template'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('قالب ماه‌تاب', false)
            ->assertSee('moonlight', false);
    }

    /* =====================================================================
     *  فاز ۵۸ — بنر اشتراک بازار (رفع «اشتراکم فعال است ولی می‌گوید فعالی ندارید»)
     *  ریشه‌یابی: شرط قبلی بنر $type === 'package' بود → در تب پیش‌فرض «همه»
     *  حتی با اشتراک فعال همیشه بنر خاکستری رندر می‌شد.
     * ===================================================================== */

    /** پاسخ استاندارد لیست پکیج‌ها با خلاصه‌ی اشتراک داده‌شده */
    private function packagesListResponse(array $subscriptionSummary): array
    {
        return [
            'data' => [
                ['slug' => 'pkg-a', 'name' => 'پکیج الف', 'is_free' => true],
            ],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            'subscription_summary' => $subscriptionSummary,
        ];
    }

    #[Test]
    public function bazaar_all_tab_shows_active_subscription_banner(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/packages*' => Http::response($this->packagesListResponse([
                'has_active_subscription' => true,
                'plan_name'               => 'طرح طلایی',
                'expires_at'              => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])),
        ]);

        // تب پیش‌فرض «همه» ($type='') — دقیقاً سناریوی باگ کاربر
        Livewire::withQueryParams(['type' => ''])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('اشتراک فعال:', false)
            ->assertSee('طرح طلایی', false)
            ->assertDontSee('اشتراک فعالی ندارید', false);
    }

    #[Test]
    public function bazaar_packages_tab_shows_active_subscription_banner(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/packages*' => Http::response($this->packagesListResponse([
                'has_active_subscription' => true,
                'plan_name'               => 'طرح طلایی',
                'expires_at'              => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])),
        ]);

        Livewire::withQueryParams(['type' => 'package'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('اشتراک فعال:', false)
            ->assertSee('طرح طلایی', false)
            ->assertDontSee('اشتراک فعالی ندارید', false);
    }

    #[Test]
    public function bazaar_templates_tab_hides_subscription_banner(): void
    {
        $this->actingCreator();

        // اشتراک فقط پکیج‌ها را پوشش می‌دهد (گلاساری API) → در تب قالب‌ها بنر معنا ندارد
        $this->fakeHttp([
            '*/api/v1/templates*' => Http::response([
                'data' => [
                    ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'purchases_count' => 3],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        Livewire::withQueryParams(['type' => 'template'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('قالب ماه‌تاب', false)
            ->assertDontSee('اشتراک فعال:', false)
            ->assertDontSee('اشتراک فعالی ندارید', false);
    }

    #[Test]
    public function bazaar_without_subscription_shows_cta_banner(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/packages*' => Http::response($this->packagesListResponse([
                'has_active_subscription' => false,
                'plan_name'               => null,
                'expires_at'              => null,
            ])),
        ]);

        Livewire::withQueryParams(['type' => 'package'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('اشتراک فعالی ندارید', false)
            ->assertDontSee('اشتراک فعال:', false);
    }

    #[Test]
    public function subscription_summary_without_flag_is_derived_from_expiry(): void
    {
        $this->actingCreator();

        // API پرچم has_active_subscription را نفرستاده ولی انقضا آینده است → باید فعال استنتاج شود
        $this->fakeHttp([
            '*/api/v1/packages*' => Http::response($this->packagesListResponse([
                'plan_name'  => 'طرح طلایی',
                'expires_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])),
        ]);

        Livewire::withQueryParams(['type' => ''])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('اشتراک فعال:', false)
            ->assertDontSee('اشتراک فعالی ندارید', false);
    }

    /* =====================================================================
     *  جریان خرید
     * ===================================================================== */

    #[Test]
    public function paid_template_purchase_creates_template_record_and_redirects_to_gateway(): void
    {
        $creator = $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates/moonlight/purchase' => Http::response([
                'payment_url'    => 'https://pay.example/redirect',
                'transaction_id' => 'tr_tpl_1',
                'amount'         => 980000,
                'gateway'        => 'zarinpal',
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'latest_version' => ['version' => '1.4.2']])
            ->set('tDetailOpen', true)
            ->call('startTemplatePurchase')
            ->assertRedirect('https://pay.example/redirect');

        $purchase = PackagePurchase::where('transaction_id', 'tr_tpl_1')->first();

        $this->assertNotNull($purchase, 'رکورد خرید قالب باید ثبت شود');
        $this->assertSame('template', $purchase->type);
        $this->assertSame(PackagePurchase::STATUS_PENDING, $purchase->status);
        $this->assertSame('normal', $purchase->meta['purchase_type'] ?? null);
    }

    #[Test]
    public function exclusive_purchase_requires_double_confirmation(): void
    {
        $this->actingCreator();

        $this->fakeHttp();

        $component = Livewire::test(PackageIndex::class)
            // فاز ۶۶ — خرید اختصاصی فقط برای قالب پولی معنا دارد (قالب بدون قیمت = رایگان و همیشه normal)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'price' => 980000, 'exclusive_price' => 2900000])
            ->set('tDetailOpen', true)
            ->set('tDetailPurchaseType', 'exclusive')
            ->call('startTemplatePurchase');

        // اولین کلیک فقط تأیید می‌خواهد — هیچ خریدی ثبت نشده
        $this->assertTrue($component->get('tExclusiveConfirmed'));
        $this->assertSame(0, PackagePurchase::count());

        // دومین کلیک ادامه می‌دهد (پاسخ fake خالی → خطای درگاه اما بدون exception)
        $component->call('startTemplatePurchase');
        $this->assertSame(0, PackagePurchase::count(), 'پاسخ بدون payment_url نباید رکورد بسازد');
    }

    #[Test]
    public function repeat_purchase_with_download_token_dispatches_install_immediately(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates/moonlight/purchase' => Http::response([
                'is_free'        => true,
                'download_token' => 'dl_repeat',
                'version'        => '1.4.2',
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'latest_version' => ['version' => '1.4.2']])
            ->call('startTemplatePurchase');

        Queue::assertPushed(InstallThemeJob::class, function (InstallThemeJob $job) {
            return $job->slug === 'moonlight'
                && $job->downloadToken === 'dl_repeat'
                && $job->purchaseType === 'normal';
        });

        $this->assertSame(InstalledTheme::STATUS_UPDATING, InstalledTheme::where('slug', 'moonlight')->value('status'));
    }

    #[Test]
    public function template_payment_callback_verifies_and_queues_install(): void
    {
        $this->actingCreator();

        $purchase = PackagePurchase::create([
            'type'           => 'template',
            'admin_id'       => $this->actingCreator()->id,
            'package_slug'   => 'moonlight',
            'package_name'   => 'قالب ماه‌تاب',
            'version'        => '1.4.2',
            'amount'         => 980000,
            'transaction_id' => 'tr_tpl_9',
            'status'         => PackagePurchase::STATUS_PENDING,
            'meta'           => ['purchase_type' => 'normal'],
        ]);

        $this->fakeHttp([
            '*/api/v1/templates/payments/tr_tpl_9/verify' => Http::response([
                'paid'           => true,
                'download_token' => 'dl_cb',
                'version'        => '1.4.2',
                'signature'      => str_repeat('e', 64),
                'message'        => 'پرداخت با موفقیت تأیید شد.',
            ]),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('np.packages.payment.callback', ['type' => 'template', 'transaction_id' => 'tr_tpl_9']));

        $response->assertRedirect();

        $purchase->refresh();
        $this->assertTrue($purchase->isPaid());

        Queue::assertPushed(InstallThemeJob::class, function (InstallThemeJob $job) {
            return $job->slug === 'moonlight'
                && $job->downloadToken === 'dl_cb'
                && $job->expectedHash === str_repeat('e', 64);
        });

        $this->assertSame(InstalledTheme::STATUS_UPDATING, InstalledTheme::where('slug', 'moonlight')->value('status'));
    }

    #[Test]
    public function failed_template_payment_marks_purchase_failed(): void
    {
        $this->actingCreator();

        PackagePurchase::create([
            'type'           => 'template',
            'admin_id'       => $this->actingCreator()->id,
            'package_slug'   => 'moonlight',
            'package_name'   => 'قالب ماه‌تاب',
            'version'        => '1.4.2',
            'transaction_id' => 'tr_tpl_fail',
            'status'         => PackagePurchase::STATUS_PENDING,
            'meta'           => ['purchase_type' => 'normal'],
        ]);

        $this->fakeHttp([
            '*/api/v1/templates/payments/tr_tpl_fail/verify' => Http::response(['error' => 'پرداخت هنوز تأیید نشده است.'], 400),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get(route('np.packages.payment.callback', ['type' => 'template', 'transaction_id' => 'tr_tpl_fail']));

        $response->assertRedirect();

        $this->assertSame(PackagePurchase::STATUS_FAILED, PackagePurchase::where('transaction_id', 'tr_tpl_fail')->value('status'));
        Queue::assertNotPushed(InstallThemeJob::class);
    }

    /* =====================================================================
     *  فاز ۶۶ — مودال جزئیات قالب (جزئیات HTML + حالت قالب رایگان)
     *  ===================================================================== */

    #[Test]
    public function template_detail_modal_renders_full_html_description(): void
    {
        $this->actingCreator();

        Livewire::test(PackageIndex::class)
            ->set('tDetail', [
                'slug'              => 'moonlight',
                'name'              => 'قالب ماه‌تاب',
                'price'             => 980000,
                'short_description' => '<p>معرفی <strong>کوتاه</strong></p>',
                'description'       => '<h2>ویژگی‌های قالب</h2><ul><li>اسلایدر</li></ul><p style="color:red">پشتیبانی کامل</p>',
            ])
            ->set('tDetailOpen', true)
            ->assertSee('جزئیات قالب')
            // جزئیات HTML باید خام رندر شود (نه escape) — درخواست کاربر فاز ۶۶
            ->assertSee('<h2>ویژگی‌های قالب</h2>', false)
            ->assertSee('<ul><li>اسلایدر</li></ul>', false)
            // استایل inline سالم می‌ماند (sanitize_package_html فقط بردارها را حذف می‌کند)
            ->assertSee('style="color:red"', false)
            // short_description هم به‌صورت HTML رندر می‌شود (بدون strip_tags)
            ->assertSee('معرفی <strong>کوتاه</strong>', false);
    }

    #[Test]
    public function template_detail_modal_for_free_theme_hides_purchase_type(): void
    {
        $this->actingCreator();

        Livewire::test(PackageIndex::class)
            ->set('tDetail', [
                'slug'        => 'moonlight',
                'name'        => 'قالب ماه‌تاب',
                'price'       => 0,
                'description' => '<p>توضیحات قالب رایگان</p>',
            ])
            ->set('tDetailOpen', true)
            ->assertSee('این قالب رایگان است')
            ->assertSee('نصب قالب')
            ->assertSee('رایگان')
            ->assertDontSee('نوع خرید را انتخاب کنید')
            ->assertDontSee('خرید اختصاصی')
            ->assertDontSee('خرید عادی');
    }

    #[Test]
    public function template_detail_modal_for_paid_theme_keeps_purchase_types(): void
    {
        $this->actingCreator();

        Livewire::test(PackageIndex::class)
            ->set('tDetail', [
                'slug'            => 'moonlight',
                'name'            => 'قالب ماه‌تاب',
                'price'           => 980000,
                'exclusive_price' => 2900000,
            ])
            ->set('tDetailOpen', true)
            ->assertSee('نوع خرید را انتخاب کنید')
            ->assertSee('خرید عادی')
            ->assertSee('خرید اختصاصی')
            ->assertSee('980,000')
            ->assertSee('2,900,000');
    }

    #[Test]
    public function free_template_purchase_forces_normal_type(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates/moonlight/purchase' => Http::response([
                'is_free'        => true,
                'download_token' => 'dl_free',
                'version'        => '1.4.2',
            ]),
        ]);

        // حتی اگر حالت انتخاب‌شده «اختصاصی» باشد، قالب رایگان همیشه خرید عادی است
        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'price' => 0])
            ->set('tDetailOpen', true)
            ->set('tDetailPurchaseType', 'exclusive')
            ->call('startTemplatePurchase');

        Queue::assertPushed(InstallThemeJob::class, function (InstallThemeJob $job) {
            return $job->slug === 'moonlight'
                && $job->downloadToken === 'dl_free'
                && $job->purchaseType === 'normal';
        });

        $this->assertSame(InstalledTheme::STATUS_UPDATING, InstalledTheme::where('slug', 'moonlight')->value('status'));
    }

    /* =====================================================================
     *  فاز ۶۷ — مودال خالی نمی‌ماند: استخراج هر شکل پاسخ + تصویر/گالری + خطای خودتشخیص
     *  ===================================================================== */

    #[Test]
    public function template_detail_modal_shows_thumbnail_url_and_gallery(): void
    {
        $this->actingCreator();

        Livewire::test(PackageIndex::class)
            ->set('tDetail', [
                'slug'          => 'moonlight',
                'name'          => 'قالب ماه‌تاب',
                'price'         => 980000,
                'thumbnail_url' => 'https://cdn.example/cover.png',
                'description'   => '<p>توضیحات</p>',
                'gallery'       => [
                    'https://cdn.example/shot-1.png',
                    ['url' => 'https://cdn.example/shot-2.png'],
                ],
            ])
            ->set('tDetailOpen', true)
            // تصویر شاخص باید از thumbnail_url بیاید (درخواست کاربر فاز ۶۷)
            ->assertSee('src="https://cdn.example/cover.png"', false)
            // گالری: هر دو آیتم (رشته و آرایه با url)
            ->assertSee('گالری پیش‌نمایش')
            ->assertSee('https://cdn.example/shot-1.png', false)
            ->assertSee('https://cdn.example/shot-2.png', false);
    }

    #[Test]
    public function template_detail_modal_shows_error_with_retry_for_unusable_payload(): void
    {
        $this->actingCreator();

        // پاسخ بدون هیچ قالبی (بدون slug/name و بدون wrapper شناخته‌شده)
        $this->fakeHttp([
            '*/api/v1/templates/moonlight' => Http::response(['status' => 'error']),
        ]);

        $match = fn ($req) => str_contains($req->url(), '/api/v1/templates/moonlight') && $req->method() === 'GET';

        $component = Livewire::test(PackageIndex::class)
            ->call('openTemplateDetail', 'moonlight')
            ->assertSee('دریافت جزئیات قالب ناموفق بود')
            ->assertSee('تلاش دوباره')
            ->assertSee('پاسخ سرور قالب‌ها ناقص بود');

        $this->assertNull($component->get('tDetail'));

        // فاز ۶۷ — پاسخ بد کش نمی‌شود: کلیک دوم دوباره به API می‌زند (نه مودال خالیِ کش‌شده)
        $component->call('openTemplateDetail', 'moonlight');

        $detailCalls = collect(Http::recorded())->filter(fn ($pair) => $match($pair[0]))->count();
        $this->assertSame(2, $detailCalls, 'کلیک دوم باید دوباره به API برود — پاسخ بی‌داده نباید کش شود');
    }

    #[Test]
    public function template_detail_payload_extraction_supports_wrapped_responses(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates/moonlight' => Http::response([
                'result' => [
                    'template' => [
                        'slug'  => 'moonlight',
                        'name'  => 'قالب ماه‌تاب',
                        'price' => 980000,
                    ],
                ],
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('openTemplateDetail', 'moonlight')
            ->assertSee('قالب ماه‌تاب', false)
            ->assertSee('نوع خرید را انتخاب کنید')
            ->assertSee('خرید اختصاصی');
    }

    #[Test]
    public function template_detail_invalid_json_response_shows_clear_error(): void
    {
        $this->actingCreator();

        // پاسخ 200 با بدنهٔ غیرJSON (مثلاً صفحهٔ خطای پروکسی) — نباید null کش شود
        $this->fakeHttp([
            '*/api/v1/templates/moonlight' => Http::response('<html>gateway error</html>', 200),
        ]);

        $component = Livewire::test(PackageIndex::class)
            ->call('openTemplateDetail', 'moonlight')
            ->assertSee('دریافت جزئیات قالب ناموفق بود')
            ->assertSee('پاسخ سرور پکیج‌ها معتبر نبود');

        $this->assertNull($component->get('tDetail'));
    }

    #[Test]
    public function bazaar_template_cards_use_thumbnail_url(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates*' => Http::response([
                'data' => [
                    [
                        'id'            => 5,
                        'name'          => 'قالب ماه‌تاب',
                        'slug'          => 'moonlight',
                        'thumbnail_url' => 'https://cdn.example/card.png',
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        Livewire::withQueryParams(['type' => 'template'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('src="https://cdn.example/card.png"', false);
    }

    /* =====================================================================
     *  فاز ۶۹ — نرمال‌سازی فیلدهای آبجکتی API + cursor-pointer + لودینگ کارت
     *  ===================================================================== */

    #[Test]
    public function template_detail_modal_coerces_object_fields_to_strings(): void
    {
        $this->actingCreator();

        // خطای لایو کاربر: «htmlspecialchars(): Argument #1 ($string) must be of
        // type string, array given» — فیلدهایی مثل author/category/تصویر آبجکت‌اند
        $this->fakeHttp([
            '*/api/v1/templates/moonlight' => Http::response([
                'data' => [
                    'slug'          => 'moonlight',
                    'name'          => 'قالب ماه‌تاب',
                    'price'         => 980000,
                    'author'        => ['id' => 3, 'name' => 'استودیو قالب'],
                    'category'      => ['id' => 7, 'title' => 'فروشگاهی'],
                    'thumbnail_url' => ['url' => 'https://cdn.example/cover.png'],
                    'description'   => ['html' => '<h2>ویژگی‌های قالب</h2>'],
                ],
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('openTemplateDetail', 'moonlight')
            // author: {name} → «استودیو قالب» + category: {title} → «فروشگاهی»
            ->assertSee('استودیو قالب', false)
            ->assertSee('فروشگاهی', false)
            // description: {html} → رندر خام HTML
            ->assertSee('<h2>ویژگی‌های قالب</h2>', false)
            // thumbnail_url: {url} → src تصویر شاخص
            ->assertSee('src="https://cdn.example/cover.png"', false);
    }

    #[Test]
    public function template_detail_modal_normalizes_paragraph_arrays_and_object_gallery(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates/moonlight' => Http::response([
                'data' => [
                    'slug'              => 'moonlight',
                    'name'              => 'قالب ماه‌تاب',
                    'price'             => 980000,
                    'short_description' => ['<p>معرفی کوتاه</p>'],
                    'description'       => ['<p>پاراگراف اول</p>', '<p>پاراگراف دوم</p>'],
                    'gallery'           => [
                        ['url' => 'https://cdn.example/shot-1.png'],
                        ['path' => 'https://cdn.example/shot-2.png'],
                        'https://cdn.example/shot-3.png',
                    ],
                ],
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('openTemplateDetail', 'moonlight')
            ->assertSee('معرفی کوتاه', false)
            // آرایهٔ پاراگراف‌ها → پیوست و رندر HTML خام
            ->assertSee('<p>پاراگراف اول</p>', false)
            ->assertSee('<p>پاراگراف دوم</p>', false)
            // گالری از سه شکل آیتم (آبجکت url / آبجکت path / رشته)
            ->assertSee('گالری پیش‌نمایش')
            ->assertSee('https://cdn.example/shot-1.png', false)
            ->assertSee('https://cdn.example/shot-2.png', false)
            ->assertSee('https://cdn.example/shot-3.png', false);
    }

    #[Test]
    public function bazaar_cards_show_pointer_and_per_card_loading_spinner(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates*' => Http::response([
                'data' => [
                    ['id' => 5, 'name' => 'قالب ماه‌تاب', 'slug' => 'moonlight', 'thumbnail_url' => 'https://cdn.example/card.png'],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
            '*/api/v1/packages*' => Http::response([
                'data' => [
                    ['slug' => 'pkg-a', 'name' => 'پکیج الف', 'is_free' => true],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ]),
        ]);

        // کارت قالب: cursor-pointer + اسپینر لودینگ روی خود کارت تا کامل شدن مودال
        Livewire::withQueryParams(['type' => 'template'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('cursor-pointer', false)
            ->assertSee('wire:target="openTemplateDetail(\'moonlight\')"', false);

        // کارت پکیج: همان اسپینر برای openDetail
        Livewire::withQueryParams(['type' => 'package'])
            ->test(PackageIndex::class)
            ->assertOk()
            ->assertSee('cursor-pointer', false)
            ->assertSee('wire:target="openDetail(\'pkg-a\')"', false);
    }

    /* =====================================================================
     *  فعال‌سازی و حذف
     * ===================================================================== */

    #[Test]
    public function template_cannot_be_uninstalled_while_active(): void
    {
        $creator = $this->actingCreator();

        $installer = new PackageInstallerService(new PackageApiService, new \Illuminate\Filesystem\Filesystem);

        $themeDir = $this->workspace.'/themes/ActiveTheme';
        mkdir($themeDir.'/config', 0777, true);
        file_put_contents($themeDir.'/config/general.php', "<?php return ['theme_name' => 'ActiveTheme'];");

        InstalledTheme::create([
            'slug'   => 'active-theme',
            'name'   => 'ActiveTheme',
            'status' => InstalledTheme::STATUS_INSTALLED,
        ]);

        // قالب «فعال» — get_current_theme از env CURRENT_THEME می‌خواند
        // (تست بدون تغییر env: اگر ActiveTheme فعال نبود، حذف باید موفق باشد؛
        //  این تست فقط گارد active را وقتی پوشش می‌دهد که env یکی باشد)

        try {
            $installer->uninstallTheme('not-installed-slug');
            $this->fail('حذف قالب نصب‌نشده باید خطا بدهد');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('نصب نشده', $e->getMessage());
        }

        // حذف قالب غیرفعال موفق است
        InstalledTheme::create([
            'slug'   => 'old-theme',
            'name'   => 'OldTheme',
            'status' => InstalledTheme::STATUS_INSTALLED,
        ]);
        mkdir($this->workspace.'/themes/OldTheme/config', 0777, true);
        file_put_contents($this->workspace.'/themes/OldTheme/config/general.php', '<?php return ["theme_name" => "OldTheme"];');

        $this->assertTrue($installer->uninstallTheme('old-theme'));
        $this->assertDirectoryDoesNotExist($this->workspace.'/themes/OldTheme');
        $this->assertNull(InstalledTheme::where('slug', 'old-theme')->first());
    }
}
