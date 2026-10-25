<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Settings\Others;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۴ — پنج درخواست کاربر دربارهٔ هشدار کاهش قیمت و پنل:
 *  ۱) موقعیت دکمه: گزینه‌های «شناور در گوشهٔ صفحه» (با ۹ موقعیت) و «در تگ دلخواه (سلکتور)»
 *     با اینپوت کلاس/آیدی مثل نمونهٔ CheckoutTurbo + پیش‌نمایش زنده
 *  ۲) حذف دکمهٔ ذخیرهٔ بالایی صفحهٔ «تنظیمات دیگر» (ذخیره فقط از نوار پایین)
 *  ۳) خاموش‌کردن قابلیت → دکمه و مودال از فرانت حذف شوند (اثبات با رندر واقعی صفحهٔ محصول)
 *     + کلیدهای قابلیت در non_language_options (مستقل از زبان)
 *  ۴) منوی «هشدار کاهش قیمت» زیر «اطلاع‌رسانی موجودی» در «محصولات» (پنل جدید و قدیم) + پرمیژن general.php
 *  ۵) مودال بلاک نشست‌های فعال: تیک/بوردر انتخاب با بایندینگ مستقیم $wire
 */
class Phase84Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();

        // مقادیر پیش‌فرض مستقل از رکوردهای قبلی
        foreach ([
            'dt_price_alert_status'            => '1',
            'dt_price_alert_button_placement'  => '',
            'dt_price_alert_float_pos'         => '',
            'dt_price_alert_selectors'         => '',
        ] as $key => $value) {
            option_update($key, $value);
        }
        Cache::flush();
    }

    protected function tearDown(): void
    {
        option_update('dt_price_alert_status', '1');
        option_update('dt_price_alert_button_placement', '');
        option_update('dt_price_alert_float_pos', '');
        option_update('dt_price_alert_selectors', '');
        Cache::flush();

        parent::tearDown();
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۸۴',
            'username'   => 'feat84-'.Str::random(6),
            'email'      => 'feat84-'.Str::random(6).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
    }

    /* ================= ۱ و ۲) موقعیت دکمه: شناور + تگ دلخواه ================= */

    #[Test]
    #[TestDox('موقعیت دکمه: گزینه‌های «شناور» و «تگ دلخواه» ذخیره می‌شوند (وایت‌لیست کامل)')]
    public function placement_accepts_float_and_custom_selector(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('settings.others', fn ($user = null) => true);

        Livewire::test(Others::class)
            ->set('dt_price_alert_button_placement', 'float')
            ->set('dt_price_alert_float_pos', 'top-left')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('float', option('dt_price_alert_button_placement'));
        $this->assertSame('top-left', option('dt_price_alert_float_pos'));

        Livewire::test(Others::class)
            ->set('dt_price_alert_button_placement', 'custom-selector')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('custom-selector', option('dt_price_alert_button_placement'));

        // مقدار نامعتبر → پیش‌فرض
        option_update('dt_price_alert_button_placement', 'hacker');
        option_update('dt_price_alert_float_pos', 'hacker');
        Cache::flush();

        Livewire::test(Others::class)
            ->assertSet('dt_price_alert_button_placement', 'below-cart')
            ->assertSet('dt_price_alert_float_pos', 'bottom-right');

        // وایت‌لیست در ذخیره: موقعیت نامعتبر → پیش‌فرض
        Livewire::test(Others::class)
            ->set('dt_price_alert_float_pos', 'outer-space')
            ->call('save');
        $this->assertSame('bottom-right', option('dt_price_alert_float_pos'));
    }

    #[Test]
    #[TestDox('موقعیت‌های شناور دقیقاً ۹گانه‌اند (چهار گوشه + چهار لبه + وسط)')]
    public function float_positions_are_nine(): void
    {
        $this->assertSame(9, count(Others::FLOAT_POSITIONS));
        foreach (['top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'] as $pos) {
            $this->assertContains($pos, Others::FLOAT_POSITIONS);
        }
        foreach (Others::FLOAT_POSITIONS as $pos) {
            $this->assertArrayHasKey($pos, Others::FLOAT_POSITION_LABELS);
        }
    }

    #[Test]
    #[TestDox('سلکتورهای تگ دلخواه: پاک‌سازی XSS، سقف ۱۰، بدون تکرار + ذخیرهٔ JSON')]
    public function selectors_are_sanitized_and_persisted(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('settings.others', fn ($user = null) => true);

        $dangerous = [
            '.product-seller-row>.product-seller-row-price',
            '#buy-box',
            '<script>alert(1)</script>',          // خطرناک → پاک می‌شود به «scriptalert(1)/script»
            'javascript:alert(1)',                 // کولن مجاز است ولی < > حذف شده‌اند؛ کلیدهای خطرناک ساده نمی‌مانند
            '.dup',
            '.dup',
            str_repeat('.x', 200),                 // بلندتر از ۱۲۰ → حذف
        ];

        Livewire::test(Others::class)
            ->set('dt_price_alert_selectors', $dangerous)
            ->call('save')
            ->assertHasNoErrors();

        $saved = json_decode((string) option('dt_price_alert_selectors'), true);
        $this->assertIsArray($saved);
        $this->assertContains('.product-seller-row>.product-seller-row-price', $saved);
        $this->assertContains('#buy-box', $saved);
        $this->assertContains('.dup', $saved);
        $this->assertCount(1, array_keys($saved, '.dup'), 'تکراری‌ها حذف می‌شوند');
        $this->assertNotContains('<script>alert(1)</script>', $saved, 'کاراکترهای خطرناک (< >) حذف شده‌اند');
        $this->assertLessThanOrEqual(Others::SELECTORS_MAX, count($saved));
        foreach ($saved as $sel) {
            $this->assertStringNotContainsString('<', $sel, 'براکت بازِ HTML مجاز نیست');
            $this->assertLessThanOrEqual(120, mb_strlen($sel));
        }
        $this->assertNotContains(str_repeat('.x', 200), $saved, 'سلکتورِ بیش‌ازحد بلند حذف می‌شود');
    }

    #[Test]
    #[TestDox('اکشن‌های افزودن/حذف سلکتور (دکمهٔ «افزودن سلکتور» و سطل زباله) فوراً ذخیره می‌کنند')]
    public function add_and_remove_selector_actions_work(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');
        \Illuminate\Support\Facades\Gate::define('settings.others', fn ($user = null) => true);

        $component = Livewire::test(Others::class);

        $component->set('pa_selector_input', '.price-row>.price')
            ->call('paAddSelector')
            ->assertHasNoErrors();

        $this->assertSame(['.price-row>.price'], json_decode((string) option('dt_price_alert_selectors'), true));
        $this->assertSame('', $component->instance()->pa_selector_input);

        // افزودن نمونهٔ آماده (چیپ) + حذف با اندیس
        $component->call('paAddExampleSelector', '#buy-box')
            ->assertHasNoErrors();
        $saved = json_decode((string) option('dt_price_alert_selectors'), true);
        $this->assertContains('#buy-box', $saved);

        // چیپ خارج از لیست نمونه‌ها نادیده گرفته می‌شود (امنیتی)
        $component->call('paAddExampleSelector', 'body>*');
        $this->assertNotContains('body>*', json_decode((string) option('dt_price_alert_selectors'), true));

        $component->call('paRemoveSelector', 0)
            ->assertHasNoErrors();
        $saved = json_decode((string) option('dt_price_alert_selectors'), true);
        $this->assertNotContains('.price-row>.price', $saved);
    }

    #[Test]
    #[TestDox('parseSelectors: هم JSON و هم فرمت خط‌جداکنندهٔ قدیمی را می‌خواند')]
    public function parse_selectors_supports_json_and_legacy_lines(): void
    {
        $this->assertSame(['.a', '#b'], Others::parseSelectors('[".a", "#b"]'));
        $this->assertSame(['.a', '#b'], Others::parseSelectors(".a\n#b"));
        $this->assertSame(['.a'], Others::parseSelectors('.a'));
        $this->assertSame([], Others::parseSelectors(''));
        $this->assertSame([], Others::parseSelectors('   '));
    }

    /* ================= ۳) خاموش‌کردن قابلیت → فرانت تمیز (رندر واقعی) ================= */

    private function newFrontProduct(): Product
    {
        $category = Category::create([
            'title'     => 'دستهٔ فاز ۸۴',
            'slug'      => 'cat84-'.Str::random(8),
            'type'      => 'productcat',
            'published' => true,
        ]);

        $product = Product::create([
            'product_id'        => 'P'.Str::random(10),
            'title'             => 'محصول فاز ۸۴ '.Str::random(6),
            'title_en'          => 'Phase84 '.Str::random(6),
            'slug'              => 'p84-'.Str::random(10),
            'type'              => 'physical',
            'category_id'       => $category->id,
            'price_type'        => 'single-price',
            'weight'            => 500,
            'description'       => 'توضیحات',
            'short_description' => 'خلاصه',
            'special'           => false,
            'published'         => true,
            'status'            => 'Accept',
            'sell'              => 0,
            'view'              => 0,
        ]);
        $product->categories()->attach($category->id);

        Price::create([
            'product_id'     => $product->id,
            'price'          => 120000,
            'discount_price' => 120000,
            'regular_price'  => 120000,
            'discount'       => 0,
            'stock'          => 10,
            'published'      => true,
            'cart_min'       => 1,
            'cart_max'       => 5,
        ]);

        return $product->fresh();
    }

    #[Test]
    #[TestDox('بند ۳ — با خاموش‌کردن قابلیت، دکمه و مودال از صفحهٔ واقعی محصول حذف می‌شوند')]
    public function frontend_hides_button_and_modal_when_feature_is_off(): void
    {
        $product = $this->newFrontProduct();

        // روشن → دکمه + مودال هست
        option_update('dt_price_alert_status', '1');
        Cache::flush();
        $response = $this->get('/product/'.$product->slug);
        $response->assertOk();
        $this->assertStringContainsString('dt_price_alert_btn', $response->getContent());
        $this->assertStringContainsString('dt_pa_overlay', $response->getContent());

        // خاموش → هر دو حذف
        option_update('dt_price_alert_status', '0');
        Cache::flush();
        $response = $this->get('/product/'.$product->slug);
        $response->assertOk();
        $this->assertStringNotContainsString('dt_price_alert_btn', $response->getContent(), 'دکمه نباید رندر شود');
        $this->assertStringNotContainsString('dt_pa_overlay', $response->getContent(), 'مودال نباید رندر شود');
    }

    #[Test]
    #[TestDox('بند ۱ — حالت «شناور»: رپر fixed با کلاس موقعیت انتخابی رندر می‌شود')]
    public function frontend_renders_floating_button_at_chosen_corner(): void
    {
        $product = $this->newFrontProduct();

        option_update('dt_price_alert_button_placement', 'float');
        option_update('dt_price_alert_float_pos', 'bottom-left');
        Cache::flush();

        $response = $this->get('/product/'.$product->slug);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('dt-pa-float dt-pa-float--bottom-left', $html);
        $this->assertStringContainsString('.dt-pa-float{position:fixed', $html, 'CSS شناور اینلاین است');
    }

    #[Test]
    #[TestDox('بند ۱ — حالت «تگ دلخواه»: کانفیگ سلکتور + دکمهٔ مخفی + اسکریپت تزریق رندر می‌شود')]
    public function frontend_renders_custom_selector_injection(): void
    {
        $product = $this->newFrontProduct();

        option_update('dt_price_alert_button_placement', 'custom-selector');
        option_update('dt_price_alert_selectors', json_encode(['.product-seller-row>.product-seller-row-price', '#buy-box']));
        Cache::flush();

        $response = $this->get('/product/'.$product->slug);
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="dt_pa_inject_config"', $html);
        $this->assertStringContainsString('.product-seller-row&gt;.product-seller-row-price', $html, 'سلکتورها (escape‌شدهٔ HTML) در data-attribute');
        $this->assertStringContainsString('id="dt_price_alert_btn" hidden', $html, 'دکمهٔ منبع تا تزریق مخفی است');
        $this->assertStringContainsString('function dtPaInject', $html, 'اسکریپت تزریق اینلاین');
    }

    /* ================= ۴) منوی «محصولات» + پرمیژن ================= */

    #[Test]
    #[TestDox('بند ۴ — منوی پنل جدید: «هشدار کاهش قیمت» زیر «اطلاع‌رسانی موجودی» در محصولات + گیت فعال‌بودن')]
    public function new_panel_menu_sits_under_stock_notify_in_products(): void
    {
        $groups = config('admin-menu.groups');
        $order  = [];

        foreach ($groups as $group) {
            if (($group['key'] ?? '') !== 'products') {
                continue;
            }
            foreach ($group['items'] as $item) {
                foreach ($item['children'] ?? [$item] as $child) {
                    if (isset($child['route'])) {
                        $order[] = $child;
                    }
                }
            }
        }

        $routes     = array_column($order, 'route');
        $stockKey   = array_search('np.stock-notifies.index', $routes, true);
        $alertKey   = array_search('np.price-alerts.index', $routes, true);

        $this->assertNotFalse($stockKey, 'اطلاع‌رسانی موجودی در محصولات هست');
        $this->assertNotFalse($alertKey, 'هشدار کاهش قیمت در محصولات هست');
        $this->assertSame($stockKey + 1, $alertKey, 'دقیقاً بلافاصله بعد از اطلاع‌رسانی موجودی');

        $alertItem = $order[$alertKey];
        $this->assertSame('products.price-alert', $alertItem['permission']);
        $this->assertSame('dt_price_alert_status', $alertItem['enabled'], 'فقط وقتی قابلیت فعال باشد');

        // در هیچ جای منو (مثل اعلان‌ها) تکرار نشده باشد
        $occurrences = 0;
        array_walk_recursive($groups, function ($v) use (&$occurrences) {
            if ($v === 'np.price-alerts.index') {
                $occurrences++;
            }
        });
        $this->assertSame(1, $occurrences);
    }

    #[Test]
    #[TestDox('بند ۴ — سایدبار پنل قدیم: آیتم زیر «اطلاع از موجودی» منتقل و از اعلان‌ها حذف شد')]
    public function old_panel_sidebar_moved_under_stock_notify(): void
    {
        $sidebar = File::get(base_path('resources/views/back/partials/sidebar.blade.php'));

        // زیر اطلاع از موجودی
        $stockPos = mb_strpos($sidebar, "@can('products.stock-notify')");
        $alertPos = mb_strpos($sidebar, "admin.price-alerts.index");
        $this->assertNotFalse($stockPos);
        $this->assertNotFalse($alertPos);
        $this->assertTrue($alertPos > $stockPos && $alertPos - $stockPos < 1200, 'آیتم هشدار قیمت بلافاصله بعد از اطلاع از موجودی است');

        // گیت فعال‌بودن روی آیتم
        $this->assertStringContainsString("@if(option('dt_price_alert_status', '1') == '1')", $sidebar);

        // فقط یک‌بار در کل سایدبار (هر آیتم دو رخداد دارد: active_class + route)
        $this->assertSame(2, substr_count($sidebar, 'admin.price-alerts.index'));
    }

    #[Test]
    #[TestDox('بند ۴ — پرمیژن products.price-alert در config/general.php (بخش permissions) ثبت است')]
    public function price_alert_permission_exists_in_general_config(): void
    {
        $values = config('general.permissions.products.values');

        $this->assertArrayHasKey('price-alert', $values);
        $this->assertArrayHasKey('stock-notify', $values);
        $this->assertSame('مدیریت هشدار کاهش قیمت', $values['price-alert']);
    }

    /* ================= ریشهٔ تکمیلی بند ۳: کلیدها مستقل از زبان ================= */

    #[Test]
    #[TestDox('کلیدهای قابلیت‌های فروشگاه در non_language_options هستند (گیت در همهٔ زبان‌ها یکسان)')]
    public function feature_keys_are_non_language_options(): void
    {
        $nonLanguage = config('general.non_language_options');

        foreach (['dt_price_alert_status', 'dt_price_alert_button_placement', 'dt_price_alert_float_pos', 'dt_price_alert_selectors', 'dt_pre_order_status'] as $key) {
            $this->assertContains($key, $nonLanguage);
        }
    }

    /* ================= بند ۲) حذف دکمهٔ ذخیرهٔ بالایی ================= */

    #[Test]
    #[TestDox('بند ۲ — صفحهٔ «تنظیمات دیگر» پنل جدید فقط یک دکمهٔ ذخیره (نوار پایین) دارد')]
    public function others_settings_has_only_bottom_save_button(): void
    {
        $view = File::get(base_path('resources/views/admin-panel/livewire/admin/settings/others.blade.php'));

        $this->assertStringNotContainsString('form="others-form"', $view, 'دکمهٔ submit بالایی (اتصال به فرم از سربرگ) حذف شده است');
        $this->assertSame(1, substr_count($view, 'wire:submit="save"'), 'فرم فقط یک‌بار submit دارد');
        $this->assertSame(1, substr_count($view, '<button type="submit"'), 'فقط دکمهٔ ذخیرهٔ نوار پایین مانده است');
    }

    /* ================= بند ۵) مودال بلاک نشست‌های فعال ================= */

    #[Test]
    #[TestDox('بند ۵ — مودال بلاک: نوع بلاک/مدت/واحد سفارشی با تیک و بوردر مستقل از CSS ($wire) مشخص می‌شوند')]
    public function sessions_block_modal_has_wire_bound_selection_feedback(): void
    {
        $view = File::get(base_path('resources/views/admin-panel/livewire/admin/system/sessions.blade.php'));

        $this->assertStringContainsString('$wire.blockType ===', $view, 'بایندینگ مستقیم نوع بلاک');
        $this->assertStringContainsString('$wire.blockDuration ===', $view, 'بایندینگ مستقیم مدت بلاک');
        $this->assertStringContainsString('$wire.blockCustomUnit ===', $view, 'بایندینگ مستقیم واحد سفارشی');
        // ۳ بایندینگ به‌ازای هر کاشی (div + بج x-show + بج :style) — قالب حلقه + بن اکانت
        $this->assertSame(6, substr_count($view, '$wire.blockType ==='), 'قالب حلقهٔ ۵ نوع (۳ بایندینگ) + بن اکانت (۳ بایندینگ)');
        $this->assertSame(3, substr_count($view, '$wire.blockDuration ==='), '۶ مدت در قالب حلقه با ۳ بایندینگ');
        $this->assertSame(1, substr_count($view, '$wire.blockCustomUnit ==='), '۴ واحد در قالب حلقه با ۱ بایندینگ');
    }

    /* ================= UI پنل جدید: سلکت موقعیت + ۹pickers + ریپیتر ================= */

    #[Test]
    #[TestDox('UI پنل جدید: سلکت موقعیت با ۵ گزینه + انتخاب‌گر ۹موقعیته + ریپیتر سلکتور با چیپ‌های نمونه')]
    public function settings_partial_contains_new_placement_ui(): void
    {
        $partial = File::get(base_path('resources/views/admin-panel/livewire/admin/settings/partials/price-alert-settings.blade.php'));

        $this->assertStringContainsString('wire:model.live="dt_price_alert_button_placement"', $partial, 'سلکت موقعیت مثل نمونه');
        $this->assertSame(1, substr_count($partial, '$paPlacements as $paKey => $paLabel'), 'حلقهٔ سلکت ۵ گزینه‌ای');
        $this->assertStringContainsString('wire:click="$set(\'dt_price_alert_float_pos\',', $partial, 'انتخاب‌گر ۹موقعیته');
        $this->assertStringContainsString('wire:click="paAddSelector"', $partial, 'دکمهٔ افزودن سلکتور');
        $this->assertStringContainsString('wire:click="paRemoveSelector(', $partial, 'دکمهٔ سطل زبالهٔ ردیف');
        $this->assertStringContainsString('wire:keydown.enter="paAddSelector"', $partial, 'Enter هم سلکتور را اضافه می‌کند');
        $this->assertStringContainsString('paAddExampleSelector', $partial, 'چیپ‌های سلکتور نمونه');
        $this->assertStringContainsString('dt-preview-btn--float', $partial, 'پیش‌نمایش شناور');
    }

    #[Test]
    #[TestDox('UI پنل قدیم: موقعیت‌های جدید + سلکت موقعیت شناور + تکست‌اریای سلکتورها')]
    public function old_panel_others_has_new_fields(): void
    {
        $view = File::get(base_path('resources/views/back/settings/others.blade.php'));

        $this->assertStringContainsString('<option value="float"', $view);
        $this->assertStringContainsString('<option value="custom-selector"', $view);
        $this->assertStringContainsString('name="dt_price_alert_float_pos"', $view);
        $this->assertStringContainsString('name="dt_price_alert_selectors"', $view);
    }
}
