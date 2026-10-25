<?php

namespace Tests\Feature\Packages;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * فاز ۷۹-ب-۳ — تست پایهٔ پکیج‌های پول‌محور (CreditPay v2 / CheckoutTurbo / DigiPay)
 *
 * فلسفهٔ تست: روی هر میزبانی که پکیج‌ها نصب شده‌اند، ساختار و قرارداد سرویس‌ها
 * واقعاً راستی‌آزمایی می‌شود (شکست ادغام/به‌روزرسانی را قبل از فروش می‌گیرد)؛
 * روی محیط‌های بدون پکیج، با پیام روشن Skip می‌شوند تا برای خریداران سردرگمی نسازند.
 *
 * پوشش:
 *  - CreditPay v2.0.1: مانیفست، ساختار ادغام (InstallmentPayment داخل CreditPay)،
 *    قرارداد FinnotechService (سرویس واحد ۱۸ سرویس + شیم فلوی اعتباری)، ریاضیِ
 *    کارمزد و جریمهٔ دیرکرد، ثبت دستور اتصال تم (credit:wire-theme)
 *  - CheckoutTurbo / DigiPay: سلامت مانیفست و فایل‌های حیاتی (سبک)
 */
class MoneyPackagesTest extends TestCase
{
    // ==================================================================
    //  کمکی‌ها
    // ==================================================================

    private function moduleDir(string $name): ?string
    {
        $path = base_path("Modules/{$name}");

        return is_dir($path . '/app') ? $path : null;
    }

    private function requireModule(string $name): string
    {
        $path = $this->moduleDir($name);

        if ($path === null) {
            $this->markTestSkipped("ماژول {$name} روی این میزبان نصب نیست — تست ساختار پکیج رد شد.");
        }

        return $path;
    }

    private function requireTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("جدول {$table} مایگریت نشده — پکیج نصب است ولی مایگریشن‌هایش اجرا نشده‌اند.");
        }
    }

    // ==================================================================
    //  CreditPay v2 — مانیفست و ساختار ادغام
    // ==================================================================

    #[Test]
    public function creditpay_manifest_is_valid_v2(): void
    {
        $dir = $this->requireModule('CreditPay');

        $manifest = json_decode((string) file_get_contents($dir . '/module.json'), true);

        $this->assertIsArray($manifest, 'module.json باید JSON معتبر باشد.');
        $this->assertSame('CreditPay', $manifest['name']);
        $this->assertSame('credit-pay', $manifest['alias']);

        $version = (float) $manifest['version'];
        $this->assertGreaterThanOrEqual(2.0, $version, 'نسخهٔ نصب‌شده باید v2 (پکیج یکپارچهٔ اقساطی+اعتباری) یا بالاتر باشد.');

        $this->assertContains(
            \Modules\CreditPay\Providers\CreditPayServiceProvider::class,
            $manifest['providers'],
            'پرووایدر ماژول باید CreditPayServiceProvider باشد.'
        );
    }

    #[Test]
    public function creditpay_merged_structure_is_complete(): void
    {
        $dir = $this->requireModule('CreditPay');

        // سرویس‌های هر دو ساب‌سیستم
        foreach ([
            'app/Services/CreditService.php',
            'app/Services/FinnotechService.php',          // سرویس واحد ادغام‌شده
            'app/Services/InstallmentService.php',        // از InstallmentPayment
            'app/Services/EligibilityService.php',
            'app/Services/InstallmentStatisticsService.php',
            'app/Observers/OrderObserver.php',            // اعتباری
            'app/Observers/InstallmentOrderObserver.php', // اقساطی (ادغام‌شده)
            'app/Listeners/ActivateInstallmentPlanAfterOrderPaid.php',
            'app/Listeners/OrderPaidListener.php',
            'app/Console/Commands/WireThemeCommand.php',  // اتصال خودکار تم
            'app/Console/Commands/ProcessOverdueInstallmentsCommand.php',
            'app/Console/Commands/SendRemindersCommand.php',
        ] as $file) {
            $this->assertFileExists($dir . '/' . $file, "فایل حیاتی {$file} در پکیج یکپارچه غایب است.");
        }

        // مایگریشن‌های ساب‌سیستم اقساطی (با گارد hasTable برای ارتقای بدون‌دست‌دادن-داده)
        $migrations = glob($dir . '/database/migrations/*.php') ?: [];
        $names      = array_map('basename', $migrations);
        $this->assertNotEmpty(
            preg_grep('/create_installment_tables|create_installment_table/', $names),
            'مایگریشن جداول اقساطی باید موجود باشد.'
        );

        // ویوهای هوک چک‌اوت/سایدبار هر دو حالت خرید
        foreach ([
            'resources/views/front/checkout_hook.blade.php',
            'resources/views/front/installment_checkout_hook.blade.php',
            'resources/views/front/installment_sidebar_hook.blade.php',
            'resources/views/back/partials/menu.blade.php',
            'resources/views/back/partials/installment_menu.blade.php',
        ] as $view) {
            $this->assertFileExists($dir . '/' . $view, "ویو حیاتی {$view} غایب است.");
        }
    }

    #[Test]
    public function creditpay_provider_wires_both_subsystems(): void
    {
        $dir  = $this->requireModule('CreditPay');
        $code = (string) file_get_contents($dir . '/app/Providers/CreditPayServiceProvider.php');

        // سرویس‌های اقساطی ثبت شده‌اند
        $this->assertStringContainsString('InstallmentService::class', $code);
        $this->assertStringContainsString('EligibilityService::class', $code);

        // کامندهای زمان‌بندی‌شدهٔ اقساطی
        $this->assertStringContainsString('installments:process-overdue', $code);
        $this->assertStringContainsString('installments:send-reminders', $code);

        // لیسنر فعال‌سازی طرح روی OrderPaid + آبزرور لغو
        $this->assertStringContainsString('ActivateInstallmentPlanAfterOrderPaid::class', $code);
        $this->assertStringContainsString('InstallmentOrderObserver::class', $code);

        // منوهای دوگانه (اعتباری + اقساطی)
        $this->assertStringContainsString('back.partials.installment_menu', $code);
        $this->assertStringContainsString('front.partials.installment_menu', $code);
    }

    // ==================================================================
    //  CreditPay v2 — قرارداد FinnotechService واحد
    // ==================================================================

    #[Test]
    public function finnotech_service_list_covers_18_kyc_services(): void
    {
        $this->requireModule('CreditPay');
        $this->requireTable('credit_settings');

        $services = \Modules\CreditPay\Services\FinnotechService::getServiceList();

        $this->assertCount(18, $services, 'لیست سرویس‌های KYC فینوتک باید ۱۸ سرویس باشد.');
        foreach ($services as $key => $info) {
            $this->assertArrayHasKey('label', $info, "سرویس {$key} بدون برچسب است.");
            $this->assertArrayHasKey('group', $info, "سرویس {$key} بدون گروه است.");
        }

        // سرویس‌های شاخص هر چهار گروه
        foreach (['sayad_check', 'id_mobile_match', 'account_inquiry', 'promissory'] as $key) {
            $this->assertArrayHasKey($key, $services);
        }
    }

    #[Test]
    public function finnotech_verify_identity_returns_safe_contract_when_disabled(): void
    {
        $this->requireModule('CreditPay');
        $this->requireTable('credit_settings');

        $service = app(\Modules\CreditPay\Services\FinnotechService::class);

        if ($service->isEnabled()) {
            $this->markTestSkipped('فینوتک روی این میزبان فعال است — تست قراردادِ حالتِ غیرفعال فقط وقتی معنا دارد که غیرفعال باشد.');
        }

        $result = $service->verifyIdentity('0012345678', '09121234567');

        // قرارداد خروجی باید دقیقاً همین کلیدها را داشته باشد (CreditController/CreditUserController وابسته‌اند)
        foreach (['success', 'match', 'tracking_id', 'status', 'raw_response', 'message'] as $key) {
            $this->assertArrayHasKey($key, $result, "خروجی verifyIdentity باید کلید {$key} را داشته باشد.");
        }

        $this->assertFalse($result['success']);
        $this->assertFalse($result['match']);
        $this->assertSame(\Modules\CreditPay\Models\CreditAccount::FINNOTECH_SKIPPED, $result['status']);
    }

    // ==================================================================
    //  CreditPay v2 — ریاضیِ کارمزد و جریمه
    // ==================================================================

    #[Test]
    public function credit_markup_cart_percent_math_is_correct(): void
    {
        $this->requireModule('CreditPay');
        $this->requireTable('credit_settings');

        $service = app(\Modules\CreditPay\Services\CreditService::class);

        $method = \Modules\CreditPay\Models\CreditSetting::get('markup_method', 'cart_percent');
        if ($method !== 'cart_percent') {
            $this->markTestSkipped("روش کارمزد میزبان {$method} است — تست ریاضیِ cart_percent فقط با پیش‌فرض معنا دارد.");
        }

        $percent = (float) \Modules\CreditPay\Models\CreditSetting::get('markup_cart_percent', 5);

        // سبد ۲٫۰۰۰٫۰۰۰ تومانی (۱٬۲۰۰٬۰۰۰ + ۴۰۰٬۰۰۰×۲)
        $items = [
            ['price' => 1_200_000, 'quantity' => 1],
            ['price' => 400_000,   'quantity' => 2],
        ];

        $result = $service->calculateMarkup($items);

        // ساختار خروجی: ['amount' => int, 'details' => [...]] (سازگار با هر دو کلید تاریخی)
        $amount = $result['amount'] ?? $result['markup_amount'] ?? $result['markupAmount'] ?? null;
        $this->assertNotNull($amount, 'خروجی calculateMarkup باید مبلغ کارمزد داشته باشد.');
        $this->assertSame(
            (int) round(2_000_000 * ($percent / 100)),
            (int) $amount,
            'مبلغ کارمزد درصدی سبد باید دقیق باشد.'
        );
    }

    #[Test]
    public function installment_penalty_math_is_correct(): void
    {
        $this->requireModule('CreditPay');

        // خالص و بدون دیتابیس: مدل‌های درون‌حافظه + ریاضی سرویس
        $plan = new \Modules\CreditPay\Models\InstallmentPlan();
        $plan->forceFill(['penalty_rate_per_day' => 0.5]); // نیم‌درصد در روز

        $payment = new \Modules\CreditPay\Models\InstallmentPayment();
        $payment->forceFill([
            'amount'   => 100_000,
            'status'   => \Modules\CreditPay\Models\InstallmentPayment::STATUS_OVERDUE,
            'due_date' => now()->subDays(10)->startOfDay(),
        ]);
        $payment->setRelation('plan', $plan);

        $service = app(\Modules\CreditPay\Services\InstallmentService::class);

        $this->assertSame(10, $payment->daysLate());
        $this->assertSame(
            5_000, // ۱۰۰٬۰۰۰ × ۰٫۵٪ × ۱۰ روز
            $service->calculatePenalty($payment),
            'جریمهٔ دیرکرد باید amount × rate/100 × days باشد.'
        );

        // قسط سررسیدنشده → صفر
        $future = new \Modules\CreditPay\Models\InstallmentPayment();
        $future->forceFill([
            'amount'   => 100_000,
            'status'   => \Modules\CreditPay\Models\InstallmentPayment::STATUS_PENDING,
            'due_date' => now()->addDays(5),
        ]);
        $future->setRelation('plan', $plan);
        $this->assertSame(0, $future->daysLate());
        $this->assertSame(0, $service->calculatePenalty($future));
    }

    // ==================================================================
    //  CreditPay v2 — اتصال خودکار تم
    // ==================================================================

    #[Test]
    public function wire_theme_command_is_registered(): void
    {
        $this->requireModule('CreditPay');

        $found = collect(\Illuminate\Support\Facades\Artisan::all())
            ->has('credit:wire-theme');

        $this->assertTrue($found, 'دستور credit:wire-theme باید ثبت شده باشد (اتصال خودکار تم).');
    }

    // ==================================================================
    //  CheckoutTurbo / DigiPay — سلامت ساختار (سبک)
    // ==================================================================

    #[Test]
    public function checkout_turbo_manifest_is_healthy(): void
    {
        $dir = $this->requireModule('CheckoutTurbo');

        $manifest = json_decode((string) file_get_contents($dir . '/module.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('CheckoutTurbo', $manifest['name']);
        $this->assertNotEmpty($manifest['providers'], 'CheckoutTurbo باید پرووایدر داشته باشد.');

        $this->assertFileExists($dir . '/app/Http/Controllers/Front/QuickOrderController.php', 'قلب فرانت CheckoutTurbo باید موجود باشد.');
    }

    #[Test]
    public function digipay_manifest_is_healthy(): void
    {
        $dir = $this->requireModule('DigiPay');

        $manifest = json_decode((string) file_get_contents($dir . '/module.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('DigiPay', $manifest['name']);
        $this->assertGreaterThanOrEqual(1.2, (float) $manifest['version'], 'DigiPay باید حداقل v1.2.0 (نصب خودکار درگاه) باشد.');
    }
}
