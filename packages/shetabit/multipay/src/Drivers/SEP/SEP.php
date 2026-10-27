<?php

namespace Shetabit\Multipay\Drivers\SEP;

use GuzzleHttp\Client;
use Shetabit\Multipay\Abstracts\Driver;
use Shetabit\Multipay\Contracts\ReceiptInterface;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Receipt;
use Shetabit\Multipay\RedirectionForm;
use Shetabit\Multipay\Request;

class SEP extends Driver
{
    /**
     * SEP HTTP Client.
     *
     * @var object
     */
    protected Client $client;

    /**
     * Invoice
     *
     * @var Invoice
     */
    protected $invoice;

    /**
     * Driver settings
     *
     * @var object
     */
    protected $settings;

    /**
     * فاز ۱۰۴ — آدرس پایهٔ neo-pg از هدر X-IPG-Url پاسخ توکن (مستند «راهنمای اتصال به neo-pg» سپ).
     *
     * برای ترمینال‌هایی که neo-pg رویشان فعال است، سپ در هدر X-IPG-Url پاسخِ توکن، آدرس
     * جدید پایه را برمی‌گرداند (نمونهٔ مستند: https://neo-pg.sep.ir/transaction/init) و
     * توکن باید به «همین آدرس» پست شود — فیلدها همان قبلی است. برای ترمینال‌های عادی
     * هدر وجود ندارد و آدرس کلاسیک apiPaymentUrl استفاده می‌شود.
     */
    protected ?string $neoPgBaseUrl = null;

    /**
     * SEP constructor.
     * Construct the class with the relevant settings.
     */
    public function __construct(Invoice $invoice, $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object) $settings;
        $this->client = new Client([
            'curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1'],
        ]);
    }

    /**
     * Purchase Invoice.
     *
     * @return string
     *
     * @throws PurchaseFailedException
     * @throws \SoapFault
     */
    public function purchase()
    {
        // فاز ۱۰۱ — گارد تنظیمات: ترمینال خالی/فاصله‌دار → خطای فارسیِ قابل‌اقدام بدون رفت‌وبرگشت به شاپرک
        // (قبلاً TerminalId خالی به سپ می‌رفت و همان خطای مبهم «پارامترهای ارسالی نامعتبر است» برمی‌گشت)
        $terminalId = trim((string) ($this->settings->terminalId ?? ''));
        if ($terminalId === '') {
            throw new PurchaseFailedException('کد پایانه (Terminal ID) درگاه سپ در تنظیمات خالی است — آن را از پنل مدیریت ← تنظیمات ← درگاه‌های پرداخت وارد کنید.');
        }

        // فاز ۱۰۴ — گارد فرمت ترمینال: مستند رسمی سپ (جدول پارامترها) نوع TerminalId را
        // «Int64» تعریف می‌کند؛ ترمینال سپ همیشه فقط رقم است (مثل ۱۷۴۶۲۷۵۵). مقدار غیرعددی
        // (مثلاً UUID کپی‌شده از فیلد دیگری پنل سامان) در اعتبارسنجی سپ رد می‌شود و همان
        // خطای مبهم کد ۵ «پارامترهای ارسالی نامعتبر است؛ شماره ترمینال الزامی است» را می‌دهد.
        // این گارد قبل از رفت‌وبرگشت به شاپرک، علت را شفاف و قابل‌اقدام می‌کند.
        if (! ctype_digit($terminalId)) {
            throw new PurchaseFailedException('شماره پایانهٔ درگاه سپ باید فقط رقم باشد (مستند رسمی سپ: Int64) — مقدار واردشده در تنظیمات «'.substr($terminalId, 0, 12).'…» غیرعددی است. «شماره پایانه» عددی را از پنل پذیرندگی سامان بردارید و در تنظیمات ← درگاه‌های پرداخت ← سپ وارد کنید (شناسهٔ UUID درگاه، شمارهٔ پایانه نیست).');
        }

        // فاز ۱۰۱ — payload دقیقاً مطابق مستند رسمی سپ نسخهٔ ۳.۶:
        //   پارامترهای اجباری: action, TerminalId, Amount (ریال، عدد صحیح), ResNum, RedirectUrl
        //   پارامترهای اختیاری (CellNumber/ResNum1..4) فقط «در صورت داشتن مقدار» ارسال می‌شوند —
        //   ارسال رشتهٔ خالی ("" برای CellNumber و ResNum1..4 که کد قبلی همیشه می‌فرستاد) در اعتبارسنجی سپ
        //   رد می‌شود و خطای ۵ «پارامترهای ارسالی نامعتبر است» می‌دهد (نکتهٔ ۵ مستند: از ارسال
        //   پارامترهای بی‌مقدار پرهیز کنید).
        $data = [
            'action' => 'token',
            'TerminalId' => $terminalId,
            // فالبک امن: اگر کلید currency در کانفیگ نبود، مبلغ به ریال تبدیل شود (قرارداد سپ)
            'Amount' => (int) ($this->invoice->getAmount() * (($this->settings->currency ?? 'T') == 'T' ? 10 : 1)), // convert to rial
            'ResNum' => $this->invoice->getUuid(),
            'RedirectUrl' => $this->settings->callbackUrl,
        ];

        // فاز ۱۰۱ — CellNumber طبق مستند (شماره موبایل خریدار برای واکشی کارت‌های ذخیره‌شده) —
        // فقط با مقدار معتبر ۰۹xxxxxxxxx؛ سوییچ تنظیمات sendCellNumber قابل خاموش‌کردن است (الزام شخصی‌سازی)
        $cellNumber = $this->normalizedCellNumber();
        if ($cellNumber !== null) {
            $data['CellNumber'] = $cellNumber;
        }

        // فاز ۱۰۱ — ResNum1..4 (دیتای اضافی گزارش‌گیری پنل سپ) فقط وقتی مقدار دارند ارسال می‌شوند
        foreach (['ResNum1', 'ResNum2', 'ResNum3', 'ResNum4'] as $extraKey) {
            $extraValue = trim((string) ($this->invoice->getDetail($extraKey) ?? ''));
            if ($extraValue !== '') {
                $data[$extraKey] = $extraValue;
            }
        }

        $response = $this->client->post(
            $this->settings->apiGetToken,
            [
                'json' => $data,
            ]
        );

        // فاز ۱۰۴ — هدر X-IPG-Url پاسخ توکن (مستند neo-pg): برای ترمینال‌های neo-pg فعال،
        // آدرس پایهٔ جدید درگاه در همین هدر می‌آید و pay() باید توکن را به آن بفرستد.
        $neoPgUrl = trim((string) $response->getHeaderLine('X-IPG-Url'));
        $this->neoPgBaseUrl = $neoPgUrl !== '' ? $neoPgUrl : null;

        $responseStatus = $response->getStatusCode();

        if ($responseStatus != 200) { // if something has done in a wrong way
            $this->purchaseFailed(0);
        }

        $jsonBody = $response->getBody()->getContents();
        $responseData = json_decode($jsonBody, true);

        if ($responseData['status'] != 1) {
            // فاز ۱۰۱ — errorDesc سپ دیگر دور ریخته نمی‌شود؛ در پیام استثنا + لاگ می‌آید
            // (مستند ۳.۶: پاسخ خطا {status:-1, errorCode, errorDesc} — errorDesc علت دقیق را می‌گوید)
            $this->purchaseFailed($responseData['errorCode'] ?? 0, (string) ($responseData['errorDesc'] ?? ''));
        }

        // set transaction id
        $this->invoice->transactionId($responseData['token']);

        // return the transaction's id
        return $this->invoice->getTransactionId();
    }

    /**
     * فاز ۱۰۱ — نرمال‌سازی شماره موبایل خریدار برای پارامتر CellNumber سپ.
     *
     * فقط شماره‌های معتبر موبایل ایران (09xxxxxxxxx) برگردانده می‌شوند؛ در غیر این صورت null
     * (پارامتر کاملاً حذف می‌شود — ارسال مقدار نامعتبر خطای ۵ سپ می‌دهد).
     */
    private function normalizedCellNumber(): ?string
    {
        // سوییچ تنظیمات درگاه — whitelist سخت‌گیرانه: فقط '1'/1/true روشن است؛
        // هر چیز دیگر ('0'، '' از تیک‌برداشتن سوییچ پنل، false) یعنی خاموش. نبودِ کلید = روشن (پیش‌فرض)
        $send = $this->settings->sendCellNumber ?? '1';
        if (! in_array($send, ['1', 1, true], true)) {
            return null;
        }

        // ارقام فارسی/عربی → لاتین (ورودی فارسی‌زبان) سپس حذف هر کاراکتر غیررقمی
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latinDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $mobile = str_replace($persianDigits, $latinDigits, (string) ($this->invoice->getDetail('mobile') ?? ''));
        $mobile = str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], $latinDigits, $mobile);
        $mobile = preg_replace('/\D+/', '', $mobile);
        if ($mobile === '') {
            return null;
        }

        // هم‌سان‌سازی: 989xxxxxxxxx یا 9xxxxxxxxx → 09xxxxxxxxx
        if (strlen($mobile) === 12 && str_starts_with($mobile, '98')) {
            $mobile = '0'.substr($mobile, 2);
        }

        if (strlen($mobile) === 10 && str_starts_with($mobile, '9')) {
            $mobile = '0'.$mobile;
        }

        if (! preg_match('/^09\d{9}$/', $mobile)) {
            return null;
        }

        return $mobile;
    }

    /**
     * Pay the Invoice
     */
    public function pay(): RedirectionForm
    {
        // فاز ۱۰۴ — ترمینال‌های neo-pg فعال: آدرس پایه از هدر X-IPG-Url پاسخ توکن
        // (مستند neo-pg: «فیلدهای دریافتی و ارسالی ... هیچ گونه تغییری نداشته و تنها
        // تغییر آدرس پایه می‌باشد») — برای ترمینال‌های عادی هدر خالی است و آدرس کلاسیک.
        $payUrl = $this->neoPgBaseUrl ?: $this->settings->apiPaymentUrl;

        return $this->redirectWithForm(
            $payUrl,
            [
                'Token' => $this->invoice->getTransactionId(),
                'GetMethod' => false,
            ]
        );
    }

    /**
     * Verify payment
     *
     *
     * @throws InvalidPaymentException
     * @throws \SoapFault
     * @throws PurchaseFailedException
     */
    public function verify(): ReceiptInterface
    {
        // فاز ۱۰۰ — وضعیت موفق در کال‌بک سپ: «Status» (قرارداد upstream) با فالبک «StateCode»
        // (مستند رسمی OnlinePG سامان کد ۲ را با فیلد StateCode برمی‌گرداند)
        $status = (int) (Request::input('Status') ?? Request::input('StateCode') ?? 0);
        if ($status != 2) {
            $this->purchaseFailed($status);
        }

        $data = [
            'RefNum' => Request::input('RefNum'),
            'TerminalNumber' => $this->settings->terminalId,
        ];

        // فاز ۱۰۰ — باگ تأیید: transactionIdِ فاکتور همان «توکن درگاه» است که در purchase ذخیره
        // شده و در کال‌بک با فیلد Token برمی‌گردد. نسخهٔ قبلی با ResNum مقایسه می‌کرد (شناسهٔ
        // فاکتور) که هرگز با توکن برابر نیست → تأیید هر پرداخت موفق سپ با خطای 108- رد می‌شد.
        // (هم‌سو با فیکس upstream shetabit/multipay)
        if ((string) $this->invoice->getTransactionId() !== (string) Request::input('Token')) {
            $this->notVerified(-108);
        }

        $response = $this->client->post(
            $this->settings->apiVerificationUrl,
            [
                'json' => $data,
            ]
        );

        if ($response->getStatusCode() != 200) {
            $this->notVerified(0);
        }

        $jsonData = $response->getBody()->getContents();
        $responseData = json_decode($jsonData, true);

        if ($responseData['ResultCode'] != 0) {
            // فاز ۱۰۱ — ResultDesc سپ (اگر در پاسخ باشد) در پیام استثنا می‌آید — علت دقیق ردِ تأیید
            $this->notVerified(
                $responseData['ResultCode'],
                (string) ($responseData['ResultDesc'] ?? $responseData['resultDesc'] ?? '')
            );
        }

        $transactionDetail = $responseData['TransactionDetail'];

        // فاز ۱۰۰ — کست عددی: شاپرک ممکن است مبلغ را رشته برگرداند؛ مقایسهٔ سخت‌گیرانه !==
        // بین int و string باعث ردِ نادرست تأیید می‌شد.
        $verifiedAmount = (int) ($this->invoice->getAmount() * (($this->settings->currency ?? 'T') == 'T' ? 10 : 1));
        if ($verifiedAmount !== (int) $transactionDetail['AffectiveAmount']) {
            $this->notVerified(-107);
        }

        $receipt = $this->createReceipt($data['RefNum']);
        $receipt->detail([
            'traceNo' => $transactionDetail['StraceNo'],
            'referenceNo' => $transactionDetail['RRN'],
            'transactionId' => Request::input('ResNum'),
            'RefNum' => $transactionDetail['RefNum'],
            'cardNo' => $transactionDetail['MaskedPan'],
        ]);

        // Add additional data specific for SEP gateway
        $receipt->detail([
            'TerminalNumber' => $transactionDetail['TerminalNumber'],
            'OrginalAmount' => $transactionDetail['OrginalAmount'],
            'AffectiveAmount' => $transactionDetail['AffectiveAmount'],
            'StraceDate' => $transactionDetail['StraceDate'],
            // SEP documents are not up-to-date. This will fix different
            // between variable name in docs and actual returned values.
            'Amount' => $transactionDetail['OrginalAmount'],
        ]);

        return $receipt;
    }

    /**
     * Generate the payment's receipt
     */
    protected function createReceipt($referenceId): Receipt
    {
        return new Receipt('saman', $referenceId);
    }

    /**
     * Trigger an exception
     *
     * فاز ۱۰۱ — پارامتر errorDesc: پیام دقیق سپ (errorDesc پاسخ توکن) به پیام اضافه می‌شود و
     * کل پاسخ خطا در لاگ ثبت می‌گردد؛ قبلاً علت دقیق سپ دور ریخته می‌شد و فقط پیام ثابت
     * (مثل «پارامترهای ارسالی نامعتبر است.») باقی می‌ماند و ریشه‌یابی غیرممکن بود.
     *
     * @throws PurchaseFailedException
     */
    protected function purchaseFailed($status, string $errorDesc = '')
    {
        $translations = [
            1 => ' تراکنش توسط خریدار لغو شده است.',
            2 => 'پرداخت با موفقیت انجام شد.',
            3 => 'پرداخت انجام نشد.',
            4 => 'کاربر در بازه زمانی تعیین شده پاسخی ارسال نکرده است.',
            5 => 'پارامترهای ارسالی نامعتبر است.',
            8 => 'آدرس سرور پذیرنده نامعتبر است (IP پذیرنده باید نزد سامان ثبت شده باشد).',
            9 => 'رمز کارت 3 مرتبه اشتباه وارد شده است در نتیجه کارت غیر فعال خواهد شد.',
            10 => 'توکن ارسال شده یافت نشد.',
            11 => 'با این شماره ترمینال فقط تراکنش های توکنی قابل پرداخت هستند.',
            12 => 'شماره ترمینال ارسال شده یافت نشد.',
        ];

        $message = $translations[$status] ?? ('خطای ناشناخته‌ای از درگاه سپ رخ داده است. (کد خطا: '.$status.')');

        $errorDesc = trim($errorDesc);

        if ($errorDesc !== '') {
            $message .= ' | جزئیات سپ: '.$errorDesc;
        }

        logger()->warning('SEP purchase failed', [
            'errorCode' => $status,
            'errorDesc' => $errorDesc,
            'terminalId' => trim((string) ($this->settings->terminalId ?? '')),
            'amount' => $this->invoice->getAmount(),
            'resNum' => $this->invoice->getUuid(),
        ]);

        throw new PurchaseFailedException($message);
    }

    /**
     * Trigger an exception
     *
     * فاز ۱۰۱ — پارامتر resultDesc: پیام دقیق سرویس وریفای سپ (ResultDesc) به پیام اضافه می‌شود.
     *
     * @throws InvalidPaymentException
     */
    private function notVerified($status, string $resultDesc = ''): void
    {
        $translations = [
            -2 => ' تراکنش یافت نشد.',
            -6 => 'بیش از 30 دقیقه از زمان اجرای تراکنش گذشته است.',
            2 => 'کاربر در بازه زمانی تعیین شده پاسخی ارسال نکرده است.',
            -104 => 'پارامترهای ارسالی نامعتبر است.',
            -105 => 'آدرس سرور پذیرنده نامعتبر است.',
            -106 => 'رمز کارت 3 مرتبه اشتباه وارد شده است در نتیجه کارت غیر فعال خواهد شد.',
            -107 => 'مبلغ برگشتی با مبلغ فاکتور همخوانی ندارد.',
            -108 => 'اطلاعات پرداخت با فاکتور همخوانی ندارد.',
        ];

        $message = $translations[$status] ?? ('خطای ناشناخته‌ای از درگاه سپ رخ داده است. (کد خطا: '.$status.')');

        $resultDesc = trim($resultDesc);

        if ($resultDesc !== '' && ! str_contains($message, $resultDesc)) {
            $message .= ' | جزئیات سپ: '.$resultDesc;
        }

        throw new InvalidPaymentException($message, (int) $status);
    }
}
