<?php

namespace App\Livewire\Admin\Packages;

use App\Jobs\UpdatePackageJob;
use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Models\PackagePurchase;
use App\Services\PackageInstallerService;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * جزئیات پکیج — پورت back/packages/show.blade.php + show.js قدیمی:
 *  - کارت سربرگ با تصویر/توضیح کوتاه (رندر HTML مثل قدیمی)
 *  - توضیحات کامل (long_description) + تاریخچه نسخه‌ها (changelog)
 *  - لاگ‌های اخیر با نشان وضعیت
 *  - پنل عملیات: وضعیت نصب (۴ حالت) + نصب/خرید با مودال تأیید + آپدیت +
 *    فعال/غیرفعال + حذف ماژول (پورت toggleActivation/uninstall قدیمی)
 *  - polling وضعیت هنگام نصب/آپدیت (پورت startPolling/pollStatus قدیمی)
 */
#[Title('جزئیات پکیج')]
class PackageShow extends Component
{
    use InteractsWithPackages;

    public string $slug;

    /** دیتای پکیج از API (کش detail مثل قدیمی) */
    public ?array $package = null;

    public array $logs = [];

    public ?string $apiError = null;

    /* ---------------- مودال تأیید نصب (پورت install-confirm-modal قدیمی) ---------------- */

    public bool $confirmingInstall = false;

    public ?int $installPlanId = null;

    public bool $installUseSubscription = false;

    public bool $installUseLicense = false;

    public bool $installIsFree = true;

    public int $installPrice = 0;

    public ?string $installPlanTitle = null;

    /* ---------------- مودال تأیید حذف ---------------- */

    public bool $confirmingUninstall = false;

    public function mount(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->slug = $slug;
        $this->loadPackage();
    }

    /**
     * جزئیات پکیج — پورت ۱:۱ show قدیمی (کش + enrich نصب محلی + لاگ‌ها)
     */
    public function loadPackage(): void
    {
        $cacheKey = 'packages.detail.' . $this->slug;
        $this->apiError = null;

        try {
            $data = \Illuminate\Support\Facades\Cache::remember(
                $cacheKey,
                now()->addMinutes(config('packages.cache.detail_ttl')),
                function () {
                    return $this->api()->getPackage($this->slug);
                }
            );

            $package = $data['data'] ?? $data;

            // ★ اشتراک — اصلاح اعتبار و روزها (به دلیل کش شدن detail)
            if (!empty($package['subscription']['subscription_expires_at'])) {
                $exp = \Carbon\Carbon::parse($package['subscription']['subscription_expires_at']);
                $package['subscription']['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
                if ($exp->isPast()) {
                    $package['subscription']['is_free_with_subscription'] = false;
                }
            }

            $package['purchased'] = PackagePurchase::where('package_slug', $this->slug)
                ->where('status', PackagePurchase::STATUS_PAID)
                ->whereNotNull('license_key')
                ->exists();

            // enrich: اطلاعات نصب محلی
            $installed = InstalledModule::where('slug', $this->slug)->first();
            $activeVersions = $package['active_versions'] ?? [];

            if (!empty($activeVersions)) {
                // مرتب‌سازی بر اساس نسخه به صورت نزولی
                usort($activeVersions, function ($a, $b) {
                    return version_compare($b['version'], $a['version']);
                });

                $latestVersion = $activeVersions[0]['version'];
            } else {
                $latestVersion = $package['version'] ?? '';
            }

            $hasUpdate = $installed && version_compare($latestVersion, $installed->version, '>');

            $package['installed'] = $installed ? [
                'version'               => $installed->version,
                'status'                => $installed->status,
                'is_active'             => $installed->is_active,
                'is_expired'            => $installed->isExpired(),
                'license_expires_at'    => $installed->license_expires_at?->toDateTimeString(),
                'license_expires_human' => $installed->license_expires_at?->diffForHumans(),
                'last_error'            => $installed->last_error,
            ] : null;

            $package['has_update'] = $hasUpdate;
            $package['latestVersion'] = $latestVersion;

            $this->package = $package;

            // enrich: لاگ‌های اخیر (پورت دقیق)
            $this->logs = \App\Models\ModuleInstallLog::where('module_slug', $this->slug)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($log) => [
                    'id'           => $log->id,
                    'action'       => $log->action,
                    'from_version' => $log->from_version,
                    'to_version'   => $log->to_version,
                    'status'       => $log->status,
                    'message'      => $log->message,
                    'created_at'   => $log->created_at->diffForHumans(),
                ])
                ->all();
        } catch (RuntimeException $e) {
            $this->package = null;
            $this->apiError = $e->getMessage();
        }
    }

    /* ===================================================================
     *  نصب / خرید (پورت btn-install + install-confirm-modal قدیمی)
     * =================================================================== */

    /**
     * باز کردن مودال تأیید نصب (پورت کلیک btn-install در show.js)
     */
    public function openInstall(?int $planId = null, bool $useSubscription = false, bool $useLicense = false): void
    {
        $this->installPlanId = $planId;
        $this->installUseSubscription = $useSubscription;
        $this->installUseLicense = $useLicense;

        $plan = collect($this->package['active_pricing_plans'] ?? $this->package['pricing_plans'] ?? $this->package['plans'] ?? [])
            ->firstWhere('id', $planId);

        $price = $plan
            ? (int) ($plan['discount_price'] ?? $plan['price'] ?? 0)
            : (int) ($this->package['min_price'] ?? $this->package['price'] ?? 0);

        // اشتراک/لایسنس قبلی = بدون پرداخت
        if ($useSubscription || $useLicense) {
            $this->installIsFree = true;
            $this->installPrice = 0;
        } else {
            $this->installIsFree = $price === 0 || (bool) ($this->package['is_free'] ?? false);
            $this->installPrice = $price;
        }

        $this->installPlanTitle = $plan['title'] ?? $plan['name'] ?? null;
        $this->confirmingInstall = true;
    }

    public function cancelInstall(): void
    {
        $this->confirmingInstall = false;
    }

    /**
     * تأیید و شروع نصب (پورت confirm-install-btn در show.js)
     */
    public function confirmInstall(): void
    {
        $this->confirmingInstall = false;

        $this->install($this->installPlanId, $this->installUseSubscription, $this->installUseLicense);
    }

    /** نصب/خرید از صفحه جزئیات (همان startInstall مشترک) */
    public function install(?int $pricingPlanId = null, bool $useSubscription = false, bool $useLicense = false): void
    {
        $this->startInstall($this->slug, $pricingPlanId, $useSubscription, $useLicense);
        $this->loadPackage();
    }

    /* ===================================================================
     *  آپدیت / فعال‌سازی / حذف (پورت btn-update / btn-toggle / btn-uninstall)
     * =================================================================== */

    public function updateModule(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $installed = InstalledModule::where('slug', $this->slug)->first();

        if (!$installed) {
            $this->dispatch('toast', message: 'ماژول نصب نشده است.', type: 'error');
            return;
        }

        if ($installed->status === InstalledModule::STATUS_UPDATING) {
            $this->dispatch('toast', message: 'آپدیت قبلی همچنان در حال اجراست.', type: 'error');
            return;
        }

        if ($installed->isExpired()) {
            $this->dispatch('toast', message: 'لایسنس ماژول منقضی شده است. ابتدا آن را تمدید کنید.', type: 'error');
            return;
        }

        UpdatePackageJob::dispatch($this->slug, auth('adminPanel')->id());

        $this->dispatch('toast', message: 'آپدیت در صف پردازش قرار گرفت.', type: 'success');
        $this->loadPackage();
    }

    public function toggleActivation(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        try {
            $installed = app(PackageInstallerService::class)->toggleActivation($this->slug);

            $this->dispatch(
                'toast',
                message: $installed->is_active ? 'ماژول فعال شد.' : 'ماژول غیرفعال شد.',
                type: 'success'
            );

            $this->loadPackage();
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function confirmUninstall(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->confirmingUninstall = true;
    }

    public function cancelUninstall(): void
    {
        $this->confirmingUninstall = false;
    }

    public function uninstall(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->confirmingUninstall = false;

        try {
            app(PackageInstallerService::class)->uninstall($this->slug, auth('adminPanel')->id());

            // پورت قدیمی: پس از حذف، به لیست ماژول‌های نصب‌شده برمی‌گشت
            session()->flash('np-toast', ['type' => 'success', 'message' => 'ماژول با موفقیت حذف شد.']);
            $this->redirect(route('np.packages.installed', absolute: false), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'حذف ناموفق بود: ' . $e->getMessage(), type: 'error');
        }
    }

    /* ===================================================================
     *  polling وضعیت نصب/آپدیت (پورت pollStatus قدیمی — هر ۳ ثانیه)
     * =================================================================== */

    public function refreshStatus(): void
    {
        $prevStatus = $this->package['installed']['status'] ?? null;

        $this->loadPackage();

        $nowStatus = $this->package['installed']['status'] ?? null;

        if ($prevStatus === InstalledModule::STATUS_UPDATING && $nowStatus !== InstalledModule::STATUS_UPDATING) {
            if ($nowStatus === 'installed') {
                $this->dispatch('toast', message: 'نصب با موفقیت انجام شد — نسخه ' . ($this->package['installed']['version'] ?? ''), type: 'success');
            } elseif ($nowStatus === InstalledModule::STATUS_FAILED) {
                $this->dispatch('toast', message: 'نصب/آپدیت ناموفق بود: ' . ($this->package['installed']['last_error'] ?? 'خطای نامشخص'), type: 'error');
            }
        }
    }

    /* ---------------- ابزار ویو ---------------- */

    /** برچسب فارسی عملیات لاگ (پورت channel-pill های show قدیمی) */
    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'install'    => 'نصب',
            'update'     => 'آپدیت',
            'uninstall'  => 'حذف',
            'activate'   => 'فعال‌سازی',
            'deactivate' => 'غیرفعال‌سازی',
            default      => $action,
        };
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.packages.show')
            ->layout('admin-panel::layout');
    }
}
