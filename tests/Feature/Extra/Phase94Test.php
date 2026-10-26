<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Product;
use App\Models\Widget;
use App\Services\ModuleMenuRegistry;
use App\Services\WidgetRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\ArtificialIntelligence\Models\AiChatMessage;
use Modules\ArtificialIntelligence\Models\AiChatSession;
use Modules\ArtificialIntelligence\Models\AiSetting;
use Modules\ArtificialIntelligence\Models\AiUsageLog;
use Modules\ArtificialIntelligence\Models\AiWriterDraft;
use Modules\ArtificialIntelligence\Services\AiProviderRegistry;
use Modules\ArtificialIntelligence\Services\AiProviders\AiProvider;
use Modules\ArtificialIntelligence\Services\AiProviders\OpenAiCompatibleDriver;
use Modules\ArtificialIntelligence\Services\AssetInjector;
use Modules\ArtificialIntelligence\Services\CatalogService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۴ — پکیج «هوش مصنوعی» (ArtificialIntelligence v1.0.0)
 * --------------------------------------------------------------------------
 * بستن فاز ۸۴ نقشهٔ راه: ادغام AIWriter + AICatalog + توصیه‌گر + چت‌بات
 * با ProviderRegistry مشترک (درایور سازگار Chat Completions).
 *
 *  • چهار زیرسیستم با کلید مستقل فعال/غیرفعال (الزام شخصی‌سازی کاربر)
 *  • توصیه‌گر: موتور co-purchase داده‌محور — بدون نیاز به Provider
 *  • چت‌بات: RAG-lite + سقف نرخ/روزانه + خطای نشت‌ناپذیر
 *  • قرارداد فاز ۸۶+: هویت، license_skip، منوی دو-پنلی، راهنمای ادمین،
 *    InstallPermissionsCommand قراردادی + seedDefaults idempotent.
 */
class Phase94Test extends TestCase
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

    private function requireModule(): void
    {
        if (! is_dir(base_path('Modules/ArtificialIntelligence/app'))) {
            $this->markTestSkipped('ماژول ArtificialIntelligence (هوش مصنوعی) روی این میزبان نصب نیست — تست فاز ۹۴ رد شد.');
        }
    }

    private function read(string $relative): string
    {
        $path = base_path($relative);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /** فعال‌سازی پیش‌فرض‌ها برای هر تست */
    private function aiDefaults(array $overrides = []): void
    {
        AiSetting::set('ai_enabled', '1');
        AiSetting::set('writer_enabled', '1');
        AiSetting::set('catalog_enabled', '1');
        AiSetting::set('recommender_enabled', '1');
        AiSetting::set('chatbot_enabled', '1');
        AiSetting::set('provider', 'openai');
        AiSetting::set('openai_enabled', '1');
        AiSetting::set('openai_model', 'gpt-4o-mini');
        AiSetting::set('daily_request_limit', '50');
        AiSetting::set('chatbot_daily_message_limit', '20');
        AiSetting::set('chatbot_rate_limit_per_minute', '10');

        foreach ($overrides as $key => $value) {
            AiSetting::set($key, (string) $value);
        }
    }

    /**
     * درایور فیک هوش مصنوعی — بدون HTTP واقعی؛ رفتار قابل برنامه‌ریزی.
     * Registry resolve را از container می‌گیرد → با bind جای درایور واقعی می‌نشیند.
     */
    private function bindFakeProvider(?\Throwable $throw = null, string $content = 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.'): void
    {
        app()->bind(OpenAiCompatibleDriver::class, function () use ($throw, $content) {
            return new class($throw, $content) implements AiProvider
            {
                public function __construct(
                    protected ?\Throwable $throw,
                    protected string $content,
                ) {}

                public function key(): string
                {
                    return 'openai';
                }

                public function title(): string
                {
                    return 'سرویس OpenAI (فیک)';
                }

                public function isConfigured(): bool
                {
                    return true;
                }

                public function chat(array $messages, array $options = []): array
                {
                    if ($this->throw) {
                        throw $this->throw;
                    }

                    return [
                        'content' => $this->content,
                        'raw' => ['fake' => true, 'messages' => count($messages)],
                        'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 22],
                    ];
                }

                public function ping(): array
                {
                    if ($this->throw) {
                        throw $this->throw;
                    }

                    return ['ok' => true, 'message' => 'اتصال فیک سالم است.'];
                }
            };
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
            'last_name' => 'فاز ۹۴',
            'username' => 'feat94-'.Str::random(6),
            'email' => 'feat94-'.Str::random(6).'@test.ir',
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

        $tmp = tempnam(sys_get_temp_dir(), 'ai94-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return $exitCode;
    }

    /** یک سفارش پرداخت‌شده با دو قلم (برای co-purchase) */
    private function newPaidOrderWithTwoItems($user, Product $a, Product $b)
    {
        [$order] = $this->newPaidOrder($user, $a);

        $priceB = $b->prices()->first();

        $order->items()->create([
            'product_id' => $b->id,
            'title' => $b->title,
            'price' => $priceB->salePrice(),
            'real_price' => $priceB->price,
            'quantity' => 1,
            'discount' => 0,
            'price_id' => $priceB->id,
            'product_type' => $b->type,
            'shipping_status' => 'w-pending',
        ]);

        return $order;
    }

    /* ================= ۱) هویت پکیج ================= */

    #[Test]
    #[TestDox('بند ۱ — هویت پکیج: module.json (title فارسی + v1.0.0) + composer.json + قرارداد license_skip + کلیدهای config + درایورها')]
    public function package_identity_and_license_skip_contract(): void
    {
        $this->requireModule();

        $module = json_decode((string) file_get_contents(base_path('Modules/ArtificialIntelligence/module.json')), true);
        $this->assertSame('ArtificialIntelligence', $module['name']);
        $this->assertSame('artificial-intelligence', $module['alias']);
        $this->assertSame('هوش مصنوعی', $module['title']);
        $this->assertSame('1.2.0', $module['version']);
        $this->assertStringContainsString('ArtificialIntelligenceServiceProvider', $module['providers'][0]);

        $composer = json_decode((string) file_get_contents(base_path('Modules/ArtificialIntelligence/composer.json')), true);
        $this->assertSame('modules/artificial-intelligence', $composer['name']);

        // قرارداد license_skip — الگوی PayYar/MarketingMessaging/CustomerClub/Logistics/Accounting
        $guard = $this->read('Modules/ArtificialIntelligence/app/Services/LicenseGuard.php');
        $this->assertStringContainsString("AiSetting::get('license_skip'", $guard);
        $this->assertStringContainsString("config('artificial-intelligence.license_skip'", $guard);

        $config = $this->read('Modules/ArtificialIntelligence/config/config.php');
        foreach ([
            "'ai_enabled'", "'writer_enabled'", "'catalog_enabled'",
            "'recommender_enabled'", "'chatbot_enabled'", "'provider'",
            "'openai_enabled'", "'openai_base_url'", "'openai_api_key'",
            "'openai_model'", "'daily_request_limit'", "'chatbot_daily_message_limit'",
            "'chatbot_rate_limit_per_minute'", "'chatbot_title'", "'chatbot_welcome_message'",
            "'chatbot_system_prompt'", "'recommender_count'", "'recommender_cache_hours'",
            "'catalog_auto_apply_enabled'", "'catalog_bulk_limit'", "'license_skip'",
        ] as $key) {
            $this->assertStringContainsString($key, $config, "کلید {$key} در پیش‌فرض‌های config نیست.");
        }

        // لایهٔ AI — قرارداد Provider + درایور سازگار
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Services/AiProviders/AiProvider.php'));
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Services/AiProviders/BaseAiProvider.php'));
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Services/AiProviders/OpenAiCompatibleDriver.php'));

        $driver = $this->read('Modules/ArtificialIntelligence/app/Services/AiProviders/OpenAiCompatibleDriver.php');
        $this->assertStringContainsString('chat/completions', $driver, 'درایور باید Chat Completions استاندارد را صدا بزند.');
        $this->assertStringContainsString('Bearer', $driver);

        // resolve از container (الزام تست فیک)
        $registry = $this->read('Modules/ArtificialIntelligence/app/Services/AiProviderRegistry.php');
        $this->assertStringContainsString('app($class)', $registry);
    }

    #[Test]
    #[TestDox('بند ۲ — InstallPermissionsCommand با قرارداد نام + seedDefaults idempotent (اجرای واقعی در سندباکس)')]
    public function install_permissions_command_contract_and_seeded_defaults(): void
    {
        $this->requireModule();

        // قرارداد نام کلاس/امضا برای نصب‌کنندهٔ هاست
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Console/Commands/InstallPermissionsCommand.php'));
        $cmd = $this->read('Modules/ArtificialIntelligence/app/Console/Commands/InstallPermissionsCommand.php');
        $this->assertStringContainsString('artificial-intelligence:install-permissions {--remove}', $cmd);
        $this->assertStringContainsString('seedDefaults', $cmd);

        // پرمیژن‌ها: ریشه ai + پنج فرزند
        foreach (['ai', 'ai.writer', 'ai.catalog', 'ai.chats', 'ai.usage', 'ai.settings'] as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name]);
        }

        // seedDefaults: تنظیمات موجودند (کامند واقعاً در سندباکس اجرا شده)
        $this->assertSame('1', (string) AiSetting::get('ai_enabled'));
        $this->assertSame('openai', (string) AiSetting::get('provider'));
        $this->assertSame('6', (string) AiSetting::get('recommender_count'));

        // idempotency: اجرای دوباره مقدار ادمین را بازنویسی نمی‌کند
        AiSetting::set('openai_model', 'my-custom-model');
        AiSetting::set('recommender_count', '9');
        Artisan::call('artificial-intelligence:install-permissions');
        $this->assertSame('my-custom-model', (string) AiSetting::get('openai_model'), 'اجرای مجدد نباید مقدار ادمین را بازنویسی کند.');
        $this->assertSame('9', (string) AiSetting::get('recommender_count'));
        AiSetting::set('openai_model', 'gpt-4o-mini');
        AiSetting::set('recommender_count', '6');
    }

    /* ================= ۲) ProviderRegistry ================= */

    #[Test]
    #[TestDox('بند ۳ — ProviderRegistry: resolve فیک از container + گارد {provider}_enabled → RuntimeException + titleOf فارسی')]
    public function provider_registry_resolves_fake_with_guards(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->bindFakeProvider();

        $registry = app(AiProviderRegistry::class);

        // درایور فعال پیش‌فرض: openai — فیک از container بنشسته است
        $driver = $registry->driver();
        $this->assertSame('openai', $driver->key());
        $this->assertTrue($driver->isConfigured());

        $result = $driver->chat([['role' => 'user', 'content' => 'سلام']]);
        $this->assertSame('این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.', $result['content']);
        $this->assertSame(11, $result['usage']['prompt_tokens']);

        // عنوان فارسی
        $this->assertSame('سرویس OpenAI (سازگار)', $registry->titleOf('openai'));
        $this->assertArrayHasKey('openai', $registry->enabledProviders());

        // گارد {provider}_enabled → RuntimeException با پیام فارسی
        AiSetting::set('openai_enabled', '0');
        try {
            $registry->driver();
            $this->fail('با خاموشی سرویس فعال باید RuntimeException بدهد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('غیرفعال', $e->getMessage());
        }

        // گارد کلید اصلی
        AiSetting::set('openai_enabled', '1');
        AiSetting::set('ai_enabled', '0');
        try {
            $registry->driver();
            $this->fail('با خاموشی پکیج باید RuntimeException بدهد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('هوش مصنوعی', $e->getMessage());
        }
    }

    /* ================= ۳) گاردهای اصلی ================= */

    #[Test]
    #[TestDox('بند ۴ — گاردهای اصلی: ai_enabled=0 → چت‌بات ۴۰۳ + توصیه‌گر مسدود + تولید ادمین خطای تمیز')]
    public function master_guard_blocks_every_subsystem(): void
    {
        $this->requireModule();
        $this->aiDefaults(['ai_enabled' => '0']);
        $this->bindFakeProvider();

        // چت‌بات: ۴۰۳ با پیام تمیز
        $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'سلام',
            'session_token' => 't-guard-1',
        ])->assertStatus(403)->assertJson(['ok' => false]);

        // توصیه‌گر: مسدود (۴۰۳)
        $this->getJson(route('front.artificial-intelligence.recommendations'))
            ->assertStatus(403)->assertJson(['ok' => false]);

        // صفحهٔ چت: ریدایرکت به خانه
        $this->get(route('front.artificial-intelligence.chat'))->assertRedirect('/');

        // هیچ ردپایی نمانده باشد
        $this->assertSame(0, AiChatSession::query()->count());

        // نویسندهٔ ادمین: خطای تمیز (بدون استثنا)
        $this->newCreator();
        $this->post(route('admin.ai.writer.generate'), [
            'kind' => 'article', 'tone' => 'formal', 'length' => 'medium', 'text' => 'متن تستی',
        ])->assertRedirect()->assertSessionHas('toast-error', fn ($msg) => str_contains((string) $msg, 'غیرفعال'));

        $this->assertSame(0, AiUsageLog::query()->count());
    }

    /* ================= ۴) چت‌بات ================= */

    #[Test]
    #[TestDox('بند ۵ — چت‌بات مهمان: پاسخ فیک + رکورد session/message + لاگ مصرف success')]
    public function chatbot_guest_message_with_fake_provider(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->bindFakeProvider();

        $user = $this->newUser();
        $product = $this->newProduct(['title' => 'کفش ورزشی نایک', 'description' => 'کفش ورزشی اورجینال برای دویدن']);
        unset($user, $product);

        $response = $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'کفش ورزشی دارید؟',
            'session_token' => 'guest-token-abc-94',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('reply', 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.')
            ->assertJsonPath('session_token', 'guest-token-abc-94');

        // رکورد نشست و پیام‌ها (user + assistant)
        $session = AiChatSession::query()->where('token', 'guest-token-abc-94')->first();
        $this->assertNotNull($session, 'نشست چت باید ساخته شود.');
        $this->assertSame(2, $session->messages()->count());
        $this->assertSame(AiChatMessage::ROLE_USER, AiChatMessage::query()->where('session_id', $session->id)->orderBy('id')->first()->role);
        $this->assertSame('کفش ورزشی دارید؟', AiChatMessage::query()->where('session_id', $session->id)->orderBy('id')->first()->content);
        $assistant = AiChatMessage::query()->where('session_id', $session->id)->orderByDesc('id')->first();
        $this->assertSame(AiChatMessage::ROLE_ASSISTANT, $assistant->role);
        $this->assertGreaterThan(0, $assistant->tokens);

        // لاگ مصرف موفق
        $this->assertSame(1, AiUsageLog::query()
            ->where('feature', AiUsageLog::FEATURE_CHATBOT)
            ->where('status', 'success')
            ->count());
    }

    #[Test]
    #[TestDox('بند ۶ — سقف‌های چت‌بات: daily=1 → پیام دوم پیام تمیز «سقف» + نرخ در دقیقه → ۴۲۹ تمیز')]
    public function chatbot_daily_and_rate_limits(): void
    {
        $this->requireModule();
        $this->aiDefaults(['chatbot_daily_message_limit' => '1']);
        $this->bindFakeProvider();

        // پیام اول: موفق
        $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'پیام اول', 'session_token' => 'limit-token-94',
        ])->assertStatus(200);

        // پیام دوم: سقف روزانه — پیام تمیز
        $response = $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'پیام دوم', 'session_token' => 'limit-token-94',
        ])->assertStatus(403)->assertJson(['ok' => false]);

        $this->assertStringContainsString('سقف گفتگو', (string) $response->json('message'));

        // ── نرخ در دقیقه: ۱۰ پیام در دقیقه → ۱۱اُمین ۴۲۹ ──
        AiSetting::set('chatbot_daily_message_limit', '100');
        AiSetting::set('chatbot_rate_limit_per_minute', '10');
        $token = 'rate-token-94';

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(route('front.artificial-intelligence.chat.send'), [
                'message' => "پیام نرخ {$i}", 'session_token' => $token,
            ]);
        }

        $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'پیام یازدهم', 'session_token' => $token,
        ])->assertStatus(429)->assertJson(['ok' => false]);
    }

    #[Test]
    #[TestDox('بند ۷ — چت‌بات: خاموشی زیرسیستم + خطای درایور → پاسخ تمیز بدون استثنا + لاگ failed')]
    public function chatbot_disabled_and_driver_failure_are_clean(): void
    {
        $this->requireModule();

        // ۱) chatbot_enabled=0 → ۴۰۳ تمیز
        $this->aiDefaults(['chatbot_enabled' => '0']);
        $this->bindFakeProvider();
        $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'سلام', 'session_token' => 'off-token-94',
        ])->assertStatus(403)->assertJson(['ok' => false]);
        $this->assertSame(0, AiChatSession::query()->count());

        // ۲) خطای درایور → پاسخ تمیز + لاگ failed
        $this->aiDefaults();
        $this->bindFakeProvider(new \RuntimeException('API در دسترس نیست'));

        $response = $this->postJson(route('front.artificial-intelligence.chat.send'), [
            'message' => 'سلام، سؤال دارم', 'session_token' => 'fail-token-94',
        ]);

        $response->assertStatus(502);
        $this->assertStringContainsString(
            'پشتیبانی تماس بگیرید',
            (string) $response->json('reply'),
            'پاسخ باید پیام تمیز فارسی باشد — بدون نشت خطا.'
        );
        $this->assertStringNotContainsString('API در دسترس نیست', (string) $response->json('reply'));

        $this->assertSame(1, AiUsageLog::query()
            ->where('feature', AiUsageLog::FEATURE_CHATBOT)
            ->where('status', 'failed')
            ->count());

        // پیام تمیز کاربر در تاریخچه ثبت شده
        $session = AiChatSession::query()->where('token', 'fail-token-94')->first();
        $this->assertNotNull($session);
        $this->assertSame(2, $session->messages()->count());
    }

    /* ================= ۵) توصیه‌گر ================= */

    #[Test]
    #[TestDox('بند ۸ — توصیه‌گر: co-purchase واقعی → JSON پیشنهاد درست + حذف خود محصول + گارد خاموشی + ویجت رجیستری')]
    public function recommender_co_purchase_json_and_guards(): void
    {
        $this->requireModule();
        $this->aiDefaults();

        $user = $this->newUser();
        $shoes = $this->newProduct(['title' => 'کفش دویدن', 'sell' => 5]);
        $socks = $this->newProduct(['title' => 'جوراب ورزشی', 'sell' => 1]);
        $lonely = $this->newProduct(['title' => 'محصول بدون هم‌خرید', 'sell' => 0]);

        // سفارش واقعی: کفش + جوراب در یک سفارش پرداخت‌شده
        $this->newPaidOrderWithTwoItems($user, $shoes, $socks);

        // ۱) co-purchase محصول کفش → جوراب؛ خود کفش حذف
        $response = $this->getJson(route('front.artificial-intelligence.recommendations', ['product_id' => $shoes->id]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $ids = collect($response->json('products'))->pluck('id')->all();
        $this->assertContains($socks->id, $ids, 'جوراب باید با co-purchase پیشنهاد شود.');
        $this->assertNotContains($shoes->id, $ids, 'خود محصول نباید پیشنهاد شود.');

        // شکل کارت‌ها
        $first = $response->json('products.0');
        foreach (['id', 'title', 'url', 'image', 'price'] as $key) {
            $this->assertArrayHasKey($key, $first);
        }

        // ۲) گارد خاموشی زیرسیستم → ۴۰۳
        AiSetting::set('recommender_enabled', '0');
        $this->getJson(route('front.artificial-intelligence.recommendations'))
            ->assertStatus(403);

        // ۳) ویجت در رجیستری — داده‌محور و fail-safe
        AiSetting::set('recommender_enabled', '1');
        $this->assertTrue(WidgetRegistry::has('ai-recommended'), 'ویجت ai-recommended باید در WidgetRegistry ثبت شود.');

        $widget = Widget::create([
            'page' => 'home',
            'title' => 'بر اساس خریدهای شما',
            'key' => 'ai-recommended',
            'is_active' => true,
            'theme' => 'WeblakShop',
        ]);

        $rendered = view('artificial-intelligence::front.widgets.ai-recommended', ['widget' => $widget])->render();
        $this->assertStringContainsString('بر اساس خریدهای شما', $rendered);
        $this->assertStringContainsString('جوراب ورزشی', $rendered, 'ویجت باید محصولات پیشنهادی را رندر کند.');
    }

    /* ================= ۶) نویسنده و کاتالوگ ================= */

    #[Test]
    #[TestDox('بند ۹ — AIWriter/AICatalog با فیک: تولید + لاگ + سقف روزانه + اعمال کاتالوگ + تولید گروهی + گارد خاموشی هر زیرسیستم')]
    public function writer_and_catalog_with_fake_provider(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->bindFakeProvider();
        $this->newCreator();

        // ── نویسنده: تولید + لاگ ──
        $this->post(route('admin.ai.writer.generate'), [
            'kind' => 'article', 'tone' => 'sales', 'length' => 'long', 'text' => 'مزایای خرید آنلاین',
        ])->assertRedirect()->assertSessionHas('ai_output', 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.');

        $this->assertSame(1, AiUsageLog::query()
            ->where('feature', AiUsageLog::FEATURE_WRITER)
            ->where('status', 'success')->count());

        // ذخیرهٔ پیش‌نویس مقاله → posts هسته (published=0 + status=waiting) + آرشیو پکیج
        $this->post(route('admin.ai.writer.save-draft'), [
            'title' => 'پیش‌نویس تستی فاز ۹۴',
            'content' => 'این متن پیش‌نویس تولیدشده توسط هوش مصنوعی است.',
        ])->assertRedirect();

        $draft = AiWriterDraft::query()->where('title', 'پیش‌نویس تستی فاز ۹۴')->first();
        $this->assertNotNull($draft, 'آرشیو پکیج باید پر شود.');
        $this->assertNotNull($draft->post_id, 'رکورد پیش‌نویس باید در posts هسته ساخته شود.');

        $post = Post::find($draft->post_id);
        $this->assertNotNull($post);
        $this->assertFalse((bool) $post->published, 'پیش‌نویس باید منتشرنشده باشد.');
        $this->assertSame('waiting', $post->status);

        // ── سقف روزانه (نویسنده+کاتالوگ مشترک) ──
        AiSetting::set('daily_request_limit', '1');
        $this->post(route('admin.ai.writer.generate'), [
            'kind' => 'article', 'text' => 'سوژهٔ دوم',
        ])->assertRedirect()->assertSessionHas('toast-error', fn ($msg) => str_contains((string) $msg, 'سقف'));

        AiSetting::set('daily_request_limit', '50');

        // ── گارد خاموشی نویسنده ──
        AiSetting::set('writer_enabled', '0');
        $this->post(route('admin.ai.writer.generate'), ['kind' => 'article', 'text' => 'سوژه'])
            ->assertRedirect()->assertSessionHas('toast-error', fn ($msg) => str_contains((string) $msg, 'تولید محتوا'));
        AiSetting::set('writer_enabled', '1');

        // ── کاتالوگ: تولید + اعمال با دکمهٔ تأیید ──
        $product = $this->newProduct(['title' => 'هدفون بی‌سیم پرو', 'description' => '']);

        $this->post(route('admin.ai.catalog.generate'), ['product_id' => $product->id])
            ->assertRedirect()
            ->assertSessionHas('ai_output', 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.');

        $this->post(route('admin.ai.catalog.apply'), [
            'product_id' => $product->id,
            'content' => 'توضیح جدید تولیدشده برای هدفون.',
        ])->assertRedirect();

        $this->assertSame('توضیح جدید تولیدشده برای هدفون.', $product->fresh()->description, 'توضیح محصول باید با دکمهٔ تأیید به‌روزرسانی شود.');

        // ── کاتالوگ: گارد خاموشی ──
        AiSetting::set('catalog_enabled', '0');
        $this->post(route('admin.ai.catalog.generate'), ['product_id' => $product->id])
            ->assertRedirect()->assertSessionHas('toast-error', fn ($msg) => str_contains((string) $msg, 'توضیحات محصولات'));
        AiSetting::set('catalog_enabled', '1');

        // ── تولید گروهی: سقف bulk + اعمال فقط با کلید auto ──
        $b1 = $this->newProduct(['title' => 'محصول گروهی ۱', 'description' => '']);
        $b2 = $this->newProduct(['title' => 'محصول گروهی ۲', 'description' => '']);

        // بدون auto → تولید ولی بدون اعمال
        AiSetting::set('catalog_bulk_limit', '5');
        AiSetting::set('catalog_auto_apply_enabled', '0');

        $summary = app(CatalogService::class)->bulkGenerate(null, '127.0.0.1', [$b1->id, $b2->id]);
        $this->assertSame(2, $summary['ok']);
        $this->assertSame(0, $summary['applied'], 'بدون کلید auto نباید چیزی اعمال شود.');
        $this->assertSame('', $b1->fresh()->description);

        // با auto → اعمال می‌شود
        AiSetting::set('catalog_auto_apply_enabled', '1');
        $summary = app(CatalogService::class)->bulkGenerate(null, '127.0.0.1', [$b1->id]);
        $this->assertSame(1, $summary['applied']);
        $this->assertSame('این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.', $b1->fresh()->description);

        // سقف bulk: با limit=1 فقط یکی پردازش می‌شود
        AiSetting::set('catalog_bulk_limit', '1');
        $summary = app(CatalogService::class)->bulkGenerate(null, '127.0.0.1', [$b1->id, $b2->id]);
        $this->assertSame(1, count($summary['results']));

        // لاگ کاتالوگ ثبت شده
        $this->assertGreaterThan(0, AiUsageLog::query()->where('feature', AiUsageLog::FEATURE_CATALOG)->where('status', 'success')->count());
    }

    /* ================= ۷) صفحات ادمین و قرارداد فاز ۸۶ ================= */

    #[Test]
    #[TestDox('بند ۱۰ — رندر ۷ صفحهٔ ادمین + POST تنظیمات + منوی دو-پنلی (با/بدون ai_enabled)')]
    public function admin_pages_render_and_settings_save(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        // نشست نمونه برای صفحهٔ show
        $session = AiChatSession::create(['token' => 'admin-view-token-94', 'ip' => '127.0.0.1']);
        $session->messages()->create(['role' => 'user', 'content' => 'سؤال نمایشی ادمین']);
        $session->messages()->create(['role' => 'assistant', 'content' => 'پاسخ نمایشی ادمین']);

        // رندر ۷ صفحه (+ show)
        $this->get(route('admin.ai.dashboard'))->assertOk();
        $this->get(route('admin.ai.writer'))->assertOk();
        $this->get(route('admin.ai.catalog'))->assertOk();
        $this->get(route('admin.ai.chats.index'))->assertOk();
        $this->get(route('admin.ai.chats.show', $session))->assertOk();
        $this->get(route('admin.ai.usage'))->assertOk();
        $this->get(route('admin.ai.settings.edit'))->assertOk();
        $this->get(route('admin.ai.guide'))->assertOk();

        // تنظیمات شامل فیلدهای کلیدی
        $settingsPage = $this->get(route('admin.ai.settings.edit'));
        foreach ([
            'ai_enabled', 'writer_enabled', 'catalog_enabled', 'recommender_enabled',
            'chatbot_enabled', 'openai_enabled', 'provider', 'openai_base_url',
            'openai_api_key', 'openai_model', 'daily_request_limit',
            'chatbot_daily_message_limit', 'chatbot_rate_limit_per_minute',
            'chatbot_title', 'chatbot_welcome_message', 'chatbot_system_prompt',
            'recommender_count', 'recommender_cache_hours',
            'catalog_auto_apply_enabled', 'catalog_bulk_limit',
        ] as $field) {
            $settingsPage->assertSee('name="'.$field.'"', false);
        }

        // ذخیرهٔ POST تنظیمات + اعتبارنامهٔ سرویس
        $this->post(route('admin.ai.settings.update'), [
            'ai_enabled' => '1',
            'writer_enabled' => '1',
            'catalog_enabled' => '0',
            'recommender_enabled' => '1',
            'chatbot_enabled' => '1',
            'openai_enabled' => '1',
            'catalog_auto_apply_enabled' => '1',
            'provider' => 'openai',
            'openai_base_url' => 'https://api.deepseek.example/v1/',
            'openai_api_key' => 'sk-test-94',
            'openai_model' => 'deepseek-chat',
            'daily_request_limit' => '75',
            'chatbot_daily_message_limit' => '30',
            'chatbot_rate_limit_per_minute' => '15',
            'chatbot_title' => 'دستیار فروشگاه تست',
            'chatbot_welcome_message' => 'خوش آمدید به تست!',
            'chatbot_system_prompt' => 'شما پشتیبان فروشگاه {app_name} هستید.',
            'recommender_count' => '8',
            'recommender_cache_hours' => '24',
            'catalog_bulk_limit' => '10',
        ])->assertRedirect(route('admin.ai.settings.edit'));

        $this->assertSame('0', (string) AiSetting::get('catalog_enabled'));
        $this->assertSame('https://api.deepseek.example/v1', (string) AiSetting::get('openai_base_url'), 'اسلش انتهایی باید پاک شود.');
        $this->assertSame('sk-test-94', (string) AiSetting::get('openai_api_key'));
        $this->assertSame('deepseek-chat', (string) AiSetting::get('openai_model'));
        $this->assertSame('75', (string) AiSetting::get('daily_request_limit'));
        $this->assertSame('8', (string) AiSetting::get('recommender_count'));
        $this->assertSame('دستیار فروشگاه تست', (string) AiSetting::get('chatbot_title'));

        // منوی دو-پنلی — رجیستری
        $menus = ModuleMenuRegistry::all();
        $this->assertContains('artificial-intelligence::back.partials.menu', $menus, 'منوی ادمین هوش مصنوعی در رجیستری نیست.');

        // رندر پنل قدیم — با و بدون کلید اصلی
        $menuOn = view('artificial-intelligence::back.partials.menu', ['apPanel' => 'old'])->render();
        $this->assertStringContainsString('هوش مصنوعی', $menuOn);
        $this->assertStringContainsString('داشبورد هوش مصنوعی', $menuOn);

        AiSetting::set('ai_enabled', '0');
        $menuOff = view('artificial-intelligence::back.partials.menu', ['apPanel' => 'old'])->render();
        $this->assertStringNotContainsString('داشبورد هوش مصنوعی', $menuOff, 'با خاموشی کلید اصلی، منو باید پنهان شود.');
        AiSetting::set('ai_enabled', '1');
    }

    #[Test]
    #[TestDox('بند ۱۱ — قرارداد فاز ۸۶: روت راهنما + روت‌های فرانت + کامپایل و لینت همهٔ bladeها (۱۲)')]
    public function phase86_contract_and_blade_compilation(): void
    {
        $this->requireModule();

        // راهنمای ادمین روت دارد + روت‌های فرانت موجودند (این پکیج روت فرانت دارد)
        $adminRoutes = $this->read('Modules/ArtificialIntelligence/routes/admin.php');
        $this->assertStringContainsString("'guide'", $adminRoutes);
        $this->assertStringContainsString('admin_route_prefix()', $adminRoutes);
        $this->assertStringContainsString("'ai.license'", $adminRoutes);

        $webRoutes = $this->read('Modules/ArtificialIntelligence/routes/web.php');
        $this->assertStringContainsString('front.artificial-intelligence.', $webRoutes);
        $this->assertStringContainsString('chat/send', $webRoutes);
        $this->assertStringContainsString('recommendations', $webRoutes);

        // پروایدر: روت فرانت لود می‌شود
        $rsp = $this->read('Modules/ArtificialIntelligence/app/Providers/RouteServiceProvider.php');
        $this->assertStringContainsString('mapFrontRoutes', $rsp);

        // کامپایل و لینت همهٔ bladeها
        foreach ([
            'artificial-intelligence::back.dashboard.index',
            'artificial-intelligence::back.writer.index',
            'artificial-intelligence::back.catalog.index',
            'artificial-intelligence::back.chats.index',
            'artificial-intelligence::back.chats.show',
            'artificial-intelligence::back.usage.index',
            'artificial-intelligence::back.settings.index',
            'artificial-intelligence::back.guide.index',
            'artificial-intelligence::back.partials.menu',
            'artificial-intelligence::errors.license_invalid',
            'artificial-intelligence::front.chat.index',
            'artificial-intelligence::front.widgets.ai-recommended',
        ] as $view) {
            $this->assertSame(0, $this->compileAndLint($view), "بلید {$view} باید بدون خطای syntax کامپایل شود.");
        }
    }

    /* ================= ۸) فاز ۹۶ — تزریق دستیار شناور + فرم مقاله ================= */

    /** گاردهای تزریق — همهٔ کلیدها روشن + scope ادمین */
    private function injectionDefaults(array $overrides = []): void
    {
        $this->aiDefaults([
            'post_creator_enabled' => '1',
            'assistant_enabled' => '1',
            'assistant_generate_enabled' => '1',
            'assistant_scope' => 'admin',
            'assistant_position' => 'bottom-right',
        ]);

        foreach ($overrides as $key => $value) {
            AiSetting::set($key, (string) $value);
        }
    }

    #[Test]
    #[TestDox('بند ۱۲ — تزریق: رندر صفحهٔ ایجاد مقاله (پنل جدید) شامل هر دو مارکر + config مشترک + خاموشی مستقل هر زیرسیستم')]
    public function injection_markers_on_post_create_page_and_independent_guards(): void
    {
        $this->requireModule();
        $this->injectionDefaults();

        Gate::define('posts.create', fn ($user = null) => true);
        $this->newCreator();

        // ── ① صفحهٔ ایجاد مقاله: هر دو مارکر با هر دو لایهٔ تزریق (middleware + RequestHandled) ──
        $page = $this->get(route('np.posts.create'));
        $page->assertOk();

        $html = (string) $page->getContent();

        $this->assertStringContainsString('AI-post-creator', $html, 'مارکر فرم مقاله باید تزریق شود.');
        $this->assertStringContainsString('AI-floating-assistant', $html, 'مارکر دستیار شناور باید تزریق شود.');

        // ددآپ واقعی: هر دو لایه روی همین پاسخ اجرا شده‌اند → تنها یک مارکر
        $this->assertSame(1, substr_count($html, '<!-- AI-post-creator -->'), 'تزریق دوباره نباید مارکر تکراری بسازد.');
        $this->assertSame(1, substr_count($html, '<!-- AI-floating-assistant -->'), 'تزریق دوباره نباید مارکر تکراری بسازد.');

        // config JSON مشترک — روت‌های JSON، موقعیت دستیار، i18n فارسی و provider
        $this->assertStringContainsString('window.__AI_INJECT__', $html);
        $this->assertStringContainsString('"postCreator":true', $html, 'پرچم config باید فرم مقاله را روشن نشان دهد (گارد post-creator.js).');
        $this->assertStringContainsString('"assistant":true', $html);
        $this->assertStringContainsString('"canGenerate":true', $html, 'ادمین واردشده باید مجاز به تولید با دستیار باشد.');
        $this->assertStringContainsString('admin/ai/post-creator', $html, 'آدرس JSON روت post-creator باید در config باشد.');
        $this->assertStringContainsString('admin/ai/assist', $html, 'آدرس JSON روت assist باید در config باشد.');
        $this->assertStringContainsString('bottom-right', $html);
        $this->assertStringContainsString('در حال تولید', $html, 'رشته‌های i18n فارسی باید تزریق شوند.');
        $this->assertStringContainsString('openai', $html, 'نام provider فعال برای نمایش.');

        // اسکریپت‌های دارایی با آدرس مستقیم modules/ (نه route())
        $this->assertStringContainsString('modules/artificial-intelligence/js/post-creator.js', $html);
        $this->assertStringContainsString('modules/artificial-intelligence/js/floating-assistant.js', $html);
        $this->assertStringContainsString('modules/artificial-intelligence/css/ai-inject.css', $html);

        // ── ② خاموشی post_creator_enabled → مارکر فرم غایب، دستیار حاضر ──
        $this->injectionDefaults(['post_creator_enabled' => '0']);
        $htmlNoPc = (string) $this->get(route('np.posts.create'))->getContent();

        $this->assertStringNotContainsString('AI-post-creator', $htmlNoPc);
        $this->assertStringContainsString('AI-floating-assistant', $htmlNoPc);

        // ── ③ خاموشی assistant_enabled → مارکر دستیار غایب، فرم حاضر ──
        $this->injectionDefaults(['assistant_enabled' => '0']);
        $htmlNoAssistant = (string) $this->get(route('np.posts.create'))->getContent();

        $this->assertStringContainsString('AI-post-creator', $htmlNoAssistant);
        $this->assertStringNotContainsString('AI-floating-assistant', $htmlNoAssistant);

        // ── ④ خاموشی کلید اصلی → هر دو مارکر غایب ──
        $this->injectionDefaults(['ai_enabled' => '0']);
        $htmlMasterOff = (string) $this->get(route('np.posts.create'))->getContent();

        $this->assertStringNotContainsString('AI-post-creator', $htmlMasterOff);
        $this->assertStringNotContainsString('AI-floating-assistant', $htmlMasterOff);
    }

    #[Test]
    #[TestDox('بند ۱۲ — scope دستیار: admin → فرانت بدون مارکر؛ all → فرانت حاضر با canGenerate=false برای مهمان')]
    public function assistant_scope_guards_admin_vs_front(): void
    {
        $this->requireModule();
        $this->injectionDefaults(['assistant_scope' => 'admin']);

        // مهمان فرانت (بدون لاگین ادمین) — صفحهٔ چت‌بات پکیج به‌عنوان مسیر فرانت
        $frontAdmin = (string) $this->get('/ai/chat')->getContent();
        $this->assertStringNotContainsString('AI-floating-assistant', $frontAdmin, 'با scope=admin نباید در فرانت تزریق شود.');

        // scope=all → فرانت هم دستیار دارد؛ مهمان فقط تولتیپ می‌بیند (canGenerate=false)
        $this->injectionDefaults(['assistant_scope' => 'all']);
        $frontAll = (string) $this->get('/ai/chat')->getContent();

        $this->assertStringContainsString('AI-floating-assistant', $frontAll, 'با scope=all فرانت هم باید دستیار داشته باشد.');
        $this->assertStringContainsString('"canGenerate":false', $frontAll, 'مهمان فرانت باید فقط تولتیپ ببیند.');

        // ادمین واردشده در فرانت با scope=all → تب تولید محتوا مجاز (canGenerate=true)
        Gate::define('posts.create', fn ($user = null) => true);
        $this->newCreator();

        $frontAdminIn = (string) $this->get('/ai/chat')->getContent();
        $this->assertStringContainsString('"canGenerate":true', $frontAdminIn, 'ادمین واردشده باید بتواند از تب تولید محتوا استفاده کند.');

        // فرانت هرگز فرم مقاله ندارد — حتی با scope=all
        $this->assertStringNotContainsString('AI-post-creator', $frontAll, 'فرم مقاله فقط در مسیرهای admin/{prefix}/posts/* تزریق می‌شود.');
    }

    #[Test]
    #[TestDox('بند ۱۲ — ددآپ تزریق: اجرای دوبارهٔ هستهٔ AssetInjector روی همان پاسخ مارکر تکراری نمی‌سازد')]
    public function injection_dedup_is_marker_based(): void
    {
        $this->requireModule();
        $this->injectionDefaults();

        $injector = app(AssetInjector::class);
        $request = Request::create('/admin/admin/posts/create', 'GET');

        $response = new Response(
            '<html><body><form id="post-create-form"><input name="title"></form></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );

        $injector->inject($request, $response);
        $first = (string) $response->getContent();

        $this->assertSame(1, substr_count($first, '<!-- AI-post-creator -->'));
        $this->assertSame(1, substr_count($first, '<!-- AI-floating-assistant -->'));

        // اجرای دوم (شبیه‌سازی لایهٔ دوم — listener) — نباید چیزی اضافه کند
        $injector->inject($request, $response);
        $second = (string) $response->getContent();

        $this->assertSame($first, $second, 'تزریق دوباره نباید پاسخ را تغییر دهد (dedup با مارکر).');
        $this->assertSame(1, substr_count($second, '<!-- AI-post-creator -->'));
        $this->assertSame(1, substr_count($second, '<!-- AI-floating-assistant -->'));
    }

    #[Test]
    #[TestDox('بند ۱۲ — گاردهای نهایی تزریق: POST/JSON/Livewire/غیر-2xx تزریق نمی‌شوند؛ فقط GET HTML موفق')]
    public function injection_final_guards_skip_non_html_requests(): void
    {
        $this->requireModule();
        $this->injectionDefaults();

        $injector = app(AssetInjector::class);

        // ── POST — حتی مسیر مجاز ──
        $postResponse = new Response(
            '<html><body>فرم</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']
        );
        $injector->inject(
            Request::create('/admin/admin/posts/create', 'POST'),
            $postResponse
        );
        $this->assertStringNotContainsString('AI-post-creator', (string) $postResponse->getContent(), 'درخواست POST نباید تزریق شود.');

        // ─️─ هدر x-livewire (آپدیت Livewire) ──
        $livewireResponse = new Response(
            '<html><body>فرم</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']
        );
        $livewireRequest = Request::create('/admin/admin/posts/create', 'GET');
        $livewireRequest->headers->set('x-livewire', 'true');
        $injector->inject($livewireRequest, $livewireResponse);
        $this->assertStringNotContainsString('AI-post-creator', (string) $livewireResponse->getContent(), 'پاسخ آپدیت Livewire نباید تزریق شود.');

        // ── JSON ──
        $jsonResponse = new JsonResponse(['ok' => true]);
        $injector->inject(
            Request::create('/admin/admin/ai/assist', 'GET'),
            $jsonResponse
        );
        $this->assertStringNotContainsString('AI-floating-assistant', (string) $jsonResponse->getContent(), 'پاسخ JSON نباید تزریق شود.');

        // ── وضعیت غیر 2xx ──
        $errorResponse = new Response(
            '<html><body>خطا</body></html>', 500, ['Content-Type' => 'text/html; charset=UTF-8']
        );
        $injector->inject(
            Request::create('/admin/admin/posts/create', 'GET'),
            $errorResponse
        );
        $this->assertStringNotContainsString('AI-post-creator', (string) $errorResponse->getContent(), 'پاسخ غیر موفق نباید تزریق شود.');

        // ── مسیر غیرمجاز (مثلاً لیست مقاله‌ها) ──
        $indexResponse = new Response(
            '<html><body>لیست مقاله‌ها</body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']
        );
        $injector->inject(
            Request::create('/admin/admin/posts', 'GET'),
            $indexResponse
        );
        $indexHtml = (string) $indexResponse->getContent();
        $this->assertStringNotContainsString('AI-post-creator', $indexHtml, 'فرم مقاله فقط در create/edit تزریق می‌شود.');
        $this->assertStringContainsString('AI-floating-assistant', $indexHtml, 'دستیار در همهٔ صفحات ادمین حاضر است.');
    }

    #[Test]
    #[TestDox('بند ۱۲ — قرارداد تزریق سه‌لایه + ensureAssets + دارایی‌های js/css (اسکن فایل)')]
    public function injection_three_layer_contract_and_assets(): void
    {
        $this->requireModule();

        // دارایی‌های پکیج
        foreach ([
            'Modules/ArtificialIntelligence/resources/assets/js/post-creator.js',
            'Modules/ArtificialIntelligence/resources/assets/js/floating-assistant.js',
            'Modules/ArtificialIntelligence/resources/assets/css/ai-inject.css',
        ] as $asset) {
            $this->assertFileExists(base_path($asset), "{$asset} باید موجود باشد.");
        }

        // لایهٔ ۱: میدل‌ور گروه web — ثبت در Provider boot
        $provider = $this->read('Modules/ArtificialIntelligence/app/Providers/ArtificialIntelligenceServiceProvider.php');
        $this->assertStringContainsString('pushMiddlewareToGroup(\'web\', InjectAdminAssets::class)', $provider);
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Http/Middlewares/InjectAdminAssets.php'));

        // لایهٔ ۲: Listener رویداد هستهٔ RequestHandled — ثبت در EventServiceProvider
        $events = $this->read('Modules/ArtificialIntelligence/app/Providers/EventServiceProvider.php');
        $this->assertStringContainsString('RequestHandled', $events);
        $this->assertStringContainsString('InjectAssetsOnRequestHandled', $events);
        $this->assertFileExists(base_path('Modules/ArtificialIntelligence/app/Listeners/InjectAssetsOnRequestHandled.php'));

        // هستهٔ مشترک — مارکرها، گاردها، ensureAssets و آدرس مستقیم دارایی‌ها
        $core = $this->read('Modules/ArtificialIntelligence/app/Services/AssetInjector.php');
        foreach ([
            'AI-floating-assistant',
            'AI-post-creator',
            'ensureAssets',
            "url('modules/artificial-intelligence/'",
            'assistant_generate_enabled',
            'admin/[^/]+/posts/(create|\d+/edit)$',
            'x-livewire',
        ] as $needle) {
            $this->assertStringContainsString($needle, $core, "هستهٔ تزریق باید {$needle} داشته باشد.");
        }

        // JS فرم مقاله: فرم‌ها + ادیتورها + پرکردن فیلدهای هسته
        $postCreatorJs = $this->read('Modules/ArtificialIntelligence/resources/assets/js/post-creator.js');
        foreach (['#post-create-form', '#post-edit-form', 'CKEDITOR', 'tinymce', 'ApEditor',
            'input[name="meta_title"]', 'input[name="tags"]', 'سوالات متداول',
            'X-CSRF-TOKEN', 'X-Requested-With'] as $needle) {
            $this->assertStringContainsString($needle, $postCreatorJs, "post-creator.js باید {$needle} داشته باشد.");
        }

        // JS دستیار (پورت کامل فاز ۹۷): پنل چهارتبی + تشخیص فیلد + قالب‌ها + سئوچک + تاریخچه
        $assistantJs = $this->read('Modules/ArtificialIntelligence/resources/assets/js/floating-assistant.js');
        foreach (['ac-help-fab', 'ac-help-panel', 'data-ai-hint', 'canGenerate', 'assistUrl',
            'templatesUrl', 'ac-gen-all', 'runSeoCheck', 'ac-hist-list', 'پاسخ هوشمند به کامنت',
            'بازنویسی هوشمند', 'تولید همهٔ فیلدها', "'post'", "'product'", "'comment'", "'page'"] as $needle) {
            $this->assertStringContainsString($needle, $assistantJs, "floating-assistant.js باید {$needle} داشته باشد.");
        }

        // CSS تزریقی: پالت پکیج، z-index بالا، موبایل
        $css = $this->read('Modules/ArtificialIntelligence/resources/assets/css/ai-inject.css');
        $this->assertStringContainsString('#7c3aed', $css);
        $this->assertStringContainsString('z-index: 9999', $css);
        $this->assertStringContainsString('@media (max-width: 480px)', $css);
    }

    #[Test]
    #[TestDox('بند ۱۲ — تنظیمات: دکمهٔ بررسی تزریق + پیش‌نمایش موقعیت؛ راهنما: بخش‌های فاز ۹۶ + کامپایل blade')]
    public function settings_injection_checker_and_guide_new_sections(): void
    {
        $this->requireModule();
        $this->injectionDefaults();
        $this->newCreator();

        // ── تنظیمات: کارت دستیار با دکمهٔ بررسی تزریق + پیش‌نمایش زندهٔ موقعیت ──
        $settings = $this->get(route('admin.ai.settings.edit'));
        $settings->assertOk();
        $settings->assertSee('بررسی تزریق خودکار', false);
        $settings->assertSee('ai-pos-preview', false);
        // خود صفحهٔ تنظیمات هم باید مارکر تزریق داشته باشد (مبنای دکمهٔ بررسی)
        $settings->assertSee('AI-floating-assistant', false);

        // ── راهنما: بخش‌های جدید فاز ۹۶ ──
        $guide = $this->get(route('admin.ai.guide'));
        $guide->assertOk();

        foreach (['۹ سرویس', 'Fallback', 'دستیار شناور', 'بررسی تزریق خودکار',
            'تولید یک‌جای مقاله', 'ابزارها و سئو', 'قالب‌های پرامپت', 'اتوبلاگ + کرون', 'CSV'] as $needle) {
            $guide->assertSee($needle, false);
        }

        // ── کامپایل blade ویوهای ویرایش‌شدهٔ این فاز ──
        foreach ([
            'artificial-intelligence::back.settings.index',
            'artificial-intelligence::back.guide.index',
        ] as $view) {
            $this->assertSame(0, $this->compileAndLint($view), "بلید {$view} باید بدون خطای syntax کامپایل شود.");
        }
    }
}
