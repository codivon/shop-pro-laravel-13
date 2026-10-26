<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\ArtificialIntelligence\Models\AiPromptTemplate;
use Modules\ArtificialIntelligence\Models\AiSetting;
use Modules\ArtificialIntelligence\Models\AiUsageLog;
use Modules\ArtificialIntelligence\Models\AiWriterDraft;
use Modules\ArtificialIntelligence\Services\AiProviderRegistry;
use Modules\ArtificialIntelligence\Services\AiProviders\AiProvider;
use Modules\ArtificialIntelligence\Services\AiProviders\OpenAiCompatibleDriver;
use Modules\ArtificialIntelligence\Services\SeoAnalyzerService;
use Modules\ArtificialIntelligence\Services\TemplateService;
use Modules\ArtificialIntelligence\Services\WriterService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۶ — ارتقای پکیج هوش مصنوعی (ArtificialIntelligence v1.1.0)
 * --------------------------------------------------------------------------
 * بازگشت کامل قابلیت‌های AIWriter + AICatalog قدیم به پکیج واحد:
 *
 *  • ۹ پرووایدر (OpenAI/OpenRouter/DeepSeek/Claude/Gemini/Mistral/Groq/Cohere/Ollama)
 *    + fallback زنجیره‌ای + تلاش مجدد — هر سرویس کلید مستقل فعال/غیرفعال دارد
 *    (حداقل ۶ پرووایدر — درخواست صریح کاربر).
 *  • ۱۳ ابزار محتوایی + تحلیلگر سئو بومی + صدای برند.
 *  • generateArticlePackage — فرم «تولید یک‌جای مقاله» در صفحهٔ افزودن مقاله.
 *  • قالب‌های پرامپت + اتوبلاگ زمان‌بندی‌شده + undo کاتالوگ + خروجی CSV مصرف.
 */
class Phase96Test extends TestCase
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
        if (!is_dir(base_path('Modules/ArtificialIntelligence/app'))) {
            $this->markTestSkipped('ماژول ArtificialIntelligence (هوش مصنوعی) روی این میزبان نصب نیست — تست فاز ۹۶ رد شد.');
        }
    }

    /** پیش‌فرض‌های فاز ۹۶ — همهٔ گاردها روشن */
    private function aiDefaults(array $overrides = []): void
    {
        AiSetting::flushCache();

        AiSetting::set('ai_enabled', '1');
        AiSetting::set('writer_enabled', '1');
        AiSetting::set('catalog_enabled', '1');
        AiSetting::set('provider', 'openai');
        AiSetting::set('openai_enabled', '1');
        AiSetting::set('openai_api_key', 'sk-test-fake');
        AiSetting::set('openai_model', 'gpt-4o-mini');
        AiSetting::set('provider_fallback_enabled', '1');
        AiSetting::set('request_retries', '1');
        AiSetting::set('post_creator_enabled', '1');
        AiSetting::set('assistant_enabled', '1');
        AiSetting::set('assistant_generate_enabled', '1');
        AiSetting::set('daily_request_limit', '50');
        AiSetting::set('autoblog_enabled', '1');
        AiSetting::set('autoblog_topics', "راهنمای خرید هدفون بی‌سیم\nمعرفی بهترین ساعت‌های هوشمند ۲۰۲۶");

        foreach ($overrides as $key => $value) {
            AiSetting::set($key, (string) $value);
        }

        AiSetting::flushCache();
    }

    /**
     * درایور فیک قابل‌برنامه‌ریزی — می‌تواند N بار اول شکست بخورد (شبیه‌سازی
     * fallback) و از بار (N+1)ام موفق شود. چون همهٔ درایورهای سازگار یک کلاس
     * دارند، شمارندهٔ ایستا درbind بسته می‌شود.
     */
    private function bindFakeProvider(?\Throwable $throw = null, string $content = 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است.', int $failFirstCalls = 0): void
    {
        // شمارندهٔ مشترک در شیء — reference در property promotion مجاز نیست
        $state = new \ArrayObject(['calls' => 0]);

        app()->bind(OpenAiCompatibleDriver::class, function () use ($throw, $content, $failFirstCalls, $state) {
            return new class($throw, $content, $failFirstCalls, $state) implements AiProvider {
                public function __construct(
                    protected ?\Throwable $throw,
                    protected string $content,
                    protected int $failFirstCalls,
                    protected \ArrayObject $state,
                ) {}

                public function key(): string { return 'openai'; }
                public function title(): string { return 'سرویس OpenAI (فیک)'; }
                public function isConfigured(): bool { return true; }

                public function chat(array $messages, array $options = []): array
                {
                    $this->state['calls']++;
                    $counter = $this->state['calls'];

                    if ($this->throw !== null) {
                        throw $this->throw;
                    }

                    if ($counter <= $this->failFirstCalls) {
                        throw new \RuntimeException('خطای شبیه‌سازی‌شدهٔ سرویس اصلی (تلاش '.$counter.').');
                    }

                    return [
                        'content' => $this->content,
                        'raw' => ['fake' => true],
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
            \Illuminate\Support\Facades\Gate::define($permission->name, function ($user = null) use ($permission) {
                $admin = $user instanceof \App\Models\Admin
                    ? $user
                    : \Illuminate\Support\Facades\Auth::guard('adminPanel')->user();

                return $admin instanceof \App\Models\Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۹۶',
            'username'   => 'feat96-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat96-'.\Illuminate\Support\Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ================= ۱) ماتریس پرووایدرها ================= */

    #[Test]
    #[TestDox('بند ۱ — نه پرووایدر در رجیستری + عنوان فارسی + گارد {provider}_enabled (حداقل ۶ سرویس — درخواست کاربر)')]
    public function provider_matrix_nine_services_with_guards(): void
    {
        $this->requireModule();

        $registry = app(AiProviderRegistry::class);
        $all = $registry->allProviders();

        $this->assertCount(9, $all, 'رجیستری باید دقیقاً ۹ سرویس داشته باشد (openai/openrouter/deepseek/anthropic/gemini/mistral/groq/cohere/ollama).');

        foreach (['openai', 'openrouter', 'deepseek', 'anthropic', 'gemini', 'mistral', 'groq', 'cohere', 'ollama'] as $key) {
            $this->assertArrayHasKey($key, $all);
            $this->assertNotSame($key, $all[$key], 'عنوان سرویس باید فارسی/خوانا باشد نه کلید خام.');
        }

        $this->assertSame('سرویس OpenAI (سازگار)', $all['openai'], 'عنوان openai عین قرارداد فاز ۹۴ باید بماند.');
        $this->assertSame('Claude (Anthropic)', $all['anthropic']);
        $this->assertSame('Gemini (Google)', $all['gemini']);
        $this->assertSame('Ollama (لوکال)', $all['ollama']);

        // گارد {provider}_enabled — سرویس غیرفعال در enabledProviders نیست
        AiSetting::flushCache();
        AiSetting::set('ai_enabled', '1');
        AiSetting::set('openai_enabled', '1');
        AiSetting::set('deepseek_enabled', '0');

        $enabled = $registry->enabledProviders();
        $this->assertArrayHasKey('openai', $enabled);
        $this->assertArrayNotHasKey('deepseek', $enabled);

        // گارد سرویس غیرفعال — resolve مستقیم سرویسِ غیرفعال با driver() خطا می‌دهد
        AiSetting::set('provider', 'deepseek');
        AiSetting::flushCache();

        try {
            $registry->driver();
            $this->fail('driver() برای سرویس غیرفعال باید RuntimeException بدهد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('غیرفعال است', $e->getMessage());
        }
    }

    /* ================= ۲) زنجیرهٔ جایگزینی ================= */

    #[Test]
    #[TestDox('بند ۲ — chatWithFallback: سرویس اصلی خراب → سرویس دوم موفق با provider درست؛ fallback خاموش → خطا')]
    public function chat_with_fallback_falls_over_to_next_provider(): void
    {
        $this->requireModule();
        $this->aiDefaults([
            'deepseek_enabled' => '1',
            'deepseek_api_key' => 'sk-deepseek-fake',
        ]);
        // دو تلاش اول شکست (۱ تلاش openai + ۱ retry آن، سپس deepseek موفق)
        $this->bindFakeProvider(null, 'پاسخ از سرویس جایگزین', 2);

        $registry = app(AiProviderRegistry::class);
        $result = $registry->chatWithFallback([
            ['role' => 'user', 'content' => 'سلام'],
        ]);

        $this->assertSame('پاسخ از سرویس جایگزین', $result['content']);
        $this->assertSame('deepseek', $result['provider'], 'پرووایدر موفق باید در خروجی گزارش شود.');
        $this->assertSame(11, (int) ($result['usage']['prompt_tokens'] ?? 0));

        // ── fallback خاموش → فقط سرویس فعال تلاش می‌شود و شکست می‌خورد ──
        $this->aiDefaults([
            'provider_fallback_enabled' => '0',
            'deepseek_enabled' => '1',
        ]);
        $this->bindFakeProvider(new \RuntimeException('خطای دائمی سرویس فعال'));

        try {
            app(AiProviderRegistry::class)->chatWithFallback([
                ['role' => 'user', 'content' => 'سلام'],
            ]);
            $this->fail('با fallback خاموش و سرویس فعال خراب باید خطا بدهد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('خطای دائمی', $e->getMessage());
        }
    }

    #[Test]
    #[TestDox('بند ۳ — هیچ سرویس فعالی نبود → پیام فارسی تمیز (نه نشت استک)')]
    public function chat_with_fallback_without_any_configured_provider_is_clean(): void
    {
        $this->requireModule();
        $this->aiDefaults([
            'openai_enabled' => '0',
            'provider' => 'openai',
        ]);

        try {
            app(AiProviderRegistry::class)->chatWithFallback([
                ['role' => 'user', 'content' => 'سلام'],
            ]);
            $this->fail('بدون هیچ سرویس پیکربندی‌شده باید RuntimeException بدهد.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('هیچ سرویس هوش مصنوعی فعالی پیکربندی نشده است', $e->getMessage());
        }
    }

    /* ================= ۳) تولید یک‌جای مقاله (فرم صفحهٔ مقاله) ================= */

    #[Test]
    #[TestDox('بند ۴ — generateArticlePackage: JSON سالم → نگاشت کامل فیلدها؛ JSON خراب → نگاشت دستی (عنوان = خط اول)')]
    public function article_package_maps_json_and_falls_back_to_manual(): void
    {
        $this->requireModule();
        $this->aiDefaults();

        // ── ① JSON سالم ──
        $json = json_encode([
            'title'            => 'راهنمای خرید هدفون بی‌سیم در ۱۴۰۵',
            'summary'          => 'خلاصهٔ کامل برای معرفی مقاله.',
            'content'          => '<h2>مقدمه</h2><p>متن مقالهٔ کامل با HTML تمیز.</p>',
            'meta_title'       => 'خرید هدفون بی‌سیم | راهنمای ۱۴۰۵',
            'meta_description' => 'هر آنچه قبل از خرید هدفون بی‌سیم باید بدانید.',
            'keywords'         => ['هدفون بی‌سیم', 'راهنمای خرید'],
            'faq'              => [['q' => 'بهترین برند؟', 'a' => 'بستگی به بودجه دارد.']],
        ], JSON_UNESCAPED_UNICODE);

        $this->bindFakeProvider(null, $json);

        $package = app(WriterService::class)->generateArticlePackage([
            'topic'     => 'راهنمای خرید هدفون بی‌سیم',
            'type'      => 'article',
            'with_meta' => true,
            'with_tags' => true,
            'with_faq'  => true,
        ], 1, '127.0.0.1');

        $this->assertSame('راهنمای خرید هدفون بی‌سیم در ۱۴۰۵', $package['title']);
        $this->assertSame('<h2>مقدمه</h2><p>متن مقالهٔ کامل با HTML تمیز.</p>', $package['content']);
        $this->assertSame('خرید هدفون بی‌سیم | راهنمای ۱۴۰۵', $package['meta_title']);
        $this->assertSame(['هدفون بی‌سیم', 'راهنمای خرید'], $package['keywords']);
        $this->assertCount(1, $package['faq']);
        $this->assertNotSame('', $package['provider']);

        // لاگ مصرف ثبت شود
        $this->assertSame(1, AiUsageLog::query()->where('feature', 'writer')->where('status', 'success')->count());

        // ── ② JSON خراب → نگاشت دستی ──
        $this->bindFakeProvider(null, "خط اول: عنوان مقاله بدون ساختار\nخط دوم بدنهٔ مقاله است.");
        AiSetting::flushCache();

        $manual = app(WriterService::class)->generateArticlePackage([
            'topic' => 'موضوع آزمایشی',
        ], 1, '127.0.0.1');

        $this->assertSame('خط اول: عنوان مقاله بدون ساختار', $manual['title'], 'در پارس شکست، عنوان باید خط اول باشد.');
        $this->assertSame([], $manual['keywords']);
        $this->assertSame([], $manual['faq']);
    }

    /* ================= ۴) جعبه‌ابزار ۱۳گانه ================= */

    #[Test]
    #[TestDox('بند ۵ — ۱۳ ابزار محتوایی + ثبت لاگ مصرف با نام ابزار')]
    public function content_tools_generate_and_log_usage(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->bindFakeProvider(null, '۱. عنوان اول فیک'.PHP_EOL.'۲. عنوان دوم فیک');

        $service = app(WriterService::class);

        $this->assertCount(13, WriterService::TOOLS, 'جعبه‌ابزار باید ۱۳ ابزار داشته باشد (شامل alt تصاویر).');

        $result = $service->generateTool('titles', ['text' => 'هدفون‌های بی‌سیم ۲۰۲۶'], 1, '127.0.0.1');

        $this->assertStringContainsString('عنوان اول', $result['content']);
        $this->assertSame('titles', $result['tool']);

        $log = AiUsageLog::query()->where('feature', 'writer')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('titles', (string) ($log->meta['tool'] ?? ''), 'نام ابزار باید در متای لاگ مصرف ثبت شود.');
    }

    /* ================= ۵) تحلیلگر سئو بومی ================= */

    #[Test]
    #[TestDox('بند ۶ — SeoAnalyzerService بومی: ۱۰ معیار + امتیاز محتوای خوب بالاتر از بد + پیشنهاد بهبود')]
    public function seo_analyzer_native_scoring(): void
    {
        $this->requireModule();

        $analyzer = app(SeoAnalyzerService::class);

        $goodTitle = 'راهنمای جامع خرید هدفون بی‌سیم برای موسیقی حرفه‌ای در سال ۱۴۰۵';
        $goodMeta  = 'بررسی تخصصی هدفون‌های بی‌سیم؛ مقایسهٔ مدل‌ها، باتری، کیفیت صدا و راهنمای انتخاب بهترین گزینه برای بودجهٔ شما با نکات خرید.';
        $goodBody  = '<h2>چرا هدفون بی‌سیم؟</h2><p>'.str_repeat('خرید هدفون بی‌سیم امروز محبوبیت زیادی پیدا کرده است و انتخاب مدل مناسب اهمیت دارد. ', 14).'</p>'
            .'<h3>معیارهای انتخاب</h3><p>'.str_repeat('کیفیت صدا، عمر باتری و اتصال پایدار سه معیار اصلی هستند که باید بررسی شوند. ', 10).'</p>'
            .'<p><a href="/blog">مقالهٔ مرتبط</a> را هم بخوانید. <img src="/x.png" alt="هدفون بی‌سیم"></p>';

        $good = $analyzer->analyze($goodBody, 'هدفون بی‌سیم', $goodTitle, $goodMeta);
        $bad  = $analyzer->analyze('متن کوتاه.', null, 'کم', '');

        $this->assertCount(10, $good['checks'], 'تحلیلگر باید ۱۰ معیار وزن‌دار داشته باشد.');
        $this->assertGreaterThan($bad['score'], $good['score'], 'امتیاز محتوای سئوشده باید بالاتر باشد.');
        $this->assertGreaterThanOrEqual(0, $good['score']);
        $this->assertLessThanOrEqual(100, $good['score']);
        $this->assertNotEmpty($bad['suggestions'], 'برای محتوای ضعیف باید پیشنهاد بهبود بدهد.');

        $score = SeoAnalyzerService::quickScore('<h2>عنوان</h2><p>'.str_repeat('متن نمونهٔ قابل قبول برای امتیاز سریع. ', 12).'</p>');
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /* ================= ۶) قالب‌های پرامپت ================= */

    #[Test]
    #[TestDox('بند ۷ — قالب‌های پرامپت: سید idempotent + forType + بازگردانی حذف‌شده‌ها')]
    public function prompt_templates_seed_restore_and_for_type(): void
    {
        $this->requireModule();

        $first = TemplateService::seedIfMissing();
        $this->assertGreaterThanOrEqual(24, AiPromptTemplate::query()->count(), 'بعد از سید باید حداقل ۲۴ قالب در دسترس باشد (سید ممکن است از بوت provider قبلاً انجام شده باشد).');
        $this->assertSame(0, TemplateService::seedIfMissing(), 'سید باید idempotent باشد — اجرای دوم صفر.');

        $products = TemplateService::forType('product');
        $this->assertNotEmpty($products);
        // forType قالب‌های همان نوع + عمومی را برمی‌گرداند (طراحی سرویس)
        $this->assertTrue(collect($products)->every(fn ($t) => in_array($t->content_type, ['product', 'generic'], true)));
        $this->assertGreaterThan(0, collect($products)->where('content_type', 'product')->count());

        // حذف عمدی → بازگردانی فقط همان
        $victim = AiPromptTemplate::query()->where('content_type', 'article')->first();
        $this->assertNotNull($victim);
        $victim->delete();

        $restored = TemplateService::restoreDefaults();
        $this->assertSame(1, $restored, 'restoreDefaults باید فقط قالب حذف‌شده را برگرداند.');
        // بازگردانی = درج دوباره با title + content_type همان (id جدید طبیعی است)
        $this->assertTrue(
            AiPromptTemplate::query()->where('title', $victim->title)->where('content_type', $victim->content_type)->exists(),
            'قالب حذف‌شده باید با همان عنوان/نوع برگردد.'
        );
    }

    /* ================= ۷) اتوبلاگ ================= */

    #[Test]
    #[TestDox('بند ۸ — اتوبلاگ: خاموشی → پیام فارسی؛ --force با فیک → Post پیش‌نویس + آرشیو پکیج')]
    public function autoblog_command_guards_and_creates_post(): void
    {
        $this->requireModule();
        $this->aiDefaults(['autoblog_enabled' => '0']);
        $this->bindFakeProvider(null, json_encode([
            'title'            => 'راهنمای خرید هدفون بی‌سیم',
            'summary'          => 'خلاصهٔ آزمایشی اتوبلاگ.',
            'content'          => '<h2>مقدمه</h2><p>متن کامل مقالهٔ تولیدشده.</p>',
            'meta_title'       => 'متا آزمایشی',
            'meta_description' => 'توضیح متا آزمایشی',
            'keywords'         => ['هدفون'],
            'faq'              => [],
        ], JSON_UNESCAPED_UNICODE));

        // ── ① خاموشی → پیام فارسی و هیچ تولیدی ──
        $this->artisan('artificial-intelligence:autoblog')
            ->expectsOutputToContain('اتوبلاگ خاموش است')
            ->assertSuccessful();

        $this->assertSame(0, AiWriterDraft::query()->where('feature', 'autoblog')->count());

        // ── ② --force → تولید و ساخت Post پیش‌نویس ──
        AiSetting::set('autoblog_enabled', '1');
        AiSetting::flushCache();

        $this->artisan('artificial-intelligence:autoblog', ['--force' => true])
            ->expectsOutputToContain('اتوبلاگ شروع شد')
            ->assertSuccessful();

        $drafts = AiWriterDraft::query()->where('feature', 'autoblog')->get();
        $this->assertGreaterThanOrEqual(1, $drafts->count(), 'باید رکورد آرشیو اتوبلاگ ساخته شود.');

        $postIds = $drafts->pluck('post_id')->filter()->all();
        $this->assertNotEmpty($postIds, 'اتوبلاگ باید Post هسته بسازد.');

        $post = Post::query()->find($postIds[0]);
        $this->assertNotNull($post);
        $this->assertEquals(0, $post->published, 'پیش‌فرض اتوبلاگ باید پیش‌نویس باشد (autoblog_publish=0).');
        $this->assertSame('راهنمای خرید هدفون بی‌سیم', $post->title);

        // گارد فرکانس — اجرای دوم بلافاصله باید «هنوز نرسیده» بدهد (سقف سبزی گاردها با --force رد می‌شود پس بدون force می‌آییم)
        AiSetting::set('autoblog_enabled', '1');
        AiSetting::flushCache();

        $this->artisan('artificial-intelligence:autoblog')
            ->expectsOutputToContain('زمان اجرای بعدی')
            ->assertSuccessful();
    }

    /* ================= ۸) endpoint فرم مقاله (پست‌کریتور) ================= */

    #[Test]
    #[TestDox('بند ۹ — POST post-creator: JSON کامل برای ادمین؛ خاموشی امکان → ۴۰۳ با پیام فارسی')]
    public function post_creator_endpoint_json_with_guards(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();
        $this->bindFakeProvider(null, json_encode([
            'title'            => 'عنوان از endpoint',
            'summary'          => 'خلاصهٔ از endpoint.',
            'content'          => '<p>متن از endpoint.</p>',
            'meta_title'       => 'متا endpoint',
            'meta_description' => 'توضیح متا endpoint',
            'keywords'         => ['کلیدواژه'],
            'faq'              => [['q' => 'سوال؟', 'a' => 'جواب.']],
        ], JSON_UNESCAPED_UNICODE));

        $response = $this->postJson(route('admin.ai.post-creator.generate'), [
            'topic'     => 'موضوع مقالهٔ آزمایشی',
            'type'      => 'article',
            'with_meta' => true,
            'with_tags' => true,
            'with_faq'  => true,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $data = $response->json('data');
        $this->assertSame('عنوان از endpoint', $data['title']);
        $this->assertSame(['کلیدواژه'], $data['keywords']);
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('provider', $data);

        // ── خاموشی امکان → ۴۰۳ ──
        AiSetting::set('post_creator_enabled', '0');
        AiSetting::flushCache();

        $this->postJson(route('admin.ai.post-creator.generate'), ['topic' => 'موضوع'])
            ->assertStatus(403)
            ->assertJsonPath('ok', false);
    }

    /* ================= ۹) endpoint دستیار شناور ================= */

    #[Test]
    #[TestDox('بند ۱۰ — POST assist: متن تولیدی برای ادمین؛ خطای سرویس → پیام تمیز JSON')]
    public function assist_endpoint_json_and_failure_is_clean(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();
        $this->bindFakeProvider(null, 'متن تولیدشده برای فیلد توضیح محصول.');

        $this->postJson(route('admin.ai.assist.generate'), [
            'target' => 'product',
            'field'  => 'description',
            'title'  => 'هدفون بی‌سیم مدل X',
            'context' => 'ویژگی‌ها: نویزکنسلینگ، ۳۰ ساعت باتری',
        ])->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('text', 'متن تولیدشده برای فیلد توضیح محصول.');

        // ── خطای سرویس → پیام تمیز (بدون نشت) ──
        $this->bindFakeProvider(new \RuntimeException('کلید API نامعتبر است.'));

        $this->postJson(route('admin.ai.assist.generate'), ['target' => 'post', 'title' => 'عنوان مقالهٔ آزمایشی'])
            ->assertStatus(502)
            ->assertJson(['ok' => false])
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'کلید API'));
    }

    /* ================= ۱۰) ذخیرهٔ تنظیمات جدید ================= */

    #[Test]
    #[TestDox('بند ۱۱ — ذخیرهٔ تنظیمات فاز ۹۶: پرووایدر دوم، دستیار، برند، اتوبلاگ و تلاش مجدد')]
    public function settings_persist_phase96_keys(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        $this->post(route('admin.ai.settings.update'), [
            'provider'                  => 'deepseek',
            'deepseek_enabled'          => '1',
            'deepseek_base_url'         => 'https://api.deepseek.com/v1/',
            'deepseek_model'            => 'deepseek-chat',
            'deepseek_api_key'          => 'sk-ds-new',
            'request_retries'           => '3',
            'post_creator_enabled'      => '1',
            'assistant_enabled'         => '1',
            'assistant_position'        => 'top-right',
            'assistant_scope'           => 'all',
            'brand_name'                => 'فروشگاه آزمایشی',
            'brand_audience'            => 'نوجوانان علاقه‌مند به گیمینگ',
            'brand_usp'                 => 'ارسال رایگان بالای ۵۰۰ هزار تومان',
            'brand_forbidden_words'     => 'ارزان‌ترین، شکسته',
            'autoblog_enabled'          => '1',
            'autoblog_frequency'        => 'hourly',
            'autoblog_topics'           => "موضوع اول\nموضوع دوم",
            'ai_enabled'                => '1',
        ])->assertRedirect();

        AiSetting::flushCache();

        $this->assertSame('deepseek', AiSetting::get('provider'));
        $this->assertSame('1', AiSetting::get('deepseek_enabled'));
        $this->assertSame('https://api.deepseek.com/v1', AiSetting::get('deepseek_base_url'), 'اسلش انتهایی base_url باید حذف شود.');
        $this->assertSame('deepseek-chat', AiSetting::get('deepseek_model'));
        $this->assertSame('sk-ds-new', AiSetting::get('deepseek_api_key'));
        $this->assertSame('3', AiSetting::get('request_retries'));
        $this->assertSame('top-right', AiSetting::get('assistant_position'));
        $this->assertSame('all', AiSetting::get('assistant_scope'));
        $this->assertSame('فروشگاه آزمایشی', AiSetting::get('brand_name'));
        $this->assertSame('hourly', AiSetting::get('autoblog_frequency'));

        // موقعیت نامعتبر → پیش‌فرض
        $this->post(route('admin.ai.settings.update'), [
            'assistant_position' => 'mid-center-fake',
            'ai_enabled'         => '1',
        ])->assertRedirect();
        AiSetting::flushCache();
        $this->assertSame('bottom-left', AiSetting::get('assistant_position'));
    }

    /* ================= ۱۱) خروجی CSV مصرف ================= */

    #[Test]
    #[TestDox('بند ۱۲ — خروجی CSV مصرف: BOM + هدر فارسی + ردیف لاگ')]
    public function usage_export_csv_with_bom(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        AiUsageLog::record([
            'feature' => 'writer',
            'provider' => 'openai',
            'status' => 'success',
            'prompt_text' => 'موضوع آزمایشی خروجی',
            'response_text' => 'پاسخ آزمایشی',
            'prompt_tokens' => 100,
            'completion_tokens' => 200,
            'admin_id' => 1,
            'ip' => '127.0.0.1',
        ]);

        $response = $this->get(route('admin.ai.usage.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'CSV باید با BOM شروع شود (Excel فارسی).');
        $this->assertStringContainsString('قابلیت', $content);
        $this->assertStringContainsString('پرووایدر', $content);
        $this->assertStringContainsString('موضوع آزمایشی خروجی', $content);
    }
}
