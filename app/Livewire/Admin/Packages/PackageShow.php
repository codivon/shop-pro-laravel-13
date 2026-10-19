<?php

namespace App\Livewire\Admin\Packages;

use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Models\ModuleInstallLog;
use App\Models\PackagePurchase;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('جزئیات پکیج')]
class PackageShow extends Component
{
    use InteractsWithPackages;

    public string $slug;

    /** دیتای پکیج از API (کش detail مثل قدیمی) */
    public ?array $package = null;

    public array $logs = [];

    public ?string $apiError = null;

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
            $this->logs = ModuleInstallLog::where('module_slug', $this->slug)
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

    /** نصب/خرید از صفحه جزئیات (همان startInstall مشترک) */
    public function install(?int $pricingPlanId = null, bool $useSubscription = false, bool $useLicense = false): void
    {
        $this->startInstall($this->slug, $pricingPlanId, $useSubscription, $useLicense);
        $this->loadPackage();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.packages.show')
            ->layout('admin-panel::layout');
    }
}
