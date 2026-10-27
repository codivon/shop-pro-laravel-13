<?php

namespace Shetabit\Multipay;

use Shetabit\Multipay\Contracts\DriverInterface;
use Shetabit\Multipay\Contracts\ReceiptInterface;
use Shetabit\Multipay\Exceptions\DriverNotFoundException;
use Shetabit\Multipay\Exceptions\InvoiceNotFoundException;
use Shetabit\Multipay\Exceptions\PreviouslyVerifiedException;
use Shetabit\Multipay\Exceptions\PurchaseFailedException;
use Shetabit\Multipay\Exceptions\TimeoutException;
use Shetabit\Multipay\Traits\HasPaymentEvents;
use Shetabit\Multipay\Traits\InteractsWithRedirectionForm;

class Payment
{
    use HasPaymentEvents;
    use InteractsWithRedirectionForm;

    /**
     * Payment Configuration.
     */
    protected array $config;

    /**
     * Payment Driver Settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * callbackUrl
     *
     * @var string
     */
    protected $callbackUrl;

    /**
     * Payment Driver Name.
     *
     * @var string
     */
    protected $driver;

    /**
     * Payment Driver Instance.
     *
     * @var object
     */
    protected $driverInstance;

    /**
     * @var Invoice
     */
    protected $invoice;

    /**
     * PaymentManager constructor.
     *
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        // فاز ۱۰۰ — merge کانفیگ پیش‌فرض پکیج زیر کانفیگ اپ:
        // اگر کانفیگ اپ کهنه/کش‌شده باشد و درایور جدیدی (مثل «سپ») در آن نباشد،
        // به‌جای کرشِ «Driver not found in config file»، تنظیمات پیش‌فرض پکیج
        // (endpointها + map + currency) استفاده می‌شود؛ مقادیر runtime (terminalId و …)
        // هم از جدول gateways با ->config() تزریق می‌شود.
        $this->config = $config === []
            ? $this->loadDefaultConfig()
            : array_replace_recursive($this->loadDefaultConfig(), $config);
        $this->invoice(new Invoice);
        $this->via($this->config['default']);
    }

    /**
     * Retrieve Default config's path.
     */
    public static function getDefaultConfigPath(): string
    {
        return dirname(__DIR__).'/config/payment.php';
    }

    /**
     * Set custom configs
     * we can use this method when we want to use dynamic configs
     *
     *
     * @return $this
     */
    public function config($key, $value = null): static
    {
        $configs = [];

        $key = is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            $configs[$k] = $v;
        }

        $this->settings = array_merge($this->settings, $configs);

        return $this;
    }

    /**
     * Set callbackUrl.
     *
     * @return $this
     */
    public function callbackUrl($url = null): static
    {
        $this->config('callbackUrl', $url);

        return $this;
    }

    /**
     * Reset the callbackUrl to its original that exists in configs.
     *
     * @return $this
     */
    public function resetCallbackUrl(): static
    {
        $this->callbackUrl();

        return $this;
    }

    /**
     * Set payment amount.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function amount($amount): static
    {
        $this->invoice->amount($amount);

        return $this;
    }

    /**
     * Set a piece of data to the details.
     *
     *
     *
     * @return $this
     */
    public function detail($key, $value = null): static
    {
        $this->invoice->detail($key, $value);

        return $this;
    }

    /**
     * Set transaction's id
     *
     *
     * @return $this
     */
    public function transactionId($id): static
    {
        $this->invoice->transactionId($id);

        return $this;
    }

    /**
     * Change the driver on the fly.
     *
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function via($driver): static
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->invoice->via($driver);
        $this->settings = array_merge($this->loadDefaultConfig()['drivers'][$driver] ?? [], $this->config['drivers'][$driver]);

        return $this;
    }

    /**
     * Purchase the invoice
     *
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function purchase(?Invoice $invoice = null, $finalizeCallback = null): static
    {
        if ($invoice instanceof Invoice) { // create new invoice
            $this->invoice($invoice);
        }

        $this->driverInstance = $this->getFreshDriverInstance();

        // purchase the invoice
        $transactionId = $this->driverInstance->purchase();
        if ($finalizeCallback) {
            call_user_func_array($finalizeCallback, [$this->driverInstance, $transactionId]);
        }

        // dispatch event
        $this->dispatchEvent(
            'purchase',
            $this->driverInstance,
            $this->driverInstance->getInvoice()
        );

        return $this;
    }

    /**
     * Pay the purchased invoice.
     *
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function pay($initializeCallback = null)
    {
        $this->driverInstance = $this->getDriverInstance();

        if ($initializeCallback) {
            call_user_func($initializeCallback, $this->driverInstance);
        }

        $this->validateInvoice();

        // dispatch event
        $this->dispatchEvent(
            'pay',
            $this->driverInstance,
            $this->driverInstance->getInvoice()
        );

        return $this->driverInstance->pay();
    }

    /**
     * Verifies the payment
     *
     *
     *
     * @throws InvoiceNotFoundException
     * @throws PurchaseFailedException
     * @throws PreviouslyVerifiedException
     * @throws TimeoutException
     */
    public function verify($finalizeCallback = null): ReceiptInterface
    {
        $this->driverInstance = $this->getDriverInstance();
        $this->validateInvoice();
        $receipt = $this->driverInstance->verify();

        if (! empty($finalizeCallback)) {
            call_user_func($finalizeCallback, $receipt, $this->driverInstance);
        }

        // dispatch event
        $this->dispatchEvent(
            'verify',
            $receipt,
            $this->driverInstance,
            $this->driverInstance->getInvoice()
        );

        return $receipt;
    }

    /**
     * Retrieve default config.
     */
    protected function loadDefaultConfig(): array
    {
        return require static::getDefaultConfigPath();
    }

    /**
     * Set invoice instance.
     */
    protected function invoice(Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Retrieve current driver instance or generate new one.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getDriverInstance()
    {
        if (! empty($this->driverInstance)) {
            return $this->driverInstance;
        }

        return $this->getFreshDriverInstance();
    }

    /**
     * Get new driver instance
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function getFreshDriverInstance()
    {
        $this->validateDriver();
        $class = $this->config['map'][$this->driver];

        if (! empty($this->callbackUrl)) { // use custom callbackUrl if exists
            $this->settings['callbackUrl'] = $this->callbackUrl;
        }

        return new $class($this->invoice, $this->settings);
    }

    /**
     * Validate Invoice.
     *
     * @throws InvoiceNotFoundException
     */
    protected function validateInvoice()
    {
        if (empty($this->invoice)) {
            throw new InvoiceNotFoundException('Invoice not selected or does not exist.');
        }
    }

    /**
     * Validate driver.
     *
     * @throws \Exception
     */
    protected function validateDriver()
    {
        if (empty($this->driver)) {
            throw new DriverNotFoundException('درگاه پرداخت انتخاب نشده است.');
        }

        if (empty($this->config['drivers'][$this->driver]) || empty($this->config['map'][$this->driver])) {
            // فاز ۱۰۰ — پیام فارسی و قابل‌اقدام: این خطا تقریباً همیشه یعنی کش کانفیگ کهنه است
            throw new DriverNotFoundException('تنظیمات درایور «'.$this->driver.'» در کانفیگ پرداخت یافت نشد؛ کش کانفیگ قدیمی است. دستور php artisan optimize:clear را اجرا کنید.');
        }

        if (! class_exists($this->config['map'][$this->driver])) {
            throw new DriverNotFoundException('کلاس درایور «'.$this->driver.'» یافت نشد؛ لطفاً فایل‌های پکیج پرداخت را به‌روز کنید.');
        }

        $reflect = new \ReflectionClass($this->config['map'][$this->driver]);

        if (! $reflect->implementsInterface(DriverInterface::class)) {
            throw new \Exception("درایور «{$this->driver}» باید از DriverInterface پیروی کند.");
        }
    }
}
