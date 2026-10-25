<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Services\FrontModuleMenuRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۸۸ — یکپارچه‌سازی فرانتِ پکیج ادغامی «پرداخت قسطی و اعتباری» (CreditPay)
 * --------------------------------------------------------------------------
 * درخواست‌های کاربر:
 *  ۱) پنل مشتری: شرط‌های منوها/نمایش که بر اساس نام پکیج قدیمی (InstallmentPayment)
 *     کار می‌کردند بعد از ادغام منو را نشان نمی‌دادند → باید به پکیج ادغامی وصل شوند.
 *  ۲) چک‌اوت: شرط‌های بخش «شیوه پرداخت» (payment-method) باید پکیج ادغامی را ببینند.
 *  ۳) OrderController تم: بلاک شرطی اعتباری باید با محتوای فایل
 *     OrderController_credit_section.php خود پکیج جایگزین شود + namespace سرویس اقساطی.
 *  ۴) منوی «راهنما و آموزش» در پکیج — هم ساب‌سیستم اقساطی و هم اعتباری،
 *     هم سمت مدیریت و هم سمت مشتری (کامل، خوانا، زیبا و مدرن).
 */
class Phase88Test extends TestCase
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

    private function cpDir(): ?string
    {
        $path = base_path('Modules/CreditPay');

        return is_dir($path.'/app') ? $path : null;
    }

    private function requireCreditPay(): string
    {
        $path = $this->cpDir();

        if ($path === null) {
            $this->markTestSkipped('ماژول CreditPay (پرداخت قسطی و اعتباری) روی این میزبان نصب نیست — تست فاز ۸۸ رد شد.');
        }

        return $path;
    }

    private function newCreator(): Admin
    {
        return Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'فاز ۸۸',
            'username'   => 'feat88-'.\Illuminate\Support\Str::random(6),
            'email'      => 'feat88-'.\Illuminate\Support\Str::random(6).'@test.ir',
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

    /** کامپایل blade یک ویو + lint نتیجه؛ خروجی [compiled, exitCode] */
    private function compileAndLint(string $viewName): array
    {
        $path     = view($viewName)->getPath();
        $compiler = app('blade.compiler');
        $compiled = $compiler->compileString((string) file_get_contents($path));

        $tmp = tempnam(sys_get_temp_dir(), 'icp88-').'.php';
        file_put_contents($tmp, $compiled);

        exec(
            sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($tmp)),
            $output,
            $exitCode
        );

        @unlink($tmp);

        return [$compiled, $exitCode];
    }

    /* ================= ۱) چک‌اوت تم → پکیج ادغامی ================= */

    #[Test]
    #[TestDox('بند ۱ — چک‌اوت تم (payment-method + checkout-sidebar) به پکیج ادغامی CreditPay وصل است و هیچ ردی از InstallmentPayment قدیمی نیست')]
    public function checkout_theme_gates_point_to_merged_creditpay_module(): void
    {
        $paymentMethod = $this->read('themes/WeblakShop/src/resources/views/carts/partials/payment-method.blade.php');
        $sidebar       = $this->read('themes/WeblakShop/src/resources/views/carts/partials/checkout-sidebar.blade.php');

        // هوک اقساطی از پکیج ادغامی
        $this->assertStringContainsString("module_is_ready('CreditPay', ['installment_excluded_products'])", $paymentMethod);
        $this->assertStringContainsString("credit-pay::front.installment_checkout_hook", $paymentMethod);
        // هوک اعتباری سر جایش است
        $this->assertStringContainsString("module_is_ready('CreditPay', ['credit_product_markups'])", $paymentMethod);
        $this->assertStringContainsString("credit-pay::front.checkout_hook", $paymentMethod);

        // هوک سایدبار اقساطی از پکیج ادغامی
        $this->assertStringContainsString("credit-pay::front.installment_sidebar_hook", $sidebar);

        foreach ([$paymentMethod, $sidebar] as $file) {
            $this->assertStringNotContainsString('InstallmentPayment', $file, 'گیت نام پکیج قدیمی در چک‌اوت باقی مانده است.');
            $this->assertStringNotContainsString('installment-payment::', $file, 'namespace ویوی پکیج قدیمی در چک‌اوت باقی مانده است.');
        }
    }

    /* ================= ۲) پنل مشتری تم → پکیج ادغامی ================= */

    #[Test]
    #[TestDox('بند ۲ — پنل مشتری (سفارش‌ها): شرط‌ها و مدل/ویوی اقساطی به پکیج ادغامی CreditPay وصل شده‌اند')]
    public function user_panel_order_views_point_to_merged_creditpay_module(): void
    {
        $index = $this->read('themes/WeblakShop/src/resources/views/user/orders/index.blade.php');
        $show  = $this->read('themes/WeblakShop/src/resources/views/user/orders/show.blade.php');

        $this->assertStringContainsString('module_is_active(\'CreditPay\')', $index);
        $this->assertStringContainsString('\Modules\CreditPay\Models\InstallmentPlan::where', $index);
        $this->assertStringContainsString('\Modules\CreditPay\Models\CreditOrder::where', $index);

        $this->assertStringContainsString('module_is_active(\'CreditPay\')', $show);
        $this->assertStringContainsString('\Modules\CreditPay\Models\InstallmentPlan::where', $show);
        $this->assertStringContainsString("credit-pay::front.order_installment_info", $show);

        foreach ([$index, $show] as $file) {
            $this->assertStringNotContainsString('InstallmentPayment', $file);
            $this->assertStringNotContainsString('installment-payment::', $file);
        }
    }

    /* ================= ۳) پنل ادمین قدیمی → پکیج ادغامی ================= */

    #[Test]
    #[TestDox('بند ۳ — پنل ادمین قدیمی (داشبورد + آمار سفارش‌ها): گیت‌ها و assetها به پکیج ادغامی وصل شده‌اند')]
    public function old_admin_panel_points_to_merged_creditpay_module(): void
    {
        $index      = $this->read('resources/views/back/index.blade.php');
        $statistics = $this->read('resources/views/back/statistics/orders/index.blade.php');

        $this->assertStringContainsString("module_is_active('CreditPay')", $index);
        $this->assertStringContainsString("credit-pay::back.statistics.filter-tabs", $index);
        // asset جاوااسکریپت آمار در پکیج ادغامی «installment_statistics.js» است (نه statistics.js قدیمی)
        $this->assertStringContainsString("module_asset('CreditPay', 'js/installment_statistics.js')", $index);

        $this->assertStringContainsString("module_is_active('CreditPay')", $statistics);
        $this->assertStringContainsString("credit-pay::back.statistics.filter-tabs", $statistics);
        $this->assertStringContainsString("module_asset('CreditPay', 'css/installment.css')", $statistics);

        foreach ([$index, $statistics] as $file) {
            $this->assertStringNotContainsString('InstallmentPayment', $file);
            $this->assertStringNotContainsString('installment-payment::', $file);
        }
    }

    /* ================= ۴) OrderController تم ================= */

    #[Test]
    #[TestDox('بند ۴ — OrderController: بلاک اول فقط اعتبارسنجی است و بلاک دوم عیناً «کد جایگزین» پکیج (OrderController_credit_section.php)')]
    public function ordercontroller_credit_block_matches_package_replacement_file(): void
    {
        $this->requireCreditPay();

        $controller = $this->read('themes/WeblakShop/src/Controllers/OrderController.php');

        // هیچ ردی از پکیج قدیمی اقساطی نباید بماند
        $this->assertStringNotContainsString('InstallmentPayment', $controller);
        $this->assertStringContainsString('\Modules\CreditPay\Services\InstallmentService', $controller);
        $this->assertStringContainsString('\Modules\CreditPay\Services\CreditService', $controller);

        // دو بلاک elseif ($isCredit) وجود دارد (اعتبارسنجی + ساخت)
        $this->assertSame(2, substr_count($controller, 'elseif ($isCredit) {'));

        // تفکیک دو بلاک
        $firstStart  = strpos($controller, 'elseif ($isCredit) {');
        $secondStart = strpos($controller, 'elseif ($isCredit) {', (int) $firstStart + 1);
        $this->assertNotFalse($secondStart);

        $firstBlock  = substr($controller, (int) $firstStart, (int) $secondStart - (int) $firstStart);

        // بلاک اول: فقط اعتبارسنجی — بدون ساخت سفارش اعتباری (رفع ثبت تکراری)
        $this->assertStringNotContainsString('CreditOrder::create', $firstBlock, 'بلاک اول نباید سفارش اعتباری بسازد (ثبت تکراری).');
        $this->assertStringNotContainsString('CreditInstallment::create', $firstBlock, 'بلاک اول نباید قسط بسازد.');
        $this->assertStringContainsString('canUserUseCredit', $firstBlock, 'بلاک اول باید گیت خرید اعتباری را چک کند.');

        $secondBlock = substr($controller, (int) $secondStart);

        // بلاک دوم = نسخهٔ پکیج: ساخت سفارش + قسط‌ها + چک کیف پول + prepareCartItems
        $this->assertStringContainsString('CreditOrder::create', $secondBlock);
        $this->assertStringContainsString('CreditInstallment::create', $secondBlock);
        $this->assertStringContainsString("input('gateway') === 'wallet'", $secondBlock, 'چک موجودی کیف پول (گام ۱۰ کد جایگزین پکیج).');
        $this->assertStringContainsString('prepareCartItems', $secondBlock);
        $this->assertStringContainsString("session()->flash('credit_order_id'", $secondBlock);

        // کلیدهای محتوایی فایل «کد جایگزین» پکیج — عینِ همان ترتیب
        $replacementFile = $this->cpDir().'/OrderController_credit_section.php';
        $this->assertFileExists($replacementFile, 'فایل کد جایگزین باید در ریشهٔ پکیج حفظ شود.');
        $replacement = (string) file_get_contents($replacementFile);
        foreach ([
            'canUserUseCredit',
            'prepareCartItems',
            'analyzeCart',
            "input('gateway') === 'wallet'",
            'CreditOrder::create',
            'CreditInstallment::create',
            "session()->flash('credit_order_id'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $replacement);
            $this->assertStringContainsString($needle, $secondBlock, "کلید «{$needle}» از کد جایگزین پکیج در بلاک دوم نیست.");
        }
    }

    /* ================= ۵) راهنما و آموزش — روت/ویو/منو ================= */

    #[Test]
    #[TestDox('بند ۵ — منوی «راهنما و آموزش» (اقساطی + اعتباری): روت‌های ادمین و فرانت + ویوها + آیتم منو در هر دو پنل + guide.css')]
    public function guide_route_view_and_menu_exist(): void
    {
        $dir = $this->requireCreditPay();

        // روت‌ها
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.credit.guide'), 'روت راهنمای ادمین ثبت نشده است.');
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('front.credit.guide'), 'روت راهنمای مشتری ثبت نشده است.');

        // کنترلرها
        $this->assertFileExists($dir.'/app/Http/Controllers/Back/GuideController.php');
        $this->assertFileExists($dir.'/app/Http/Controllers/GuideController.php');

        // ویوها
        $this->assertTrue(view()->exists('credit-pay::back.guide.index'));
        $this->assertTrue(view()->exists('credit-pay::front.guide'));

        // آیتم منو در هر دو پنل ادمین (دیتای apItems + مارک‌آپ قدیمی)
        $backMenu = (string) file_get_contents(view('credit-pay::back.partials.menu')->getPath());
        $this->assertStringContainsString('admin.credit.guide', $backMenu);
        $this->assertStringContainsString('راهنما و آموزش', $backMenu);

        // فاز ۸۹ — لینک راهنما نباید مستقیم در منوی سایدبار مشتری باشد (درخواست کاربر)
        $frontMenu = (string) file_get_contents(view('credit-pay::front.partials.menu')->getPath());
        $this->assertStringNotContainsString('front.credit.guide', $frontMenu, 'لینک راهنما نباید مستقیم در منوی سایدبار مشتری باشد.');

        // و به‌جایش بنر «راهنما و آموزش» داخل صفحات «اقساط من» و «اعتبار من» است
        $cta = $dir.'/resources/views/front/partials/guide_cta.blade.php';
        $this->assertFileExists($cta, 'پارشال بنر راهنمای درون‌صفحه‌ای وجود ندارد.');
        $ctaContent = (string) file_get_contents($cta);
        $this->assertStringContainsString("route('front.credit.guide')", $ctaContent);
        $this->assertStringContainsString('راهنما و آموزش', $ctaContent);
        $this->assertStringContainsString('icg-guide-banner', $ctaContent, 'دیزاین خودکفای بنر (icg-) حفظ نشده است.');

        foreach (['front/dashboard.blade.php', 'front/index.blade.php'] as $page) {
            $pageContent = (string) file_get_contents($dir.'/resources/views/'.$page);
            $this->assertStringContainsString('credit-pay::front.partials.guide_cta', $pageContent, "بنر راهنما در ویوی «{$page}» نیست.");
        }

        // دیزاین‌سیستم اسکوپ‌شدهٔ icp- + فونت‌های compat
        $this->assertFileExists($dir.'/resources/assets/css/guide.css');
        $adminGuide = (string) file_get_contents(view('credit-pay::back.guide.index')->getPath());
        $frontGuide = (string) file_get_contents(view('credit-pay::front.guide')->getPath());
        foreach ([$adminGuide, $frontGuide] as $guide) {
            $this->assertStringContainsString('css/guide.css', $guide);
            $this->assertStringContainsString('icp-scope', $guide);
        }
    }

    #[Test]
    #[TestDox('بند ۶ — صفحهٔ راهنمای ادمین واقعاً رندر می‌شود (هر دو ساب‌سیستم: اقساطی و اعتباری)')]
    public function admin_guide_page_renders(): void
    {
        $this->requireCreditPay();

        $this->actingAs($this->newCreator(), 'adminPanel');

        $response = $this->get(route('admin.credit.guide'));

        $response->assertOk();
        // هر دو ساب‌سیستم آموزش داده می‌شوند
        $response->assertSee('خرید اعتباری');
        $response->assertSee('پرداخت اقساطی');
        $response->assertSee('شروع سریع');
        $response->assertSee('سوالات پرتکرار');
        $response->assertSee('فینوتک');
    }

    #[Test]
    #[TestDox('بند ۷ — صفحهٔ راهنمای مشتری رندر می‌شود (گیت لایسنس با license_skip دِو باز می‌شود)')]
    public function front_guide_page_renders(): void
    {
        $this->requireCreditPay();

        $user = $this->newUser([
            'first_name'         => 'مشتری',
            'last_name'          => 'فاز ۸۸',
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $response = $this->get(route('front.credit.guide'));

        $response->assertOk();
        $response->assertSee('راهنمای خرید اقساطی و اعتباری');
        $response->assertSee('اعتبار من');
        $response->assertSee('اقساط من');
    }

    /* ================= ۸) هم‌ساخت‌سازی پکیج ================= */

    #[Test]
    #[TestDox('بند ۸ — هم‌ساخت: license_skip (دِو-کی) + رفع ارجاع assetهای شکسته (installment_statistics / installment_checkout)')]
    public function package_structure_consistency_and_broken_asset_fixes(): void
    {
        $dir = $this->requireCreditPay();

        // license_skip مثل بقیهٔ پکیج‌ها (PayYar / MarketingMessaging)
        $guard   = (string) file_get_contents($dir.'/app/Services/LicenseGuard.php');
        $config  = (string) file_get_contents($dir.'/config/config.php');
        $this->assertStringContainsString("CreditSetting::get('license_skip'", $guard);
        $this->assertStringContainsString("'license_skip'", $config);

        // ارجاع‌های asset دیگر به فایل‌های قدیمیِ پکیج مقدمی اشاره نمی‌کنند
        $statisticsView = (string) file_get_contents($dir.'/resources/views/back/statistics/index.blade.php');
        $hookView       = (string) file_get_contents($dir.'/resources/views/front/installment_checkout_hook.blade.php');
        $this->assertStringContainsString("module_asset('CreditPay', 'js/installment_statistics.js')", $statisticsView);
        $this->assertStringNotContainsString("js/statistics.js'", $statisticsView);
        $this->assertStringContainsString("module_asset('CreditPay', 'js/installment_checkout.js')", $hookView);
        $this->assertStringNotContainsString("js/checkout.js'", $hookView);

        // فایل‌های واقعی JS وجود دارند
        $this->assertFileExists($dir.'/resources/assets/js/installment_statistics.js');
        $this->assertFileExists($dir.'/resources/assets/js/installment_checkout.js');
    }

    /* ================= ۹) منوی فرانت رجیستر می‌شود ================= */

    #[Test]
    #[TestDox('بند ۹ — منوی فرانت پکیج (اعتبار من + اقساط من + راهنما) در FrontModuleMenuRegistry ثبت و ویوهایش موجودند')]
    public function front_menus_are_registered_in_registry(): void
    {
        $this->requireCreditPay();

        $menus = FrontModuleMenuRegistry::all();

        $this->assertContains('credit-pay::front.partials.menu', $menus, 'منوی فرانت اعتباری رجیستر نشده.');
        $this->assertContains('credit-pay::front.partials.installment_menu', $menus, 'منوی فرانت اقساطی رجیستر نشده.');

        foreach ($menus as $menuView) {
            $this->assertTrue(view()->exists($menuView), "ویوی منوی فرانت «{$menuView}» وجود ندارد.");
        }
    }

    /* ================= ۱۰) کامپایل ویوهای تغییر یافته ================= */

    #[Test]
    #[TestDox('بند ۱۰ — همهٔ ویوهای تغییر یافتهٔ فاز ۸۸ کامپایل سالم دارند')]
    public function all_changed_views_compile(): void
    {
        $this->requireCreditPay();

        foreach ([
            'credit-pay::back.guide.index',
            'credit-pay::front.guide',
            'credit-pay::back.partials.menu',
            'credit-pay::front.partials.menu',
            'credit-pay::back.statistics.index',
            'credit-pay::front.installment_checkout_hook',
            // فاز ۸۹ — ویوهای بازطراحی‌شده
            'credit-pay::front.checkout_hook',
            'credit-pay::front.sidebar_hook',
            'credit-pay::front.partials.guide_cta',
            'credit-pay::front.dashboard',
            'credit-pay::front.index',
        ] as $viewName) {
            if (! view()->exists($viewName)) {
                $this->fail("ویوی «{$viewName}» پیدا نشد.");
            }

            [, $exitCode] = $this->compileAndLint($viewName);
            $this->assertSame(0, $exitCode, "ویوی «{$viewName}» بعد از کامپایل خطای PHP دارد.");
        }
    }

    /* ================= ۱۱) فاز ۸۹ — چک‌اوت اعتباری خودکفا ================= */

    #[Test]
    #[TestDox('بند ۱۱ — هوک چک‌اوت اعتباری خودکفاست (استایل inline با ccx-) و قرارداد JS (idها + کانفیگ + سایدبار) حفظ شده است')]
    public function checkout_credit_hook_is_selfcontained_and_keeps_js_contract(): void
    {
        $dir = $this->requireCreditPay();

        $hook    = (string) file_get_contents($dir.'/resources/views/front/checkout_hook.blade.php');
        $sidebar = (string) file_get_contents($dir.'/resources/views/front/sidebar_hook.blade.php');

        // هوک دیگر به css خارجی وابسته نیست (ریشهٔ به‌هم‌ریختگی روی هاست: کپی کهنهٔ credit.css)
        $this->assertStringNotContainsString("css/credit.css", $hook, 'هوک چک‌اوت نباید به credit.css خارجی وابسته باشد.');
        $this->assertStringContainsString('<style>', $hook, 'استایل باید inline داخل خود هوک باشد.');
        $this->assertStringContainsString('ccx-toggle-card', $hook);
        $this->assertStringContainsString('ccx-card', $hook, 'کارت خلاصه با دیزاین جدید (ccx-) بازطراحی نشده است.');

        // قرارداد JS (credit_checkout.js) — idها و کانفیگ دست‌نخورده
        foreach ([
            'id="cp-credit-checkbox"',
            'id="cp-credit-summary"',
            'id="cp-credit-enabled-input"',
            'id="cp-credit-amount-input"',
            'window.cpCheckoutConfig',
            'js/credit_checkout.js',
            "route(\"front.credit.check\")",
        ] as $needle) {
            $this->assertStringContainsString($needle, $hook, "قرارداد JS در هوک چک‌اوت شکسته شده: {$needle}");
        }

        // سایدبار: کلاس‌های مقدار که JS آپدیت می‌کند + ساختار خودکفا
        foreach ([
            'id="cp-credit-sidebar-info"',
            'cp-cart-original',
            'cp-cart-markup',
            'cp-credit-deducted',
            'cp-cash-payable',
            'cp-first-installment',
            'cp-monthly-installment',
            'cp-total-with-markup',
            '<style>',
            'cp-block-header',
        ] as $needle) {
            $this->assertStringContainsString($needle, $sidebar, "قرارداد سایدبار اعتباری شکسته شده: {$needle}");
        }
    }

    /* ================= ۱۲) فاز ۸۹ — گارد قیمت یتیم ================= */

    #[Test]
    #[TestDox('بند ۱۲ — Price::toRoundPrice با محصولِ null کرش نمیکند (رفع «rounding_amount on null» در product-block-2) و رفتار گردکردن سالم می‌ماند')]
    public function orphan_price_does_not_break_rounding(): void
    {
        // ۱) قیمت یتیم (بدون محصول) — قبلاً «Attempt to read property rounding_amount on null» می‌داد
        $orphan = new \App\Models\Price;
        $orphan->setRelation('product', null);

        $this->assertSame(123456.0, $orphan->toRoundPrice(123456), 'قیمت یتیم باید خام و بدون گردکردن برگردد.');

        // ۲) رفتار عادی: گردکردن به بالا تا ۱۰۰۰ تومان
        $product = new \App\Models\Product;
        $product->rounding_amount = '1000';
        $product->rounding_type   = 'up';

        $price = new \App\Models\Price;
        $price->setRelation('product', $product);

        $this->assertSame(1235000.0, $price->toRoundPrice(1234567), 'گردکردن عادی نباید با گارد جدید خراب شود.');
    }
}
