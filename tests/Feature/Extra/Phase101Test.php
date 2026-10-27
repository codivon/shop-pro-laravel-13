<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Settings\Gateways;
use App\Models\Admin;
use App\Models\Gateway;
use App\Models\Permission;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionProperty;
use Shetabit\Multipay\Drivers\SEP\SEP as SepDriver;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Invoice;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * فاز ۱۰۱ — درگاه سپ: ریشه‌کنی «پارامترهای ارسالی نامعتبر است.» مطابق مستند رسمی سامان نسخهٔ ۳.۶.
 * -------------------------------------------------------------------------------------------------
 * گزارش کاربر: «درگاه سپ را تنظیم کردم ولی موقع خرید وارد درگاه نمیشه» + لاگ:
 *   OrderController: خرید درگاه ناموفق {"order_id":3,"gateway":"sep","error":"پارامترهای ارسالی نامعتبر است."}
 *
 * ریشه‌ها (با مطالعهٔ مستند رسمی IPG سامان v3.6 + Postman Collection خود سپ):
 *  ۱) درایور سپ همیشه «CellNumber: ""» و «ResNum1..4: ""» خالی می‌فرستاد؛ سپ پارامترِ بی‌مقدار را
 *     در اعتبارسنجی رد می‌کند (نکتهٔ ۵ مستند: از ارسال پارامترهای غیر از پارامترهای مستند پرهیز کنید)
 *     → خطای ۵.
 *  ۲) errorDesc سپ (علت دقیق خطا در پاسخ) دور ریخته می‌شد → ریشه‌یابی ناممکن بود.
 *  ۳) گارد ترمینال خالی نبود → تنظیمات ناقص با همان خطای مبهم ۵ برگشت می‌خورد.
 *
 * قرارداد فیکس (همه در همین تست قفل شده‌اند):
 *  • payload حداقلی مطابق مستند: فقط پارامترهای مقداردار ارسال می‌شوند.
 *  • CellNumber فقط با شمارهٔ معتبر ۰۹xxxxxxxxx (نرمال‌شده از 9xxxxxxxxx / +98…) و با سوییچ پنل.
 *  • پیام خطا شامل «جزئیات سپ: errorDesc» + لاگ تشخیصی.
 *  • ترمینال خالی → خطای فارسیِ قابل‌اقدام بدون تماس با شاپرک.
 */
class Phase101Test extends TestCase
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

    /**
     * درایور سپ با MockHandler گازل — درخواست خروجی برای اظهار دقیق payload گرفته می‌شود.
     *
     * @return array{0: SepDriver, 1: MockHandler}
     */
    private function makeSepDriverWithMock(Invoice $invoice, array $settingsOverrides = [], array $responses = []): array
    {
        $handler = new MockHandler($responses);

        $driver = new SepDriver($invoice, $this->sepSettings($settingsOverrides));

        $property = new ReflectionProperty($driver, 'client');
        $property->setAccessible(true);
        $property->setValue($driver, new Client(['handler' => $handler]));

        return [$driver, $handler];
    }

    private function successfulTokenResponse(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], '{"status":1,"token":"TESTTOKEN1234567890abcdef"}');
    }

    /* =====================================================================
     *  بند ۱ — payload حداقلی مطابق مستند ۳.۶ (ریشهٔ اصلی خطای ۵)
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۱-الف — بدون موبایل: payload فقط ۵ پارامتر مستند است — CellNumber/ResNum1..4 خالی ارسال نمی‌شوند')]
    public function token_payload_is_minimal_per_doc36(): void
    {
        [$driver, $handler] = $this->makeSepDriverWithMock(
            (new Invoice)->amount(100000),
            responses: [$this->successfulTokenResponse()]
        );

        $driver->purchase();

        $body = json_decode((string) $handler->getLastRequest()->getBody(), true);

        $this->assertSame(['action', 'TerminalId', 'Amount', 'ResNum', 'RedirectUrl'], array_keys($body),
            'فقط پارامترهای مستند ۳.۶ با مقدار ارسال می‌شوند — ارسال "" = خطای ۵ سپ.');

        $this->assertSame('token', $body['action']);
        $this->assertSame('2087634', $body['TerminalId']);
        $this->assertSame('https://shop.test/orders/payment/callback/sep', $body['RedirectUrl']);
        $this->assertSame(1000000, $body['Amount'], 'مبلغ باید به ریال تبدیل شده باشد (تومان × ۱۰).');
        $this->assertSame($driver->getInvoice()->getUuid(), $body['ResNum']);

        $this->assertSame('TESTTOKEN1234567890abcdef', $driver->getInvoice()->getTransactionId());
    }

    #[Test]
    #[TestDox('بند ۱-ب — با موبایل معتبر: CellNumber نرمال‌شدهٔ ۰۹xxxxxxxxx ارسال می‌شود')]
    public function valid_mobile_is_normalized_and_sent(): void
    {
        foreach (['09123456789', '9123456789', '+98 912 345 6789', '۰۹۱۲۳۴۵۶۷۸۹'] as $mobile) {
            [$driver, $handler] = $this->makeSepDriverWithMock(
                (new Invoice)->amount(100000)->detail('mobile', $mobile),
                responses: [$this->successfulTokenResponse()]
            );

            $driver->purchase();

            $body = json_decode((string) $handler->getLastRequest()->getBody(), true);

            $this->assertSame('09123456789', $body['CellNumber'] ?? null, "موبایل «{$mobile}» باید نرمال شود.");
        }
    }

    #[Test]
    #[TestDox('بند ۱-پ — موبایل نامعتبر یا خالی: CellNumber کلاً از payload حذف می‌شود (نه "" خالی)')]
    public function invalid_mobile_is_dropped_not_sent_empty(): void
    {
        foreach (['', 'abc', '123', '09123456789012345', 'username'] as $mobile) {
            [$driver, $handler] = $this->makeSepDriverWithMock(
                (new Invoice)->amount(100000)->detail('mobile', $mobile),
                responses: [$this->successfulTokenResponse()]
            );

            $driver->purchase();

            $body = json_decode((string) $handler->getLastRequest()->getBody(), true);

            $this->assertArrayNotHasKey('CellNumber', $body, "موبایل نامعتبر «{$mobile}» نباید ارسال شود.");
        }
    }

    #[Test]
    #[TestDox('بند ۱-ت — سوییچ sendCellNumber: خاموش («0» یا "" تیک‌برداشتهٔ پنل) → CellNumber حذف می‌شود')]
    public function cell_number_toggle_is_respected(): void
    {
        foreach (['0', '', false] as $toggleValue) {
            [$driver, $handler] = $this->makeSepDriverWithMock(
                (new Invoice)->amount(100000)->detail('mobile', '09123456789'),
                ['sendCellNumber' => $toggleValue],
                [$this->successfulTokenResponse()]
            );

            $driver->purchase();

            $body = json_decode((string) $handler->getLastRequest()->getBody(), true);

            $this->assertArrayNotHasKey('CellNumber', $body, 'با سوییچ خاموش، موبایل نباید ارسال شود.');
        }
    }

    /* =====================================================================
     *  بند ۲ — گارد تنظیمات: ترمینال خالی → خطای فارسیِ قابل‌اقدام (بدون تماس با شاپرک)
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۲ — ترمینال خالی/فاصله: پیام قابل‌اقدامِ تنظیمات — بدون هیچ درخواست HTTP')]
    public function empty_terminal_id_fails_fast_with_actionable_message(): void
    {
        [$driver, $handler] = $this->makeSepDriverWithMock(
            (new Invoice)->amount(100000),
            ['terminalId' => '   ']
        );

        try {
            $driver->purchase();
            $this->fail('ترمینال خالی باید استثنا بدهد.');
        } catch (PurchaseFailedException $e) {
            $this->assertStringContainsString('کد پایانه', $e->getMessage());
            $this->assertStringContainsString('تنظیمات', $e->getMessage());
        }

        $this->assertNull($handler->getLastRequest(), 'هیچ درخواستی به شاپرک نباید برود.');
    }

    /* =====================================================================
     *  بند ۳ — errorDesc سپ دیگر دور ریخته نمی‌شود (ریشه‌یابی در production ممکن شود)
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۳-الف — پاسخ خطای سپ با errorDesc: پیام = پیام ثابت + «جزئیات سپ»')]
    public function token_error_desc_is_surfaced(): void
    {
        [$driver] = $this->makeSepDriverWithMock(
            (new Invoice)->amount(100000),
            responses: [new Response(200, ['Content-Type' => 'application/json'],
                '{"status":-1,"errorCode":"5","errorDesc":"پارامترهای ارسالی نامعتبر است. شماره تراکنش پذیرنده الزامی است"}')]
        );

        try {
            $driver->purchase();
            $this->fail('خطای سپ باید استثنا بدهد.');
        } catch (PurchaseFailedException $e) {
            $this->assertStringContainsString('پارامترهای ارسالی نامعتبر است.', $e->getMessage());
            $this->assertStringContainsString('جزئیات سپ', $e->getMessage());
            $this->assertStringContainsString('شماره تراکنش پذیرنده الزامی است', $e->getMessage(),
                'errorDesc واقعی سپ باید در پیام باشد — قبلاً دور ریخته می‌شد.');
        }
    }

    #[Test]
    #[TestDox('بند ۳-ب — کد خطای ناشناخته: کد و desc در پیام می‌آید (نه «خطای ناشناختهٔ» بی‌جزئیات)')]
    public function unknown_error_code_includes_details(): void
    {
        [$driver] = $this->makeSepDriverWithMock(
            (new Invoice)->amount(100000),
            responses: [new Response(200, ['Content-Type' => 'application/json'],
                '{"status":-1,"errorCode":"42","errorDesc":"توضیح جدید سپ"}')]
        );

        try {
            $driver->purchase();
            $this->fail('خطای سپ باید استثنا بدهد.');
        } catch (PurchaseFailedException $e) {
            $this->assertStringContainsString('42', $e->getMessage());
            $this->assertStringContainsString('توضیح جدید سپ', $e->getMessage());
        }
    }

    #[Test]
    #[TestDox('بند ۳-پ — وریفای: ResultDesc پاسخ وریفای هم در پیام خطا می‌آید')]
    public function verify_result_desc_is_surfaced(): void
    {
        request()->merge(['Status' => '2', 'RefNum' => 'REF101', 'Token' => 'MOCKTOKEN101']);

        [$driver] = $this->makeSepDriverWithMock(
            (new Invoice)->amount(100000),
            responses: [new Response(200, ['Content-Type' => 'application/json'],
                '{"ResultCode":-104,"ResultDesc":"مشخصات تراکنش یافت نشد"}')]
        );

        $driver->getInvoice()->transactionId('MOCKTOKEN101');

        try {
            $driver->verify();
            $this->fail('وریفای ناموفق باید استثنا بدهد.');
        } catch (InvalidPaymentException $e) {
            $this->assertStringContainsString('پارامترهای ارسالی نامعتبر است.', $e->getMessage());
            $this->assertStringContainsString('مشخصات تراکنش یافت نشد', $e->getMessage());
        }
    }

    /* =====================================================================
     *  بند ۴ — سیم‌کشی تنظیمات: سوییچ از پنل درگاه‌ها (الزام شخصی‌سازی کاربر)
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۴-الف — پنل: فیلد سوییچ sendCellNumber برای سپ با پیش‌فرض روشن تعریف شده')]
    public function sep_settings_have_cell_number_toggle(): void
    {
        $fields = collect(Gateways::gatewayFields('sep'));
        $toggle = $fields->firstWhere('name', 'sendCellNumber');

        $this->assertNotNull($toggle, 'فیلد سوییچ موبایل سپ در پنل درگاه‌ها باید باشد.');
        $this->assertSame('toggle', $toggle['type']);
        $this->assertSame('1', $toggle['default'] ?? '', 'پیش‌فرض سوییچ باید روشن باشد.');

        // کانفیگ اپ هم پیش‌فرض دارد (برای اجرای بدون ردیف پنل)
        $this->assertSame('1', config('payment.drivers.sep.sendCellNumber'));
    }

    #[Test]
    #[TestDox('بند ۴-ب — جریان DB: مقدار سوییچ از جدول درگاه به get_gateway_configs می‌رسد')]
    public function cell_number_config_flows_from_database(): void
    {
        Gateway::where('key', 'sep')->delete();

        $gateway = Gateway::create(['key' => 'sep', 'name' => 'درگاه سپ (سمان الکترونیک)', 'is_active' => true]);

        // هنوز ذخیره نشده → پیش‌فرض روشن
        $configs = get_gateway_configs('sep');
        $this->assertSame('1', $configs['sendCellNumber'] ?? null);
        $this->assertArrayHasKey('terminalId', $configs);

        // خاموش از پنل
        $gateway->configs()->updateOrCreate(['key' => 'sendCellNumber'], ['value' => '0']);
        $this->assertSame('0', get_gateway_configs('sep')['sendCellNumber']);

        // روشن از پنل
        $gateway->configs()->updateOrCreate(['key' => 'sendCellNumber'], ['value' => '1']);
        $this->assertSame('1', get_gateway_configs('sep')['sendCellNumber']);
    }

    #[Test]
    #[TestDox('بند ۴-پ — blade پنل: رندر سوییچ toggle (مستقل از درگاه) کار می‌کند')]
    public function gateways_blade_renders_toggle_field(): void
    {
        $this->newCreator();

        Gateway::where('key', 'sep')->delete();

        $gateway = Gateway::create(['key' => 'sep', 'name' => 'درگاه سپ (سمان الکترونیک)', 'is_active' => true]);
        $gateway->configs()->updateOrCreate(['key' => 'terminalId'], ['value' => '2087634']);

        Livewire::test(Gateways::class)
            ->assertSee('sendCellNumber')
            ->assertSee('ارسال شماره موبایل خریدار به درگاه');
    }

    /* =====================================================================
     *  بند ۵ — کنترلر خرید: موبایل سفارش به فاکتور سپ تزریق می‌شود
     * ===================================================================== */

    #[Test]
    #[TestDox('بند ۵ — قرارداد کنترلر: pay برای سپ موبایل سفارش را با سوییچ تزریق می‌کند')]
    public function order_controller_injects_mobile_for_sep(): void
    {
        $source = file_get_contents(base_path('themes/WeblakShop/src/Controllers/OrderController.php'));

        $this->assertStringContainsString('$gateway === '."'sep'", $source,
            'تزریق موبایل فقط برای سپ است — درگاه‌های دیگر بی‌تغییر.');
        $this->assertStringContainsString('sendCellNumber', $source,
            'سوییچ تنظیمات در جریان خرید لحاظ شود.');
        $this->assertStringContainsString("detail('mobile'", $source,
            'موبایل سفارش به detail فاکتور می‌رود (درایور نرمال/اعتبارسنجی می‌کند).');
    }

    /**
     * ادمین creator با گیت settings.gateway (همان قرارداد Phase99Test)
     */
    private function newCreator(): Admin
    {
        // گیت‌ها از ردیف‌های فعال جدول permissions می‌آیند + یک گیت صریح برای صفحهٔ درگاه‌ها
        foreach (Permission::where('active', true)->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        Gate::define('settings.gateway', fn ($user = null) => true);

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۰۱',
            'username' => 'feat101-'.Str::random(6),
            'email' => 'feat101-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }
}
