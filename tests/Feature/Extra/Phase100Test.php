<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Packages\PackageIndex;
use App\Models\Gateway;
use App\Support\SubscriptionDisplay;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionMethod;
use Shetabit\Multipay\Drivers\SEP\SEP;
use Shetabit\Multipay\Drivers\SEP\SEP as SepDriver;
use Shetabit\Multipay\Exceptions\DriverNotFoundException;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Payment;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۰ — «کمتر از ۱ روز» اشتراک + پیام‌های وضعیت اشتراک در بازار + ریشه‌کنی باگ درگاه سپ.
 * -------------------------------------------------------------------------------------------------
 * گزارش کاربر:
 *  ۱) وقتی اشتراک طراح رو به پایان است، به‌جای «۰ روز» باید «کمتر از ۱ روز» نشان داده شود.
 *  ۲) وقتی لیست پکیج‌ها خالی است، اگر علت نبودِ اشتراک فعال است، کاربر باید علت را ببیند.
 *  ۳) درگاه سپ تنظیم شده ولی خرید وارد درگاه نمی‌شود (تست کامل با شبیه‌ساز انجام شد).
 *
 * کشف‌های فاز ۱۰۰ (درایور واقعی پروژه در packages/shetabit — override/autoload پروژه است):
 *  • باگ تأیید سپ: verify() با ResNum (شناسهٔ فاکتور) مقایسه می‌کرد در حالی‌که transactionId
 *    همان «توکن درگاه» است و در کال‌بک با فیلد Token برمی‌گردد → تأیید هر پرداخت موفق سپ
 *    با «-108» رد می‌شد. (فیکس هم‌سو با upstream)
 *  • کانفیگ کهنه/کش‌شدهٔ اپ (نبودِ drivers.sep) → «Driver not found in config file» → کاربر
 *    هرگز وارد درگاه نمی‌شد. فیکس: merge کانفیگ پیش‌فرض پکیج در Payment و پیام فارسی.
 */
class Phase100Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    /* =====================================================================
     *  بند ۱ — SubscriptionDisplay: «کمتر از ۱ روز» به‌جای ۰ روز
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۱-الف — روز باقی‌ماندهٔ صحیح برای بازه‌های بالای یک روز')]
    public function days_remaining_counts_full_days(): void
    {
        $this->assertSame(7, SubscriptionDisplay::daysRemaining(now()->addDays(7)));
        $this->assertSame(2, SubscriptionDisplay::daysRemaining(now()->addDays(2)->addHours(3)));
        $this->assertSame(0, SubscriptionDisplay::daysRemaining(now()->subHour()), 'انقضی‌شده = ۰');
    }

    #[Test]
    #[TestDox('بند ۱-ب — پرچم «کمتر از ۱ روز» فقط برای اشتراکِ فعالِ زیر ۲۴ ساعت')]
    public function less_than_one_day_flag_logic(): void
    {
        // ۵ ساعت مانده → کمتر از ۱ روز (قبلاً «۰ روز» نشان داده می‌شد)
        $this->assertTrue(SubscriptionDisplay::isLessThanOneDay(now()->addHours(5)));
        // ۲۳ ساعت و ۵۰ دقیقه مانده → هنوز کمتر از ۱ روز
        $this->assertTrue(SubscriptionDisplay::isLessThanOneDay(now()->addHours(23)->addMinutes(50)));
        // ۲ روز مانده → نه
        $this->assertFalse(SubscriptionDisplay::isLessThanOneDay(now()->addDays(2)));
        // منقضی‌شده → نه (نباید «کمتر از ۱ روز» برای اشتراک مرده بگوید)
        $this->assertFalse(SubscriptionDisplay::isLessThanOneDay(now()->subHours(2)));
        // مرز: کمی بیش از ۲۴ ساعت → «کمتر از ۱ روز» نیست
        $this->assertFalse(SubscriptionDisplay::isLessThanOneDay(now()->addHours(24)->addMinutes(5)));
    }

    #[Test]
    #[TestDox('بند ۱-پ — annotate روی بلوک اشتراکِ پکیج (subscription_expires_at)')]
    public function annotate_package_subscription_block(): void
    {
        $block = ['plan_name' => 'سالانه', 'subscription_expires_at' => now()->addHours(9)->toDateTimeString()];

        $annotated = SubscriptionDisplay::annotate($block, 'subscription_expires_at');

        $this->assertSame(0, $annotated['days_remaining'], 'زیر ۱۲ ساعت → عدد صفر (قبل از فیکس فقط همین بود)');
        $this->assertTrue($annotated['less_than_one_day'], 'پرچم «کمتر از ۱ روز» باید روشن باشد');
        $this->assertFalse($annotated['is_expired']);

        // بدون تاریخ انقضا → دست‌نخورده
        $this->assertSame(['plan_name' => 'پلن'], SubscriptionDisplay::annotate(['plan_name' => 'پلن'], 'subscription_expires_at'));
    }

    #[Test]
    #[TestDox('بند ۱-ت — خلاصهٔ اشتراک منقضی: has_active=false + پرچم is_expired')]
    public function normalize_summary_marks_expired_subscription(): void
    {
        $component = new PackageIndex;
        $method = new ReflectionMethod($component, 'normalizeSubscriptionSummary');
        $method->setAccessible(true);

        // منقضی از دیروز — API پرچم has_active_subscription=true فرستاده ولی تاریخ گذشته است
        $summary = $method->invoke($component, [
            'has_active_subscription' => true,
            'plan_name' => 'شش‌ماهه',
            'expires_at' => now()->subDay()->toDateTimeString(),
        ]);

        $this->assertFalse($summary['has_active_subscription'], 'تاریخ گذشته → اشتراک فعال نیست');
        $this->assertTrue($summary['is_expired'], 'پرچم is_expired برای بنر «اشتراک شما منقضی شده است» لازم است');
        $this->assertSame(0, $summary['days_remaining']);

        // فعال با ۹ ساعت مانده → پرچم «کمتر از ۱ روز»
        $active = $method->invoke($component, [
            'expires_at' => now()->addHours(9)->toDateTimeString(),
        ]);
        $this->assertTrue($active['has_active_subscription']);
        $this->assertTrue($active['less_than_one_day']);
        $this->assertFalse($active['is_expired']);
    }

    /* =====================================================================
     *  بند ۲ — قرارداد ویوها: «کمتر از ۱ روز» + پیام‌های اشتراکی حالت خالی
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۲-الف — پنل جدید: «کمتر از ۱ روز» + حالت خالی اشتراکی + بنر انقضا')]
    public function new_panel_view_subscription_contract(): void
    {
        $view = file_get_contents(resource_path('views/admin-panel/livewire/admin/packages/index.blade.php'));

        // فیکس «۰ روز» — نمایش «کمتر از ۱ روز» در بنر و کارت و مودال
        $this->assertStringContainsString('less_than_one_day', $view);
        $this->assertStringContainsString('کمتر از ۱ روز', $view);

        // حالت خالی اشتراک‌محور — کاربر باید بداند چرا پکیجی نمایش داده نمی‌شود
        $this->assertStringContainsString('نمایش پکیج‌ها به اشتراک فعال نیاز دارد', $view);
        $this->assertStringContainsString('به دلیل نداشتن اشتراک فعال', $view);

        // بنر انقضا (تفکیک از «اصلاً اشتراک ندارید»)
        $this->assertStringContainsString('اشتراک شما منقضی شده است', $view);
        $this->assertStringContainsString("is_expired'] ?? false", $view);
    }

    #[Test]
    #[TestDox('بند ۲-ب — پنل قدیمی: هم‌خوان با «کمتر از ۱ روز»')]
    public function legacy_panel_view_subscription_contract(): void
    {
        $view = file_get_contents(resource_path('views/back/packages/index.blade.php'));

        $this->assertStringContainsString('less_than_one_day', $view);
        $this->assertStringContainsString('کمتر از ۱ روز', $view);
    }

    /* =====================================================================
     *  بند ۳ — درگاه سپ: درایور واقعی پروژه (packages/shetabit) فیکس شد
     * ===================================================================== */

    /**
     * درایور SEP با تنظیمات کمینه (بررسی‌های Status/Token قبل از تماس HTTP اند — بدون شبکه)
     */
    private function makeSepDriver(Invoice $invoice): SepDriver
    {
        return new SepDriver($invoice, [
            'apiGetToken' => 'https://sep.shaparak.ir/onlinepg/onlinepg',
            'apiPaymentUrl' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
            'apiVerificationUrl' => 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction',
            'terminalId' => '2087634',
            'callbackUrl' => 'https://shop.test/orders/payment/callback/sep',
            'currency' => 'T',
        ]);
    }

    #[Test]
    #[TestDox('بند ۳-الف — وضعیت ناموفق کال‌بک (Status=3) → «پرداخت انجام نشد.»')]
    public function sep_verify_rejects_failed_status(): void
    {
        request()->merge(['Status' => '3', 'RefNum' => 'REF1', 'Token' => 'T1']);

        $this->expectException(PurchaseFailedException::class);
        $this->expectExceptionMessage('پرداخت انجام نشد.');

        $this->makeSepDriver((new Invoice)->amount(100000))->verify();
    }

    #[Test]
    #[TestDox('بند ۳-ب — فالبک StateCode: بدون Status ولی StateCode=3 → باز هم رد می‌شود')]
    public function sep_verify_reads_statecode_fallback(): void
    {
        // مستند OnlinePG سامان وضعیت را با StateCode می‌فرستد؛ قبل از فیکس فقط Status خوانده می‌شد
        request()->merge(['StateCode' => '3', 'RefNum' => 'REF1', 'Token' => 'T1']);

        $this->expectException(PurchaseFailedException::class);
        $this->expectExceptionMessage('پرداخت انجام نشد.');

        $this->makeSepDriver((new Invoice)->amount(100000))->verify();
    }

    #[Test]
    #[TestDox('بند ۳-پ — گارد توکن: Tokenِ کال‌بک ≠ توکنِ فاکتور → -108 (این بار با مقایسهٔ Token نه ResNum)')]
    public function sep_verify_compares_token_not_resnum(): void
    {
        // توکن ذخیره‌شدهٔ فاکتور + توکنِ جعلیِ کال‌بک → باید رد شود.
        // قبل از فیکس با ResNum مقایسه می‌شد و این گارد عملاً همیشه فعال بود (باگ).
        request()->merge(['Status' => '2', 'RefNum' => 'REF1', 'Token' => 'FAKE-TOKEN', 'ResNum' => 'MOCKTOKEN0123456789abcdef']);

        $this->expectException(InvalidPaymentException::class);
        $this->expectExceptionMessage('اطلاعات پرداخت با فاکتور همخوانی ندارد.');

        $driver = $this->makeSepDriver((new Invoice)->amount(100000));
        $driver->getInvoice()->transactionId('MOCKTOKEN0123456789abcdef');
        $driver->verify();
    }

    #[Test]
    #[TestDox('بند ۳-ت — قفل رگرسیون: درایور محلی سپ باید با Token مقایسه کند (نه ResNum)')]
    public function sep_local_driver_uses_token_check(): void
    {
        $source = file_get_contents(base_path('packages/shetabit/multipay/src/Drivers/SEP/SEP.php'));

        $this->assertStringContainsString("Request::input('Token')", $source, 'مقایسهٔ تأیید باید با فیلد Token کال‌بک باشد.');
        $this->assertStringNotContainsString("!== Request::input('ResNum')", $source, 'مقایسهٔ ResNum همان باگ -108 است — نباید برگردد (ثبت ResNum در جزئیات رسید مجاز است).');
    }

    /* =====================================================================
     *  بند ۴ — مقاومت کانفیگ: کانفیگ کهنه/کش‌شده نباید خرید را بشکند
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۴-الف — کانفیگ بدون sep (مثل کش قدیمی): درایور سپ باز هم resolve می‌شود')]
    public function payment_manager_survives_stale_config(): void
    {
        // کانفیگ اپ کهنه: sep در drivers و map نیست (مثل bootstrap/cache قبل از فاز ۹۹)
        $payment = new Payment(['default' => 'sep', 'drivers' => ['zarinpal' => ['merchantId' => 'x']]]);

        $payment->via('sep');

        // تنظیمات از کانفیگ پیش‌فرض پکیج آمده + مقادیر runtime تزریق می‌شود
        $settings = \Closure::bind(fn () => $this->settings, $payment, Payment::class)();
        $this->assertSame('https://sep.shaparak.ir/onlinepg/onlinepg', $settings['apiGetToken'] ?? null);

        // currency هم از پیش‌فرض پکیج باید بماند (تبدیل تومان→ریال)
        $this->assertSame('T', $settings['currency'] ?? null);
    }

    #[Test]
    #[TestDox('بند ۴-ب — درایور واقعاً ناموجود → پیام فارسیِ قابل‌اقدام')]
    public function unknown_driver_gives_persian_actionable_message(): void
    {
        $payment = new Payment(['default' => 'sep']);

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('تنظیمات درایور «nonexistent» در کانفیگ پرداخت یافت نشد');

        $payment->via('nonexistent');
    }

    #[Test]
    #[TestDox('بند ۴-پ — endpointهای رسمی سپ در کانفیگ پیش‌فرض پکیج محلی هم هست')]
    public function local_package_default_config_has_sep_endpoints(): void
    {
        $payment = new Payment(['default' => 'sep']);

        $ref = new ReflectionMethod(Payment::class, 'loadDefaultConfig');
        $ref->setAccessible(true);
        $defaults = $ref->invoke($payment);

        $this->assertSame('https://sep.shaparak.ir/onlinepg/onlinepg', $defaults['drivers']['sep']['apiGetToken'] ?? null);
        $this->assertSame(SEP::class, $defaults['map']['sep'] ?? null);
        $this->assertSame('T', $defaults['drivers']['sep']['currency'] ?? null, 'سپ ریال می‌خواهد — پیش‌فرض پکیج T است و خود درایور ×۱۰ می‌کند.');
    }

    /* =====================================================================
     *  بند ۵ — سیم‌کشی کانفیگ اپ + تزریق terminalId از جدول gateways
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۵ — get_gateway_configs(سپ) کد پایانه را از DB برمی‌گرداند')]
    public function sep_configs_flow_from_database(): void
    {
        // ردیف sep ممکن است در میزبان‌های مختلف از قبل (فاز ۹۹) موجود باشد
        Gateway::where('key', 'sep')->delete();

        $gateway = Gateway::create(['key' => 'sep', 'name' => 'درگاه سپ (سمان الکترونیک)', 'is_active' => true]);
        $gateway->configs()->create(['key' => 'terminalId', 'value' => '99998888']);

        $configs = get_gateway_configs('sep');

        $this->assertSame('99998888', $configs['terminalId']);
    }

    #[Test]
    #[TestDox('بند ۶ — متادیتای اشتراک در enrichPackages هم پرچم «کمتر از ۱ روز» می‌گیرد')]
    public function enrich_packages_annotates_less_than_one_day(): void
    {
        $component = new PackageIndex;
        $method = new ReflectionMethod($component, 'enrichPackages');
        $method->setAccessible(true);

        $packages = $method->invoke($component, [
            [
                'slug' => 'demo-package',
                'name' => 'پکیج نمونه',
                'is_free' => false,
                'subscription' => [
                    'plan_name' => 'ماهانه',
                    'subscription_expires_at' => now()->addHours(7)->toDateTimeString(),
                    'is_free_with_subscription' => true,
                ],
            ],
        ]);

        $sub = $packages[0]['subscription'];

        $this->assertTrue($sub['less_than_one_day']);
        $this->assertSame(0, $sub['days_remaining']);
        $this->assertTrue($sub['is_free_with_subscription'], 'اشتراکِ زیر ۲۴ ساعت هنوز فعال است — رایگان با اشتراک می‌ماند');

        // منقضی → رایگان با اشتراک قطع می‌شود (رفتار قبلی حفظ شده)
        $expired = $method->invoke($component, [
            [
                'slug' => 'demo-package-2',
                'name' => 'پکیج نمونه ۲',
                'is_free' => false,
                'subscription' => [
                    'subscription_expires_at' => now()->subHours(2)->toDateTimeString(),
                    'is_free_with_subscription' => true,
                ],
            ],
        ]);
        $this->assertFalse($expired[0]['subscription']['is_free_with_subscription']);
        $this->assertTrue($expired[0]['subscription']['is_expired']);
    }
}
