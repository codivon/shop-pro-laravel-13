<?php

namespace App\Livewire\Admin\Packages\Concerns;

use App\Jobs\InstallThemeJob;
use App\Models\InstalledTheme;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use App\Services\ThemeInstallerService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/*
 * منطق خرید/نصب قالب‌ها در بازار — فاز ۴۴
 *
 * قرارداد API §5.2: قالب‌ها لایسنس ندارند؛ دو نوع خرید (normal/exclusive).
 * جریان: خرید → payment_url (پولی) یا download_token (خرید مجدده/رایگان)
 * → callback (type=template) → verify → InstallThemeJob.
 */
trait InteractsWithTemplates
{
    /* نکته: متد api() از تریت InteractsWithPackages روی همان کامپوننت می‌آید. */

    /**
     * آدرس callback پرداخت قالب — همان روت پکیج‌ها با type=template.
     */
    protected function templateCallbackUrl(): string
    {
        if (\Illuminate\Support\Facades\Route::has('np.packages.payment.callback')) {
            return route('np.packages.payment.callback', ['type' => 'template']);
        }

        return route(config('packages.payment.callback_route'), ['type' => 'template']);
    }

    /**
     * شروع خرید قالب — از مودال جزئیات (purchaseType: normal|exclusive).
     *  - خرید مجددهٔ normal → پاسخ همان لحظه download_token می‌دهد → نصب فوری
     *  - خرید پولی → ذخیرهٔ رکورد + ریدایرکت به درگاه
     */
    public function startTemplatePurchase(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        if (empty($this->tDetail['slug'])) {
            return;
        }

        $slug         = $this->tDetail['slug'];
        $purchaseType = $this->tDetailPurchaseType === 'exclusive' ? 'exclusive' : 'normal';

        // در حال نصب؟
        if (InstalledTheme::where('slug', $slug)->where('status', InstalledTheme::STATUS_UPDATING)->exists()) {
            $this->dispatch('toast', message: 'نصب این قالب همچنان در جریان است.', type: 'error');

            return;
        }

        // هشدار اختصاصی — تأیید صریح کاربر لازم است
        if ($purchaseType === 'exclusive' && ! $this->tExclusiveConfirmed) {
            $this->tExclusiveConfirmed = true;

            return;
        }

        try {
            $purchase = $this->api()->createTemplatePurchase(
                $slug,
                $purchaseType,
                $this->templateCallbackUrl(),
                auth('adminPanel')->id()
            );

            // خرید مجدد normal / قالب رایگان → توکن دانلود مستقیم
            if (! empty($purchase['download_token']) || ($purchase['is_free'] ?? false)) {
                $this->dispatchTemplateInstall($slug, $purchase, $purchaseType);

                $this->closeTemplateDetail();

                return;
            }

            if (empty($purchase['payment_url'])) {
                $this->dispatch('toast', message: 'درخواست خرید ثبت شد اما آدرس درگاه پرداخت دریافت نشد.', type: 'error');

                return;
            }

            // خرید پولی — رکورد خرید با type=template (برای callback)
            PackagePurchase::create([
                'type'          => 'template',
                'admin_id'      => auth('adminPanel')->id(),
                'package_slug'  => $slug,
                'package_name'  => $this->tDetail['name'] ?? $slug,
                'version'       => (string) ($this->tDetail['latest_version']['version'] ?? ''),
                'amount'        => $purchase['amount'] ?? 0,
                'gateway'       => $purchase['gateway'] ?? null,
                'transaction_id'=> $purchase['transaction_id'] ?? null,
                'payment_url'   => $purchase['payment_url'] ?? null,
                'status'        => PackagePurchase::STATUS_PENDING,
                'meta'          => array_merge($purchase, ['purchase_type' => $purchaseType]),
            ]);

            $this->closeTemplateDetail();
            $this->redirect($purchase['payment_url']);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Template purchase failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور قالب‌ها.', type: 'error');
        }
    }

    /** صف‌کردن نصب قالب با توکن دانلود (خرید رایگان/مجدد) */
    protected function dispatchTemplateInstall(string $slug, array $purchase, string $purchaseType): void
    {
        $version = $this->tDetail['latest_version']['version'] ?? $purchase['version'] ?? null;

        InstalledTheme::markPending($slug, $this->tDetail['name'] ?? $slug, $version, $purchaseType, auth('adminPanel')->id());

        InstallThemeJob::dispatch(
            $slug,
            $purchase['download_token'] ?? null,
            $purchase['signature'] ?? null,
            $purchaseType,
            auth('adminPanel')->id(),
            null
        );

        $this->dispatch('toast', message: 'نصب قالب آغاز شد — تا پایان، وضعیت «در حال نصب» نمایش داده می‌شود.', type: 'success');
    }

    /* ---------------- مودال جزئیات قالب ---------------- */

    public function openTemplateDetail(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->tDetailOpen = true;
        $this->tDetailLoading = true;
        $this->tDetailError = null;
        $this->tDetail = null;
        $this->tExclusiveConfirmed = false;
        $this->tDetailPurchaseType = 'normal';

        try {
            $data = \Illuminate\Support\Facades\Cache::remember(
                'templates.detail.'.$slug,
                now()->addMinutes(config('packages.cache.detail_ttl', 10)),
                fn () => $this->api()->getTemplate($slug)
            );

            $template = $data['data'] ?? $data;

            // وضعیت نصب محلی
            $installed = InstalledTheme::where('slug', $slug)->first();
            $template['installed'] = $installed ? [
                'name'    => $installed->name,
                'version' => $installed->version,
                'status'  => $installed->status,
                'is_active' => $installed->isActive(),
            ] : null;

            $latestVersion = $template['latest_version']['version']
                ?? collect($template['active_versions'] ?? [])->first()['version']
                ?? null;

            $template['latestVersion'] = $latestVersion;
            $template['has_update'] = $installed && $latestVersion
                && version_compare((string) $latestVersion, (string) $installed->version, '>');

            $this->tDetail = $template;
        } catch (RuntimeException $e) {
            $this->tDetailError = $e->getMessage();
        } catch (Throwable $e) {
            report($e);
            $this->tDetailError = 'خطا در دریافت اطلاعات قالب.';
        } finally {
            $this->tDetailLoading = false;
        }
    }

    public function closeTemplateDetail(): void
    {
        $this->tDetailOpen = false;
        $this->tExclusiveConfirmed = false;
    }

    public function setTemplatePurchaseType(string $type): void
    {
        $this->tDetailPurchaseType = $type === 'exclusive' ? 'exclusive' : 'normal';
        $this->tExclusiveConfirmed = false;
    }

    /* ---------------- فعال‌سازی / حذف / نصب مجدد ---------------- */

    /** فعال‌سازی قالب نصب‌شده از کارت/مودال بازار */
    public function activateTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('themes.update') ?: abort(403);

        $installed = InstalledTheme::where('slug', $slug)->first();

        if (! $installed) {
            $this->dispatch('toast', message: 'این قالب هنوز نصب نشده است.', type: 'error');

            return;
        }

        try {
            app(ThemeInstallerService::class)->activate($installed->name);

            $this->dispatch('toast', message: 'قالب «'.$installed->name.'» به عنوان قالب فعال تنظیم شد.', type: 'success');
            $this->loadTemplates();
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /** نصب مجدد قالب خریداری‌شده (خرید مجددهٔ normal بدون پرداخت انجام می‌شود) */
    public function reinstallTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        if (InstalledTheme::where('slug', $slug)->where('status', InstalledTheme::STATUS_UPDATING)->exists()) {
            $this->dispatch('toast', message: 'نصب این قالب همچنان در جریان است.', type: 'error');

            return;
        }

        try {
            // برای خریدِ مجدده، endpoint خرید مستقیم download_token برمی‌گرداند
            $purchase = $this->api()->createTemplatePurchase(
                $slug,
                'normal',
                $this->templateCallbackUrl(),
                auth('adminPanel')->id()
            );

            if (empty($purchase['download_token'])) {
                $this->dispatch('toast', message: $purchase['error'] ?? 'دریافت توکن دانلود ناموفق بود؛ این قالب ممکن است اختصاصی خریداری شده باشد.', type: 'error');

                return;
            }

            $this->tDetail = ['slug' => $slug, 'name' => InstalledTheme::where('slug', $slug)->value('name') ?: $slug, 'latest_version' => null];
            $this->dispatchTemplateInstall($slug, $purchase, 'normal');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Template reinstall failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور قالب‌ها.', type: 'error');
        }
    }

    /** حذف قالب (غیرفعال) از بازار */
    public function uninstallTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('themes.delete') ?: abort(403);

        try {
            app(ThemeInstallerService::class)->uninstall($slug);

            $this->dispatch('toast', message: 'قالب با موفقیت حذف شد.', type: 'success');
            $this->loadTemplates();
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }
}
