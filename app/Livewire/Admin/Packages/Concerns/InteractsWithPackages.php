<?php

namespace App\Livewire\Admin\Packages\Concerns;

use App\Jobs\InstallPackageJob;
use App\Models\InstalledModule;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Throwable;

/**
 * منطق مشترک نصب پکیج‌ها — پورت ۱:۱ متدهای Back\PackageController
 * (startInstall / dispatchInstall / installWithSubscription /
 *  reinstallWithExistingLicense / licenseHasTime)
 *
 * اتصالات خارجی عیناً صدا زده می‌شوند (PackageApiService)؛ همه‌ی مسیرها
 * try/catch دارند تا نبودِ شبکه پنل را نکشد (طبق spec فاز ۷-ج۴).
 */
trait InteractsWithPackages
{
    protected function api(): PackageApiService
    {
        return app(PackageApiService::class);
    }

    /**
     * آدرس callback پرداخت — در پنل جدید به کامپوننت np اشاره می‌کند،
     * اگر روت هنوز ثبت نشده باشد همان روت قدیمی پنل استفاده می‌شود.
     */
    protected function paymentCallbackUrl(): string
    {
        if (Route::has('np.packages.payment.callback')) {
            return route('np.packages.payment.callback');
        }

        return route(config('packages.payment.callback_route'));
    }

    /**
     * شروع فرآیند نصب (پورت startInstall قدیمی)
     * - پکیج رایگان: مستقیم dispatchInstall
     * - اشتراک فعال: installWithSubscription
     * - لایسنس قبلی: reinstallWithExistingLicense
     * - پکیج پولی: ایجاد purchase → ریدایرکت به درگاه
     */
    public function startInstall(string $slug, ?int $pricingPlanId = null, bool $useSubscription = false, bool $useLicense = false): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        try {
            $package = $this->api()->getPackage($slug);
            $package = $package['data'] ?? $package;
            $version = $package['active_versions'] ? $package['active_versions'][0]['version'] : null;

            $selectedPlan = collect($package['active_pricing_plans'] ?? [])
                ->firstWhere('id', $pricingPlanId);

            $isFree = false;
            if ($selectedPlan) {
                $isFree = ($selectedPlan['price'] == 0);
            } else {
                // اگر پلن پیدا نشد، از مقدار is_free پکیج استفاده کن (پورت قدیمی)
                $isFree = (bool) ($package['is_free'] ?? false);
            }

            // ★ نصب با اشتراک فعال (بدون پرداخت)
            if ($useSubscription) {
                $this->installWithSubscription($slug);
                return;
            }

            // اگر قبلاً نصب شده، جلوگیری شود
            if (InstalledModule::where('slug', $slug)->exists()) {
                $this->dispatch('toast', message: 'این پکیج قبلاً نصب شده است.', type: 'error');
                return;
            }

            if ($isFree) {
                // پکیج رایگان: مستقیم به job
                $this->dispatchInstall($slug, $version, $pricingPlanId);
                return;
            }

            // ★ نصب مجدد با لایسنس قبلی (خریداری‌شده — بدون پرداخت)
            if ($useLicense) {
                $this->reinstallWithExistingLicense($slug);
                return;
            }

            // پکیج پولی: ایجاد درخواست خرید با pricing_plan_id
            $callbackUrl = $this->paymentCallbackUrl();

            $purchase = $this->api()->createPurchase(
                $slug,
                $callbackUrl,
                auth('adminPanel')->id(),
                $pricingPlanId
            );

            // ذخیره رکورد خرید (پورت قدیمی)
            PackagePurchase::create([
                'admin_id'       => auth('adminPanel')->id(),
                'package_slug'   => $slug,
                'package_name'   => $package['name'] ?? $slug,
                'version'        => $version,
                'amount'         => $purchase['amount'] ?? ($package['price'] ?? 0),
                'gateway'        => $purchase['gateway'] ?? null,
                'transaction_id' => $purchase['transaction_id'] ?? null,
                'payment_url'    => $purchase['payment_url'] ?? null,
                'status'         => PackagePurchase::STATUS_PENDING,
                'meta'           => $purchase,
            ]);

            if (!empty($purchase['payment_url'])) {
                $this->redirect($purchase['payment_url']);
                return;
            }

            $this->dispatch('toast', message: 'درخواست خرید ثبت شد اما آدرس درگاه پرداخت دریافت نشد.', type: 'error');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Package install failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور.', type: 'error');
        }
    }

    /**
     * نصب پکیج رایگان (پورت dispatchInstall قدیمی)
     */
    protected function dispatchInstall(string $slug, ?string $version, ?int $pricingPlanId): void
    {
        try {
            // برای پکیج رایگان، ابتدا یک درخواست "purchase" آزاد به API می‌زنیم
            $callbackUrl = $this->paymentCallbackUrl();
            $purchase = $this->api()->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
            $licenseKey = $purchase['license_key'] ?? null;

            if (!$licenseKey) {
                $callbackUrl = $this->paymentCallbackUrl();
                $purchase = $this->api()->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
                $licenseKey = $purchase['license_key'] ?? null;
            }

            InstallPackageJob::dispatch(
                $slug,
                $licenseKey,
                auth('adminPanel')->id(),
                null, // purchaseId در callback پر می‌شود
                null  // downloadToken از داخل job گرفته می‌شود
            );

            $this->dispatch('toast', message: 'پکیج در صف نصب قرار گرفت.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * نصب پکیج با اشتراک فعال (پورت installWithSubscription قدیمی)
     */
    protected function installWithSubscription(string $slug): void
    {
        try {
            $package = $this->api()->getPackage($slug);
            $package = $package['data'] ?? $package;
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: 'بررسی اشتراک ناموفق بود: ' . $e->getMessage(), type: 'error');
            return;
        }

        $subscription = $package['subscription'] ?? null;
        $eligible = false;

        if ($subscription && ($subscription['is_free_with_subscription'] ?? false)) {
            $expiresAt = $subscription['subscription_expires_at'] ?? null;
            if (empty($expiresAt)) {
                $eligible = true; // اشتراک نامحدود
            } else {
                try { $eligible = \Carbon\Carbon::parse($expiresAt)->isFuture(); }
                catch (\Throwable $e) { $eligible = false; }
            }
        }

        if (!$eligible) {
            $this->dispatch('toast', message: 'اشتراک فعال شما این پکیج را پوشش نمی‌دهد یا منقضی شده است. برای نصب، پلن را انتخاب کنید.', type: 'error');
            return;
        }

        $licenseKey = $subscription['license_key'] ?? null;
        $downloadToken = null;

        // ★ لایسنس صادرنشده (فقط اشتراک) → درخواست purchase از مسیر اشتراکِ API
        if (empty($licenseKey)) {
            try {
                $callbackUrl = $this->paymentCallbackUrl();
                $purchase = $this->api()->createPurchase(
                    $slug,
                    $callbackUrl,
                    auth('adminPanel')->id(),
                    null   // pricing_plan_id لازم نیست — مسیر اشتراک قبل از چک پلن اجرا می‌شود
                );
            } catch (RuntimeException $e) {
                $this->dispatch('toast', message: 'صدور لایسنس از اشتراک ناموفق بود: ' . $e->getMessage(), type: 'error');
                return;
            }

            // اطمینان: پاسخ واقعاً از مسیر اشتراک باشد، نه شروع یک خرید پولی
            if (empty($purchase['via_subscription']) || empty($purchase['license_key'])) {
                $this->dispatch('toast', message: 'دسترسی به این پکیج از طریق اشتراک ممکن نشد. لطفاً پلن موردنظر را خریداری کنید.', type: 'error');
                return;
            }

            $licenseKey = $purchase['license_key'];
            $downloadToken = $purchase['download_token'] ?? null;
        }

        $version = $package['latest_version']['version']
            ?? collect($package['active_versions'] ?? [])->first()['version']
            ?? null;

        InstalledModule::markPending($slug, $package['name'] ?? null, $version, $licenseKey);

        InstallPackageJob::dispatch(
            $slug,
            $licenseKey,
            auth('adminPanel')->id(),
            null,
            $downloadToken   // ★ اگر token گرفتیم، install() مرحله verifyLicense را skip می‌کند
        );

        Log::info('Package install via subscription', [
            'slug'        => $slug,
            'had_license' => !empty($subscription['license_key']),
        ]);

        $this->dispatch('toast', message: 'پکیج در صف نصب قرار گرفت.', type: 'success');
    }

    /**
     * نصب مجدد با لایسنس قبلی (پورت reinstallWithExistingLicense قدیمی)
     */
    protected function reinstallWithExistingLicense(string $slug): void
    {
        $purchase = PackagePurchase::query()
            ->where('package_slug', $slug)
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        if (!$purchase) {
            $this->dispatch('toast', message: 'خرید معتبری برای این پکیج یافت نشد.', type: 'error');
            return;
        }

        try {
            $verify = $this->api()->verifyLicense($slug, $purchase->license_key);
        } catch (Throwable $e) {
            $this->dispatch('toast', message: 'بررسی لایسنس ناموفق بود: ' . $e->getMessage(), type: 'error');
            return;
        }

        if (!($verify['valid'] ?? false)) {
            $this->dispatch('toast', message: $verify['message'] ?? 'لایسنس شما منقضی شده است. برای ادامه، یکی از پلن‌ها را انتخاب کنید.', type: 'error');
            return;
        }

        InstallPackageJob::dispatch(
            $slug,
            $purchase->license_key,
            $purchase->admin_id,
            $purchase->id,
            $verify['download_token'] ?? null
        );

        Log::info('Package reinstall with existing license', [
            'slug'        => $slug,
            'purchase_id' => $purchase->id,
        ]);

        $this->dispatch('toast', message: 'پکیج در صف نصب قرار گرفت.', type: 'success');
    }

    /**
     * آیا لایسنس هنوز زمان (اعتبار) دارد؟ (پورت licenseHasTime قدیمی)
     * خالی = لایسنس مادام‌العمر (duration_months=0) → دارای اعتبار
     */
    protected function licenseHasTime($apiExpiresAt = null, $dbExpiresAt = null): bool
    {
        $date = $apiExpiresAt ?: $dbExpiresAt;

        if (empty($date)) {
            return true; // نامحدود
        }

        try {
            return \Carbon\Carbon::parse($date)->isFuture();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
