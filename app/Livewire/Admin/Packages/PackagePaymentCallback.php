<?php

namespace App\Livewire\Admin\Packages;

use App\Jobs\InstallPackageJob;
use App\Jobs\InstallThemeJob;
use App\Models\InstalledModule;
use App\Models\InstalledTheme;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * callback درگاه پرداخت پکیج‌ها — پورت ۱:۱ Back\PackagePaymentController::callback
 *
 * منطق قدیمی مستقیماً redirect است؛ بنابراین کل منطق در mount اجرا و
 * با session('payment_result') به صفحه ایندکس بازار ریدایرکت می‌شود
 * (همان قرارداد ویوی قدیمی برای نمایش مودال نتیجه پرداخت).
 */
#[Title('نتیجه پرداخت')]
class PackagePaymentCallback extends Component
{
    public ?array $result = null;

    public function mount(Request $request): void
    {
        // فاز ۴۴ — callback خرید قالب‌ها از همان مسیر با پارامتر type=template
        if ($request->query('type') === 'template') {
            $this->handleTemplateCallback($request);

            return;
        }

        $transactionId = $request->query('transaction_id')
            ?? $request->query('Authority')
            ?? $request->query('transactionId')
            ?? $request->query('tracking_code');

        $purchaseId = $request->query('purchase_id');

        // ★ پارامترهای لغو/خطا که درگاه به callback اضافه می‌کند
        $isCancelled = $request->query('cancel') === 'true' || $request->query('status') === 'failed';
        $gatewayError = $request->query('error');   // «تراکنش توسط خریدار لغو شده است.»

        if (!$transactionId && !$purchaseId) {
            $this->redirectToIndexWithResult('failed', 'پرداخت ناموفق بود',
                $gatewayError ?: 'اطلاعات تراکنش ناقص است.');
            return;
        }

        $purchase = PackagePurchase::query()
            ->when($transactionId, fn ($q) => $q->where('transaction_id', $transactionId))
            ->first();

        if (!$purchase) {
            $this->redirectToIndexWithResult('error', 'خطا', 'رکورد خرید یافت نشد.');
            return;
        }

        try {
            $verify = app(PackageApiService::class)->verifyPayment($transactionId ?: $purchase->transaction_id);

            if (!($verify['paid'] ?? false)) {
                $failReason = $verify['message'] ?? $gatewayError ?? 'پرداخت تأیید نشد.';
                $purchase->markAsFailed($failReason);

                // ★ پیام بر اساس لغو/ناموفق بودن
                $title = $isCancelled ? 'پرداخت لغو شد' : 'پرداخت ناموفق بود';
                $message = $isCancelled
                    ? 'پرداخت توسط شما لغو شد و مبلغی از حساب شما کسر نشده است. در صورت نیاز می‌توانید دوباره تلاش کنید.'
                    : 'پرداخت شما تأیید نشد. در صورت کسر مبلغ، حداکثر ۷۲ ساعت بازمی‌گردد.';

                $this->redirectToIndexWithResult('failed', $title, $message, [
                    'package_name'   => $purchase->package_name ?? $purchase->package_slug,
                    'package_slug'   => $purchase->package_slug,
                    'amount'         => $purchase->amount ?? null,
                    'transaction_id' => $transactionId,
                ]);
                return;
            }

            // ---------- مسیر موفق (بدون تغییر) ----------
            $licenseKey = $verify['license_key'] ?? null;
            if (!$licenseKey) {
                throw new RuntimeException('لایسنس از API دریافت نشد.');
            }

            $purchase->markAsPaid([
                'license_key'        => $licenseKey,
                'license_expires_at' => $verify['expires_at'] ?? null,
                'gateway'            => $verify['gateway'] ?? null,
            ]);

            /*
             * فاز ۶۴ — قفل نصب همزمان: اگر نصب دیگری در جریان است، این نصب صف نمی‌شود؛
             * لایسنس روی رکورد خرید ذخیره شده و از بازار با «بررسی و نصب» (بدون پرداخت مجدد) قابل نصب است.
             */
            if ($this->hasInstallationLock()) {
                $this->redirectToIndexWithResult('success', 'پرداخت با موفقیت انجام شد',
                    'پرداخت شما تأیید شد؛ اما اکنون نصب دیگری در جریان است. پس از پایان آن، از بازار گزینه «بررسی و نصب» را بزنید (بدون پرداخت مجدد).', [
                        'package_name'   => $purchase->package_name ?? $purchase->package_slug,
                        'package_slug'   => $purchase->package_slug,
                        'amount'         => $purchase->amount ?? null,
                        'transaction_id' => $transactionId,
                    ]);

                return;
            }

            InstalledModule::markPending(
                $purchase->package_slug,
                $purchase->package_name,
                $purchase->version,
                $licenseKey
            );

            InstallPackageJob::dispatch(
                $purchase->package_slug,
                $licenseKey,
                $purchase->admin_id,
                $purchase->id,
                $verify['download_token'] ?? null
            );

            $this->redirectToIndexWithResult('success', 'پرداخت با موفقیت انجام شد',
                'مبلغ پرداخت‌شده تأیید شد و نصب پکیج آغاز گردید.', [
                    'package_name'   => $purchase->package_name ?? $purchase->package_slug,
                    'package_slug'   => $purchase->package_slug,
                    'amount'         => $purchase->amount ?? null,
                    'transaction_id' => $transactionId,
                    'license_key'    => $licenseKey,
                ]);
        } catch (Throwable $e) {
            Log::error('Payment callback failed', [
                'transaction' => $transactionId,
                'error'       => $e->getMessage(),
            ]);

            // ★ قطع ارتباط / 5xx = نامعلوم — رکورد failed نمی‌شود (شاید پرداخت واقعاً موفق بوده)
            $this->redirectToIndexWithResult('error', 'خطا در تأیید پرداخت',
                'در تأیید پرداخت مشکلی پیش آمد. اگر مبلغ کسر شده است، حداکثر تا ۷۲ ساعت آینده وضعیت به‌روز می‌شود یا با پشتیبانی تماس بگیرید.', [
                    'transaction_id' => $transactionId,
                ]);
        }
    }

    /**
     * فاز ۴۴ — تأیید پرداخت قالب (قرارداد API §5.2: بدون لایسنس، پاسخ download_token می‌دهد)
     * پس از موفقیت، نصب قالب به صف می‌رود (InstallThemeJob).
     */
    private function handleTemplateCallback(Request $request): void
    {
        $transactionId = $request->query('transaction_id')
            ?? $request->query('Authority')
            ?? $request->query('transactionId')
            ?? $request->query('tracking_code');

        $isCancelled = $request->query('cancel') === 'true' || $request->query('status') === 'failed';
        $gatewayError = $request->query('error');

        if (! $transactionId) {
            $this->redirectToIndexWithResult('failed', 'پرداخت ناموفق بود',
                $gatewayError ?: 'اطلاعات تراکنش ناقص است.');

            return;
        }

        $purchase = PackagePurchase::query()
            ->where('type', 'template')
            ->where('transaction_id', $transactionId)
            ->first();

        if (! $purchase) {
            $this->redirectToIndexWithResult('error', 'خطا', 'رکورد خرید قالب یافت نشد.');

            return;
        }

        try {
            $verify = app(PackageApiService::class)->verifyTemplatePayment($transactionId);

            if (! ($verify['paid'] ?? false)) {
                $failReason = $verify['message'] ?? $gatewayError ?? 'پرداخت تأیید نشد.';
                $purchase->markAsFailed($failReason);

                $title = $isCancelled ? 'پرداخت لغو شد' : 'پرداخت ناموفق بود';
                $message = $isCancelled
                    ? 'پرداخت توسط شما لغو شد و مبلغی از حساب شما کسر نشده است. در صورت نیاز می‌توانید دوباره تلاش کنید.'
                    : 'پرداخت شما تأیید نشد. در صورت کسر مبلغ، حداکثر ۷۲ ساعت بازمی‌گردد.';

                $this->redirectToIndexWithResult('failed', $title, $message, [
                    'package_name'   => $purchase->package_name,
                    'package_slug'   => $purchase->package_slug,
                    'amount'         => $purchase->amount,
                    'transaction_id' => $transactionId,
                ]);

                return;
            }

            // ---------- موفق ----------
            $downloadToken = $verify['download_token'] ?? null;

            $purchase->markAsPaid([
                'gateway' => $verify['gateway'] ?? null,
                'version' => $verify['version'] ?? $purchase->version,
            ]);

            // فاز ۶۴ — قفل نصب همزمان: اگر نصب دیگری در جریان است، نصب قالب صف نمی‌شود (خرید ذخیره شده)
            if ($this->hasInstallationLock()) {
                $this->redirectToIndexWithResult('success', 'پرداخت با موفقیت انجام شد',
                    'پرداخت شما تأیید شد؛ اما اکنون نصب دیگری در جریان است. پس از پایان آن، از بازار گزینه «نصب مجدد قالب» را بزنید (بدون پرداخت مجدد).', [
                        'package_name'   => $purchase->package_name,
                        'package_slug'   => $purchase->package_slug,
                        'amount'         => $purchase->amount,
                        'transaction_id' => $transactionId,
                    ]);

                return;
            }

            InstalledTheme::markPending(
                $purchase->package_slug,
                $purchase->package_name,
                $verify['version'] ?? $purchase->version,
                (string) ($purchase->meta['purchase_type'] ?? 'normal'),
                $purchase->admin_id
            );

            InstallThemeJob::dispatch(
                $purchase->package_slug,
                $downloadToken,
                $verify['signature'] ?? null,   // هش ZIP طبق قرارداد verify
                (string) ($purchase->meta['purchase_type'] ?? 'normal'),
                $purchase->admin_id,
                $purchase->id
            );

            $this->redirectToIndexWithResult('success', 'پرداخت با موفقیت انجام شد',
                'مبلغ پرداخت‌شده تأیید شد و نصب قالب آغاز گردید.', [
                    'package_name'   => $purchase->package_name,
                    'package_slug'   => $purchase->package_slug,
                    'amount'         => $purchase->amount,
                    'transaction_id' => $transactionId,
                ]);
        } catch (Throwable $e) {
            Log::error('Template payment callback failed', [
                'transaction' => $transactionId,
                'error'       => $e->getMessage(),
            ]);

            $this->redirectToIndexWithResult('error', 'خطا در تأیید پرداخت',
                'در تأیید پرداخت مشکلی پیش آمد. اگر مبلغ کسر شده است، حداکثر تا ۷۲ ساعت آینده وضعیت به‌روز می‌شود یا با پشتیبانی تماس بگیرید.', [
                    'transaction_id' => $transactionId,
                ]);
        }
    }

    /**
     * فاز ۶۴ — آیا نصب دیگری (پکیج/قالب — با پنجرهٔ تازگی ۱۰ دقیقه‌ای) در جریان است؟
     * همان قاعدهٔ PackageIndex::hasOtherInstallationInProgress — اینجا بدون استثنا برای slug.
     */
    private function hasInstallationLock(): bool
    {
        $freshAfter = now()->subMinutes(10);

        return InstalledModule::query()
                ->where('status', InstalledModule::STATUS_UPDATING)
                ->where('updated_at', '>', $freshAfter)
                ->exists()
            || InstalledTheme::query()
                ->where('status', InstalledTheme::STATUS_UPDATING)
                ->where('updated_at', '>', $freshAfter)
                ->exists();
    }

    /**
     * ریدایرکت به ایندکس همراه با دیتای نتیجه پرداخت (پورت redirectToIndexWithResult قدیمی)
     * اولویت با صفحه جدید (np)؛ اگر روت هنوز ثبت نشده باشد به پنل قدیمی برمی‌گردد.
     */
    private function redirectToIndexWithResult(string $status, string $title, string $message, array $extra = []): void
    {
        session()->flash('payment_result', array_merge([
            'status'  => $status,   // success | failed | error
            'title'   => $title,
            'message' => $message,
        ], $extra));

        $this->result = session('payment_result');

        $target = Route::has('np.packages.index')
            ? route('np.packages.index')
            : route('admin.packages.index');

        $this->redirect($target);
    }

    public function render()
    {
        // معمولاً هرگز رندر نمی‌شود (mount همیشه redirect می‌کند)
        return view('admin-panel::livewire.admin.packages.payment-callback')
            ->layout('admin-panel::layout');
    }
}
