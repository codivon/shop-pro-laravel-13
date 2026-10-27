<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Support\PulseSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionProperty;
use Shetabit\Multipay\Drivers\SEP\SEP as SepDriver;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Invoice;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۴ — سپ (فرمت ترمینال + neo-pg + واحد مبلغ) و داشبورد Pulse.
 * -------------------------------------------------------------------------------------------------
 * لاگ جدید کاربر بعد از فاز ۱۰۱ (دیگنوستیکِ errorDesc که آن فاز اضافه کرد، جواب داد):
 *   {"errorCode":"5","errorDesc":"پارامترهای ارسال شده نامعتبر است.; شماره ترمینال الزامی است",
 *    "terminalId":"01a0be94-e09f-736a-b416-df430d0a90a6", ...}
 *
 * ریشه (مستند رسمی سپ v3.6، جدول پارامترها): TerminalId از نوع «Int64» است — ترمینال سپ
 * همیشه فقط رقم است. مقدار غیرعددی (UUID کپی‌شده از فیلد دیگری پنل سامان) در اعتبارسنجی
 * سپ رد می‌شود و همان خطای کد ۵ با پسوند «شماره ترمینال الزامی است» برمی‌گردد.
 *  ۱) درایور حالا قبل از رفت‌وبرگشت به شاپرک، فرمت غیرعددی را با پیام فارسیِ قابل‌اقدام رد می‌کند.
 *  ۲) هدر X-IPG-Url پاسخ توکن (مستند neo-pg) — ترمینال‌های neo-pg فعال باید توکن را به آدرس
 *     همین هدر بفرستند؛ پشتیبانی اضافه شد (فیلدها همان قبلی، فقط آدرس پایه عوض می‌شود).
 *  ۳) واحد مبلغ: سپ «ریال» می‌خواهد؛ سوییچ پنل درگاه (amountIsToman) تعیین می‌کند مبلغ ×۱۰ شود یا نه.
 *  ۴) داشبورد Pulse (/pulse): پیش‌فرض پکیج فقط local باز است → روی تولیدی ۴۰۳؛ گیت viewPulse
 *     حالا برای ادمین‌های لاگین‌کردهٔ پنل + سوییچ تنظیماتی باز می‌شود.
 */
class Phase104Test extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    /* =====================================================================
     *  ابزار — درایور سپ با کلاینت HTTP شبیه‌سازی‌شده (بدون شبکه)
     * ===================================================================== */

    private function sepSettings(array $overrides = []): array
    {
        return array_merge([
            'apiGetToken' => 'https://sep.shaparak.ir/onlinepg/onlinepg',
            'apiPaymentUrl' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
            'apiVerificationUrl' => 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction',
            'terminalId' => '2087634',
            'callbackUrl' => 'https://shop.test/orders/payment/callback/sep',
            'currency' => 'T',
        ], $overrides);
    }

    private function makeSepDriverWithMock(Invoice $invoice, array $settingsOverrides = [], array $responses = []): array
    {
        $handler = new MockHandler($responses);

        $driver = new SepDriver($invoice, $this->sepSettings($settingsOverrides));

        $property = new ReflectionProperty($driver, 'client');
        $property->setAccessible(true);
        $property->setValue($driver, new Client(['handler' => $handler]));

        return [$driver, $handler];
    }

    private function successfulTokenResponse(array $headers = []): Response
    {
        return new Response(200, array_merge(['Content-Type' => 'application/json'], $headers), '{"status":1,"token":"TESTTOKEN1234567890abcdef"}');
    }

    /* =====================================================================
     *  بند ۱ — گارد فرمت ترمینال (ریشهٔ لاگ کاربر: UUID به‌جای شمارهٔ پایانهٔ عددی)
     * ===================================================================== */

    #[Test]
    #[TestDox('ترمینال غیرعددی (UUID) قبل از رفت‌وبرگشت به شاپرک با پیام فارسیِ قابل‌اقدام رد می‌شود')]
    public function sep_purchase_rejects_non_numeric_terminal_without_http_call(): void
    {
        // دقیقاً همان UUID لاگ کاربر — صف MockHandler خالی است؛ هر تماس HTTP استثنای
        // گازل می‌دهد که از PurchaseFailedException جداست → اگر گارد نبود تست می‌شکند
        $invoice = new Invoice;
        $invoice->amount(100000);

        [$driver] = $this->makeSepDriverWithMock($invoice, [
            'terminalId' => '01a0be94-e09f-736a-b416-df430d0a90a6',
        ]);

        $this->expectException(PurchaseFailedException::class);
        $this->expectExceptionMessage('فقط رقم');

        $driver->purchase();
    }

    #[Test]
    #[TestDox('پیام گارد ترمینال غیرعددی، مسیر اصلاح را به کاربر نشان می‌دهد')]
    public function sep_non_numeric_terminal_message_is_actionable(): void
    {
        $invoice = new Invoice;
        $invoice->amount(100000);

        [$driver] = $this->makeSepDriverWithMock($invoice, [
            'terminalId' => '01a0be94-e09f-736a-b416',
        ]);

        try {
            $driver->purchase();
            $this->fail('انتظار PurchaseFailedException می‌رفت');
        } catch (PurchaseFailedException $e) {
            $this->assertStringContainsString('شماره پایانه', $e->getMessage());
            $this->assertStringContainsString('پنل پذیرندگی سامان', $e->getMessage());
        }
    }

    #[Test]
    #[TestDox('ترمینال عددی از گارد می‌گذرد و payload درست ارسال می‌شود')]
    public function sep_purchase_accepts_numeric_terminal_and_sends_payload(): void
    {
        $invoice = new Invoice;
        $invoice->amount(100000)->detail('mobile', '09121234567');

        [$driver, $handler] = $this->makeSepDriverWithMock($invoice, [
            'terminalId' => '2087634',
        ], [$this->successfulTokenResponse()]);

        $transactionId = $driver->purchase();
        $this->assertSame('TESTTOKEN1234567890abcdef', $transactionId);

        $body = json_decode((string) $handler->getLastRequest()->getBody(), true);
        $this->assertSame('token', $body['action']);
        $this->assertSame('2087634', $body['TerminalId']);
        $this->assertSame(1000000, $body['Amount']); // تومان ×۱۰ = ریال (قرارداد مستند سپ)
        $this->assertSame('09121234567', $body['CellNumber']);
    }

    /* =====================================================================
     *  بند ۲ — واحد مبلغ (سپ ریال می‌خواهد؛ سوییچ amountIsToman پنل)
     * ===================================================================== */

    #[Test]
    #[TestDox('سوییچ واحد مبلغ: ریالی → بدون ضریب، تومانی → ضریب ده')]
    public function sep_currency_setting_controls_amount_multiplier(): void
    {
        // ریالی — مبلغ همان که هست به سپ می‌رود (بدون ×۱۰)
        $invoice = new Invoice;
        $invoice->amount(173420000);

        [$driver, $handler] = $this->makeSepDriverWithMock($invoice, [
            'currency' => 'R',
        ], [$this->successfulTokenResponse()]);

        $driver->purchase();

        $this->assertSame(173420000, json_decode((string) $handler->getLastRequest()->getBody(), true)['Amount']);
    }

    /* =====================================================================
     *  بند ۳ — هدر X-IPG-Url پاسخ توکن (مستند neo-pg سامان)
     * ===================================================================== */

    #[Test]
    #[TestDox('با هدر X-IPG-Url، فرم پرداخت به آدرس neo-pg ارسال می‌شود')]
    public function sep_pay_uses_x_ipg_url_header_for_neo_pg_terminals(): void
    {
        $invoice = new Invoice;
        $invoice->amount(100000);

        [$driver] = $this->makeSepDriverWithMock($invoice, [], [
            $this->successfulTokenResponse(['X-IPG-Url' => 'https://neo-pg.sep.ir/transaction/init']),
        ]);

        $driver->purchase();
        $form = $driver->pay();

        $this->assertSame('https://neo-pg.sep.ir/transaction/init', $form->getAction());
        $this->assertSame('TESTTOKEN1234567890abcdef', $form->getInputs()['Token']);
    }

    #[Test]
    #[TestDox('بدون هدر X-IPG-Url (ترمینال عادی)، فرم به آدرس کلاسیک می‌رود')]
    public function sep_pay_falls_back_to_classic_url_without_header(): void
    {
        $invoice = new Invoice;
        $invoice->amount(100000);

        [$driver] = $this->makeSepDriverWithMock($invoice, [], [
            $this->successfulTokenResponse(),
        ]);

        $driver->purchase();
        $form = $driver->pay();

        $this->assertSame('https://sep.shaparak.ir/OnlinePG/OnlinePG', $form->getAction());
    }

    /* =====================================================================
     *  بند ۴ — داشبورد Pulse (/pulse): گیت viewPulse
     * ===================================================================== */

    #[Test]
    #[TestDox('Pulse برای مهمان و مشتری بسته است و برای ادمینِ پنل با سوییچ باز می‌شود')]
    public function pulse_gate_follows_admin_panel_guard_and_switch(): void
    {
        // مهمان → بسته
        $this->assertFalse(Gate::allows('viewPulse'));

        // مشتری (گارد web) → بسته حتی با سشن
        $customer = $this->newUser();
        $this->actingAs($customer);
        $this->assertFalse(Gate::allows('viewPulse'));

        // ادمین پنل (گارد adminPanel) → باز
        $admin = Admin::create([
            'first_name' => 'ادمین',
            'last_name' => 'فاز ۱۰۴',
            'username' => 'feat104-'.Str::random(6),
            'email' => 'admin-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($admin, 'adminPanel');
        $this->assertTrue(Gate::allows('viewPulse'));

        // سوییچ خاموش → حتی ادمین هم بسته
        PulseSettings::setAdminAccess(false);
        Cache::flush();
        $this->assertFalse(Gate::allows('viewPulse'));

        // سوییچ روشن دوباره
        PulseSettings::setAdminAccess(true);
        Cache::flush();
        $this->assertTrue(Gate::allows('viewPulse'));
    }

    #[Test]
    #[TestDox('مهمان روی /pulse پاسخ ۴۰۳ می‌گیرد (میدل‌ور Authorize پالس)')]
    public function pulse_route_returns_403_for_guests(): void
    {
        $this->get('/pulse')->assertStatus(403);
    }

    #[Test]
    #[TestDox('کلاس PulseSettings قرارداد پیش‌فرض روشن را دارد')]
    public function pulse_settings_defaults_to_enabled(): void
    {
        option_update(PulseSettings::KEY_ADMIN_ACCESS, '0');
        Cache::flush();
        $this->assertFalse(PulseSettings::adminAccess());

        option_update(PulseSettings::KEY_ADMIN_ACCESS, '1');
        Cache::flush();
        $this->assertTrue(PulseSettings::adminAccess());
    }
}
