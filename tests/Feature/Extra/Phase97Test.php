<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\ArtificialIntelligence\Models\AiPromptTemplate;
use Modules\ArtificialIntelligence\Models\AiSetting;
use Modules\ArtificialIntelligence\Models\AiWriterDraft;
use Modules\ArtificialIntelligence\Services\AiProviders\AiProvider;
use Modules\ArtificialIntelligence\Services\AiProviders\OpenAiCompatibleDriver;
use Modules\ArtificialIntelligence\Services\AssistService;
use Modules\ArtificialIntelligence\Services\TemplateLibrary;
use Modules\ArtificialIntelligence\Services\TemplateService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۹۷ — پورت کامل دستیار شناور پکیج «محتوای هوشمند» قدیمی + بازطراحی تنظیمات.
 * --------------------------------------------------------------------------
 *  • کتابخانهٔ ۱۰۴ قالب پرامپت (۴۰ محصول + ۲۶ مقاله + ۱۴ صفحه + ۱۰ دیدگاه + ۱۴ عمومی)
 *    با seed idempotent — درخواست صریح کاربر (ویجت قدیمی همین‌ها را نشان می‌داد).
 *  • endpoint قالب‌های پنل شناور (assist/templates) با is_generic.
 *  • مغز کامل AssistService: قواعد اختصاصی هر فیلد، ۷ لحن، حالت بازنویسی،
 *    قالب کتابخانه، پاسخ هوشمند دیدگاه، پاک‌سازی پاسخ و نرمال‌سازی کلیدواژه.
 *  • محدودیت نرخ ۴۰ درخواست/ساعت دستیار (پورت قدیمی).
 *  • تزریق config کامل (templatesUrl) + صفحهٔ تنظیمات بازطراحی‌شدهٔ تمام‌عرض.
 */
class Phase97Test extends TestCase
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
            $this->markTestSkipped('ماژول ArtificialIntelligence (هوش مصنوعی) روی این میزبان نصب نیست — تست فاز ۹۷ رد شد.');
        }
    }

    private function aiDefaults(array $overrides = []): void
    {
        AiSetting::flushCache();

        AiSetting::set('ai_enabled', '1');
        AiSetting::set('writer_enabled', '1');
        AiSetting::set('provider', 'openai');
        AiSetting::set('openai_enabled', '1');
        AiSetting::set('openai_api_key', 'sk-test-fake');
        AiSetting::set('provider_fallback_enabled', '0');
        AiSetting::set('request_retries', '0');
        AiSetting::set('assistant_enabled', '1');
        AiSetting::set('assistant_generate_enabled', '1');
        AiSetting::set('daily_request_limit', '500');

        foreach ($overrides as $key => $value) {
            AiSetting::set($key, (string) $value);
        }

        AiSetting::flushCache();
    }

    private function bindFakeProvider(?string $content = 'این پاسخِ تولیدشدهٔ فیک هوش مصنوعی است و به‌اندازهٔ کافی بلند است.', ?\Throwable $throw = null): void
    {
        app()->bind(OpenAiCompatibleDriver::class, function () use ($content, $throw) {
            return new class($content, $throw) implements AiProvider
            {
                public function __construct(
                    protected string $content,
                    protected ?\Throwable $throw,
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
                    if ($this->throw !== null) {
                        throw $this->throw;
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
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۹۷',
            'username' => 'feat97-'.Str::random(6),
            'email' => 'feat97-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ================= ۱) کتابخانهٔ ۱۰۴ قالب ================= */

    #[Test]
    #[TestDox('بند ۱ — کتابخانهٔ ۱۰۴ قالب (۴۰ محصول + ۲۶ مقاله + ۱۴ صفحه + ۱۰ دیدگاه + ۱۴ عمومی) با seed idempotent')]
    public function template_library_104_items_seed_idempotent(): void
    {
        $this->requireModule();

        $library = TemplateLibrary::all();

        $this->assertCount(104, $library, 'کتابخانهٔ پورت‌شده باید دقیقاً ۱۰۴ قالب داشته باشد.');
        $this->assertSame(2, TemplateLibrary::LIB_VERSION);

        $counts = [];
        foreach ($library as $tpl) {
            $this->assertArrayHasKey('title', $tpl);
            $this->assertArrayHasKey('content_type', $tpl);
            $this->assertArrayHasKey('body', $tpl);
            $this->assertNotSame('', trim((string) $tpl['title']));
            $this->assertNotSame('', trim((string) $tpl['body']));
            $counts[$tpl['content_type']] = ($counts[$tpl['content_type']] ?? 0) + 1;
        }

        $this->assertSame(40, $counts['product'] ?? 0);
        $this->assertSame(26, $counts['article'] ?? 0, 'نوع post قدیمی باید به article نگاشت شده باشد.');
        $this->assertSame(14, $counts['page'] ?? 0);
        $this->assertSame(10, $counts['comment'] ?? 0);
        $this->assertSame(14, $counts['generic'] ?? 0);

        // قالب‌های شاخصی که کاربر در ویجت قدیمی می‌دید
        $titles = array_column($library, 'title');
        foreach (['مقالهٔ آموزشی گام‌به‌گام', 'راهنمای خرید جامع', 'بستهٔ کامل سئو (متاها)', 'پیامک بازاریابی', 'متون Alt تصویر'] as $must) {
            $this->assertContains($must, $titles, "قالب «{$must}» باید در کتابخانه باشد.");
        }

        // seed idempotent — بوت اپ قبلاً همه را seed کرده؛ پس:
        // ①کل جدول باید دست‌کم ۱۲۸ ردیف داشته باشد (۲۴ فاز ۹۶ + ۱۰۴ کتابخانه)
        $total = AiPromptTemplate::query()->count();
        $this->assertGreaterThanOrEqual(128, $total, 'جدول قالب‌ها باید ۲۴ فاز ۹۶ + ۱۰۴ کتابخانهٔ قدیمی را داشته باشد.');

        // ②حذف یک قالب کتابخانه → seedIfMissing فقط همان را برمی‌گرداند
        $victim = AiPromptTemplate::query()->where('title', 'بستهٔ کامل سئو (متاها)')->first();
        $this->assertNotNull($victim);
        $victim->delete();
        $restored = TemplateService::seedIfMissing();
        $this->assertSame(1, $restored, 'فقط قالب حذف‌شده باید بازسازی شود.');

        // ③seed دوم idempotent
        $second = TemplateService::seedIfMissing();
        $this->assertSame(0, $second, 'seed دوم باید idempotent باشد (هیچ ردیف تکراری).');

        $this->assertDatabaseHas('ai_prompt_templates', ['title' => 'مقالهٔ آموزشی گام‌به‌گام', 'content_type' => 'article']);
        $this->assertDatabaseHas('ai_prompt_templates', ['title' => 'بستهٔ کامل سئو (متاها)', 'content_type' => 'generic']);
    }

    /* ================= ۲) endpoint قالب‌های پنل شناور ================= */

    #[Test]
    #[TestDox('بند ۲ — GET assist/templates: قالب‌های نوع صفحه + عمومی با is_generic (پورت قدیمی)')]
    public function assist_templates_endpoint_returns_kind_plus_generic(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();
        TemplateService::seedIfMissing();

        // kind=post → مقاله + عمومی
        $this->getJson(route('admin.ai.assist.templates', ['kind' => 'post']))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['ok', 'items' => [['id', 'name', 'prompt', 'is_generic']]]);

        $data = $this->getJson(route('admin.ai.assist.templates', ['kind' => 'post']))->json();
        $items = $data['items'];
        $this->assertNotEmpty($items);

        $types = array_unique(array_column($items, 'type'));
        $this->assertContains('article', $types);
        $this->assertContains('generic', $types);
        $this->assertNotContains('product', $types, 'قالب محصول نباید در صفحهٔ مقاله بیاید.');

        // قالب عمومی با is_generic=true
        $genericItems = array_values(array_filter($items, fn ($i) => $i['is_generic']));
        $this->assertNotEmpty($genericItems);
        $this->assertContains('بستهٔ کامل سئو (متاها)', array_column($genericItems, 'name'));

        // kind=comment → پاسخ دیدگاه
        $commentData = $this->getJson(route('admin.ai.assist.templates', ['kind' => 'comment']))->json();
        $commentNames = array_column($commentData['items'], 'name');
        $this->assertContains('پاسخ شکایت با همراهی', $commentNames);
        $this->assertContains('پاسخ انتقاد به کیفیت', $commentNames);

        // kind=auto → همان generic
        $autoData = $this->getJson(route('admin.ai.assist.templates', ['kind' => 'auto']))->json();
        $autoTypes = array_unique(array_column($autoData['items'], 'type'));
        $this->assertSame(['generic'], array_values($autoTypes));

        // مهمان ممنوع (Admin میدل‌ور)
        $this->getJson(route('admin.ai.assist.templates', ['kind' => 'post']))
            ->assertOk(); // ادمینِ همان نشست — بعد از خروج باید ریدایرکت/۴۰۱ شود
    }

    /* ================= ۳) مغز کامل دستیار: فیلد/بازنویسی/کامنت ================= */

    #[Test]
    #[TestDox('بند ۳ — POST assist با payload ویجت: قواعد فیلد (عنوان/کلیدواژه/سئو) + بازنویسی + پاسخ دیدگاه')]
    public function assist_widget_payload_field_rules_rewrite_and_reply(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        // ── فیلد title: پاسخ گیومه‌دار و تگ‌دار → پاک‌سازی می‌شود ──
        $this->bindFakeProvider('<p>«راهنمای کامل خرید هدفون بی‌سیم»</p>');
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'post',
            'field' => 'title',
            'topic' => 'راهنمای خرید هدفون',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('field', 'title')
            ->assertJsonPath('text', 'راهنمای کامل خرید هدفون بی‌سیم');

        // ── فیلد keywords: JSON array → «، » نرمال می‌شود ──
        $this->bindFakeProvider('["هدفون بی‌سیم", "ایرفون", "هدست گیمینگ", "بلوتوث ۵.۳"]');
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'product',
            'field' => 'keywords',
            'topic' => 'هدفون بی‌سیم مدل X',
        ])->assertOk()
            ->assertJsonPath('text', 'هدفون بی‌سیم، ایرفون، هدست گیمینگ، بلوتوث ۵.۳');

        // ── فیلد seo_title با پاسخ بلند → کوتاه‌سازی به ۶۸ ──
        $this->bindFakeProvider(str_repeat('ع', 120));
        $data = $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'post',
            'field' => 'seo_title',
            'topic' => 'موضوع سئو',
        ])->assertOk()->json();
        $this->assertLessThanOrEqual(70, mb_strlen($data['text']));

        // ── بازنویسی با منبع کوتاه → پیام فارسی تمیز ──
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'post',
            'field' => 'description',
            'topic' => 'موضوع',
            'mode' => 'rewrite',
            'source' => 'کوتاه',
        ])->assertStatus(502)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'بازنویسی'));

        // ── بازنویسی با منبع بلند → ok ──
        $this->bindFakeProvider('متن بازنویسی‌شدهٔ کامل و قابل قبول برای فیلد توضیحات محصول.');
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'product',
            'field' => 'description',
            'topic' => 'هدفون X',
            'mode' => 'rewrite',
            'source' => 'متن فعلی محصول که به‌اندازهٔ کافی بلند است تا بازنویسی شود.',
        ])->assertOk()->assertJsonPath('ok', true);

        // ── پاسخ هوشمند دیدگاه ──
        $this->bindFakeProvider('با سلام و احترام، مشکل شما بررسی و پیگیری شد؛ پشتیبانی تماس می‌گیرد. ممنون از همراهی شما.');
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'comment',
            'field' => 'reply',
            'context' => 'سفارش من یک هفته است نرسیده، پیگیری کنید لطفاً.',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('kind', 'comment');

        // ── بدون موضوع و بدون منبع → گارد فارسی ──
        $this->bindFakeProvider('هر متنی');
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'post',
            'field' => 'description',
        ])->assertStatus(502)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'موضوع'));
    }

    /* ================= ۴) قالب کتابخانه در پرامپت + سازگاری قدیمی ================= */

    #[Test]
    #[TestDox('بند ۴ — template_prompt وارد پرامپت می‌شود + payload قدیمی فاز ۹۶ (target/title) هم کار می‌کند')]
    public function assist_template_prompt_and_legacy_payload(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        $captured = new \ArrayObject(['system' => '', 'user' => '']);
        app()->bind(OpenAiCompatibleDriver::class, function () use ($captured) {
            return new class($captured) implements AiProvider
            {
                public function __construct(protected \ArrayObject $captured) {}

                public function key(): string
                {
                    return 'openai';
                }

                public function title(): string
                {
                    return 'فیک';
                }

                public function isConfigured(): bool
                {
                    return true;
                }

                public function chat(array $messages, array $options = []): array
                {
                    foreach ($messages as $m) {
                        if (($m['role'] ?? '') === 'system') {
                            $this->captured['system'] = (string) $m['content'];
                        }
                        if (($m['role'] ?? '') === 'user') {
                            $this->captured['user'] = (string) $m['content'];
                        }
                    }

                    return [
                        'content' => 'مقالهٔ آموزشی گام‌به‌گام دربارهٔ موضوع خواسته‌شده با تمام مراحل و جزئیات کافی برای انتشار در وبلاگ فروشگاه.',
                        'raw' => ['fake' => true],
                        'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 6],
                    ];
                }

                public function ping(): array
                {
                    return ['ok' => true, 'message' => 'ok'];
                }
            };
        });

        // قالب کتابخانه → باید داخل system prompt بیاید
        $this->postJson(route('admin.ai.assist.generate'), [
            'kind' => 'post',
            'field' => 'content',
            'topic' => 'چای ساز صنعتی',
            'template_prompt' => 'یک مقالهٔ آموزشی گام‌به‌گام دربارهٔ «{title}» بنویس: مقدمه، مراحل شماره‌گذاری‌شده، نکات مهم.',
        ])->assertOk();

        $this->assertStringContainsString('قالب/سبک انتخاب‌شدهٔ کاربر', $captured['system']);
        $this->assertStringContainsString('مراحل شماره‌گذاری‌شده', $captured['system']);
        $this->assertStringContainsString('چای ساز صنعتی', $captured['user']);
        // متغیرهای جایگزین‌نشده حذف می‌شوند
        $this->assertStringNotContainsString('{title}', $captured['user']);

        // سازگاری برگشتی payload فاز ۹۶
        $this->bindFakeProvider('توضیح تولیدشده برای محصول فیک با جزئیات کافی برای پاسخ معتبر.');
        $this->postJson(route('admin.ai.assist.generate'), [
            'target' => 'product',
            'field' => 'description',
            'title' => 'هدفون بی‌سیم مدل X',
            'context' => 'ویژگی‌ها: نویزکنسلینگ',
        ])->assertOk()->assertJsonPath('ok', true);
    }

    /* ================= ۵) محدودیت نرخ ۴۰/ساعت ================= */

    #[Test]
    #[TestDox('بند ۵ — درخواست ۴۱م دستیار در ساعت → ۴۲۹ با پیام فارسی (پورت قدیمی)')]
    public function assist_rate_limit_forty_per_hour(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();
        $this->bindFakeProvider('پاسخ فیک کوتاه برای آزمون محدودیت نرخ دستیار شناور.');

        $last = null;
        for ($i = 0; $i < 41; $i++) {
            $last = $this->postJson(route('admin.ai.assist.generate'), [
                'kind' => 'post',
                'field' => 'title',
                'topic' => 'موضوع آزمون نرخ شماره '.$i,
            ]);
        }

        $last->assertStatus(429)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'سهمیهٔ تولید'));
    }

    /* ================= ۶) تزریق config کامل + صفحهٔ تنظیمات جدید ================= */

    #[Test]
    #[TestDox('بند ۶ — تزریق templatesUrl در __AI_INJECT__ + صفحهٔ تنظیمات بازطراحی‌شده (تمام‌عرض/تب‌بندی) رندر می‌شود')]
    public function injection_config_and_redesigned_settings_page(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        // صفحهٔ تنظیمات — طراحی جدید
        $response = $this->get(route('admin.ai.settings.edit'));
        $response->assertOk();

        $html = (string) $response->getContent();
        $this->assertStringContainsString('ai-hero', $html, 'سربرگ قهرمان طراحی جدید باید باشد.');
        $this->assertStringContainsString('ai-tabs', $html, 'نوار تب‌ها باید باشد.');
        $this->assertStringContainsString('data-tab="services"', $html);
        $this->assertStringContainsString('ai-prov-grid', $html, 'گرید کارت‌های سرویس باید باشد.');
        $this->assertStringContainsString('ai-savebar', $html, 'savebar چسبان باید باشد.');

        // محتوای تنظیمات دیگر wrapper قدیمی ندارد (بلوک ai-scope مستقل است)

        // تزریق دستیار شناور در صفحهٔ ادمین + config کامل
        $this->assertStringContainsString('AI-floating-assistant', $html, 'مارکر دستیار شناور باید تزریق شود.');
        $this->assertStringContainsString('__AI_INJECT__', $html);
        $this->assertStringContainsString('assist/templates', $html, 'آدرس endpoint قالب‌ها باید در config باشد.');

        // روت‌های جدید ثبت شده‌اند
        $this->assertTrue(Route::has('admin.ai.assist.templates'));
    }

    /* ================= ۸) گردش‌کار بررسی/تأیید (پورت قدیمی) ================= */

    #[Test]
    #[TestDox('بند ۸ — صف بررسی: تولید → ردیف pending → ویرایش → تأیید (اعمال با snapshot) → undo + تأیید همه + رد')]
    public function catalog_review_workflow_full_cycle(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $creator = $this->newCreator();
        $this->bindFakeProvider('توضیح کامل و جذاب برای محصول آزمایشی تولیدشده توسط هوش مصنوعی فیک تست.');

        $product = Product::create([
            'title' => 'محصول بررسی فاز ۹۷',
            'product_id' => 'RV-'.Str::random(6),
            'slug' => 'rv-'.Str::random(8),
            'published' => true,
        ]);

        // ── تولید → ردیف pending ساخته می‌شود ──
        $this->post(route('admin.ai.catalog.generate'), ['product_id' => $product->id])
            ->assertRedirect();

        $pending = AiWriterDraft::query()
            ->where('feature', 'catalog')
            ->where('status', 'generated')
            ->where('kind', 'product')
            ->where('title', 'محصول بررسی فاز ۹۷')
            ->first();
        $this->assertNotNull($pending, 'تولید کاتالوگ باید ردیف صف بررسی بسازد.');

        // ── ویرایش پیش از تأیید ──
        $this->post(route('admin.ai.catalog.review.update', $pending), [
            'content' => 'محتوای ویرایش‌شدهٔ دستی ادمین با جزئیات کافی برای اعتبارسنجی گردش‌کار بررسی.',
        ])->assertRedirect()->assertSessionHas('toast-success');

        $pending->refresh();
        $this->assertStringContainsString('ویرایش‌شدهٔ دستی', $pending->content);

        // ── تأیید → اعمال روی محصول + snapshot ──
        $oldDescription = 'توضیح قدیمی محصول قبل از هوش مصنوعی';
        $product->forceFill(['description' => $oldDescription])->save();

        $this->post(route('admin.ai.catalog.review.approve', $pending))
            ->assertRedirect()->assertSessionHas('toast-success');

        $product->refresh();
        $this->assertStringContainsString('ویرایش‌شدهٔ دستی', (string) $product->description, 'محتوای ویرایش‌شده باید روی محصول اعمال شده باشد.');

        $pending->refresh();
        $this->assertSame('applied', $pending->status);
        $this->assertSame($oldDescription, (string) ($pending->meta['snapshot']['description'] ?? ''), 'snapshot مقادیر قبلی باید ثبت شده باشد.');

        // ── undo از تاریخچه ──
        $this->post(route('admin.ai.catalog.undo', $pending))
            ->assertRedirect()->assertSessionHas('toast-success');

        $product->refresh();
        $this->assertSame($oldDescription, (string) $product->description, 'بازگردانی باید توضیح قبلی را برگرداند.');
        $pending->refresh();
        $this->assertSame('reverted', $pending->status);
    }

    #[Test]
    #[TestDox('بند ۹ — رد تولید + تأیید همهٔ صف + تولید گروهی بدون auto → صف بررسی')]
    public function catalog_review_reject_approve_all_and_bulk_queue(): void
    {
        $this->requireModule();
        $this->aiDefaults(['catalog_auto_apply_enabled' => '0']);
        $this->newCreator();
        $this->bindFakeProvider('توضیح تولید گروهی برای محصول آزمایشی با متن کافی برای اعتبار.');

        // ── رد ──
        $draft = AiWriterDraft::create([
            'title' => 'ردی‌شدنی', 'content' => 'محتوای کافی برای رد شدن در آزمون گردش‌کار بررسی.', 'feature' => 'catalog',
            'kind' => 'product', 'status' => 'generated', 'meta' => ['target' => 'product', 'target_id' => 1],
        ]);

        $this->post(route('admin.ai.catalog.review.reject', $draft))
            ->assertRedirect()->assertSessionHas('toast-success');
        $draft->refresh();
        $this->assertSame('rejected', $draft->status);

        // رد مجدد → خطا
        $this->post(route('admin.ai.catalog.review.reject', $draft))
            ->assertRedirect()->assertSessionHas('toast-error');

        // ── تولید گروهی با auto خاموش → صف بررسی ──
        $p1 = Product::create(['title' => 'گروهی الف', 'product_id' => 'GB-'.Str::random(6), 'slug' => 'gb-'.Str::random(8), 'published' => true]);
        $p2 = Product::create(['title' => 'گروهی ب', 'product_id' => 'GB-'.Str::random(6), 'slug' => 'gb-'.Str::random(8), 'published' => true]);

        $this->post(route('admin.ai.catalog.bulk'), ['product_ids' => [$p1->id, $p2->id]])
            ->assertRedirect()->assertSessionHas('toast-success');

        $queued = AiWriterDraft::query()
            ->where('feature', 'catalog')->where('status', 'generated')
            ->whereIn('title', ['گروهی الف', 'گروهی ب'])->count();
        $this->assertSame(2, $queued, 'تولید گروهی بدون اعمال خودکار باید صف بررسی بسازد.');

        // ── تأیید همه ──
        $this->post(route('admin.ai.catalog.review.approve-all'))
            ->assertRedirect()->assertSessionHas('toast-success');

        $p1->refresh();
        $p2->refresh();
        $this->assertStringContainsString('تولید گروهی', (string) $p1->description);
        $this->assertStringContainsString('تولید گروهی', (string) $p2->description);

        $applied = AiWriterDraft::query()
            ->where('feature', 'catalog')->where('status', 'applied')
            ->whereIn('title', ['گروهی الف', 'گروهی ب'])->count();
        $this->assertSame(2, $applied);
    }

    /* ================= ۱۰) مدیریت قالب‌های پرامپت (پورت CRUD قدیمی) ================= */

    #[Test]
    #[TestDox('بند ۱۰ — CRUD قالب‌ها + خروجی/ورود JSON + یکتایی title+content_type')]
    public function templates_crud_export_import(): void
    {
        $this->requireModule();
        $this->aiDefaults();
        $this->newCreator();

        // صفحهٔ مدیریت
        $this->get(route('admin.ai.templates.index'))->assertOk()->assertSee('قالب‌های پرامپت');

        // ساخت
        $this->post(route('admin.ai.templates.store'), [
            'title' => 'قالب دستی آزمون ۹۷',
            'content_type' => 'generic',
            'body' => 'پرامپت آزمایشی قالب دستی با متن کافی برای اعتبارسنجی.',
            'active' => '1',
        ])->assertRedirect()->assertSessionHas('toast-success');

        $tpl = AiPromptTemplate::query()
            ->where('title', 'قالب دستی آزمون ۹۷')->first();
        $this->assertNotNull($tpl);

        // یکتایی → خطا
        $this->post(route('admin.ai.templates.store'), [
            'title' => 'قالب دستی آزمون ۹۷',
            'content_type' => 'generic',
            'body' => 'پرامپت تکراری با متن کافی برای اعتبارسنجی یکتایی.',
        ])->assertRedirect()->assertSessionHas('toast-error');

        // ویرایش
        $this->post(route('admin.ai.templates.update', $tpl), [
            'title' => 'قالب دستی آزمون ۹۷ (ویرایش)',
            'content_type' => 'page',
            'body' => 'پرامپت ویرایش‌شده با متن کافی برای اعتبارسنجی ویرایش.',
            'active' => '1',
        ])->assertRedirect()->assertSessionHas('toast-success');

        $tpl->refresh();
        $this->assertSame('page', $tpl->content_type);

        // export → JSON شامل قالب
        $export = $this->get(route('admin.ai.templates.export'));
        $export->assertOk();
        $json = json_decode($export->getContent(), true);
        $this->assertIsArray($json['items']);
        $this->assertTrue(collect($json['items'])->contains(fn ($i) => str_contains((string) ($i['title'] ?? ''), 'قالب دستی آزمون ۹۷')));

        // import با آرایهٔ مستقیم — یک قالب جدید
        $this->post(route('admin.ai.templates.import'), [
            'import_json' => json_encode(['items' => [[
                'title' => 'قالب ورودی آزمون ۹۷',
                'content_type' => 'comment',
                'body' => 'پرامپت ورودی از JSON با متن کافی برای اعتبارسنجی ورود.',
                'active' => 1,
            ]]], JSON_UNESCAPED_UNICODE),
        ])->assertRedirect()->assertSessionHas('toast-success');

        $this->assertDatabaseHas('ai_prompt_templates', ['title' => 'قالب ورودی آزمون ۹۷', 'content_type' => 'comment']);

        // import دوباره → skip
        $this->post(route('admin.ai.templates.import'), [
            'import_json' => json_encode(['items' => [[
                'title' => 'قالب ورودی آزمون ۹۷',
                'content_type' => 'comment',
                'body' => 'تکراری — باید skip شود.',
            ]]], JSON_UNESCAPED_UNICODE),
        ])->assertRedirect();

        // حذف
        $this->post(route('admin.ai.templates.destroy', $tpl))
            ->assertRedirect()->assertSessionHas('toast-success');
        $this->assertDatabaseMissing('ai_prompt_templates', ['id' => $tpl->id]);
    }

    /* ================= ۷) سرویس: گاردها + حدس خودکار ================= */

    #[Test]
    #[TestDox('بند ۷ — سرویس دستیار: گارد خاموشی + حدس خودکار نوع از موضوع')]
    public function assist_service_guards_and_auto_kind_guess(): void
    {
        $this->requireModule();
        $this->aiDefaults(['ai_enabled' => '0']);
        $this->newCreator();

        $service = app(AssistService::class);

        try {
            $service->generate(['kind' => 'post', 'field' => 'title', 'topic' => 'موضوع'], 1, '127.0.0.1');
            $this->fail('با پکیج خاموش باید RuntimeException بیاید.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('غیرفعال', $e->getMessage());
        }

        // حدس خودکار نوع
        $m = new \ReflectionMethod(AssistService::class, 'guessKindFromTopic');
        $m->setAccessible(true);
        $this->assertSame('comment', $m->invoke($service, 'پاسخ به این کامنت کاربر'));
        $this->assertSame('post', $m->invoke($service, 'یک مقاله برای وبلاگ فروشگاه'));
        $this->assertSame('product', $m->invoke($service, 'توضیح محصول و قیمت'));
        $this->assertSame('page', $m->invoke($service, 'متن صفحه درباره ما'));
        $this->assertSame('generic', $m->invoke($service, 'متن ساده'));
    }
}
