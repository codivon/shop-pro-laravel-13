<?php

namespace App\Services;

use App\Models\InstalledModule;
use App\Models\PackageCache;
use App\Models\PackagePurchase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * بازیابی ماژول‌های نصب‌شده از پوشهٔ Modules/ — فاز ۱۲۲ (گزارش کاربر)
 * --------------------------------------------------------------------------
 * سناریو: اشتراک منقضی شد و پس از تمدید، جدول installed_modules خالی بود
 * (ریست/بازیابی دیتابیس روی هاست) ولی پوشه‌های Modules/ سر جایشان بودند —
 * در نتیجه هیچ ماژولی «نصب‌شده» شناخته نمی‌شد:
 *   • منوی «پکیج‌ها» در سایدبار غایب بود (ماژول‌های nwidart بدون رکورد
 *    installed_modules توسط LicenseGuard خودشان «نصب‌نشده» شمرده می‌شوند)
 *   • صفحهٔ «ماژول‌های نصب‌شده» خالی بود
 *   • بازار همهٔ پکیج‌ها را «نصب‌نشده» نشان می‌داد
 *
 * این سرویس تطبیق (reconcile) دوطرفه انجام می‌دهد:
 *   ۱) اسکن Modules/ و خواندن module.json هر ماژول (name/version/title)
 *   ۲) ساخت رکورد گمشدهٔ installed_modules با:
 *        - نسخه از module.json (منبع حقیقتِ روی دیسک)
 *        - بهترین تلاش برای slug بازار: خرید پرداخت‌شده → کش بازار → kebab
 *        - بازیابی لایسنس از آخرین خرید پرداخت‌شده + راستی‌آزمایی آنلاین (fail-safe)
 *   ۳) بازسازی modules_statuses.json + پاک‌سازی کش‌ها تا منوها بلافاصله برگردند
 *
 * عملیات فقط «افزودنی» است: رکوردهای موجود هرگز بازنویسی/حذف نمی‌شوند؛
 * پوشه‌های ماژول دست‌نخورده می‌مانند و هیچ دانلود/مایگریشنی اجرا نمی‌شود.
 */
class ModuleRecoveryService
{
    /**
     * فهرست ماژول‌های روی دیسک + وضعیت رکورد دیتابیس (برای پیش‌نمایش)
     *
     * @return array<int, array{name: string, title: ?string, version: ?string, slug_hint: ?string, has_row: bool, license_state: string}>
     */
    public function discover(): array
    {
        $rowsBySlug = InstalledModule::query()->pluck('name', 'slug');
        $rowsByName = InstalledModule::query()->pluck('slug', 'name');

        $result = [];

        foreach ($this->scanModuleFolders() as $module) {
            $name = $module['name'];

            $hasRow = $rowsByName->has($name)
                || $rowsBySlug->contains(fn ($rowName) => strcasecmp((string) $rowName, $name) === 0);

            $slugHint = $this->resolveMarketSlug($name, $module['title']);
            $license = $hasRow ? null : $this->resolveLicense($slugHint, $name);

            $result[] = [
                'name' => $name,
                'title' => $module['title'],
                'version' => $module['version'],
                'slug_hint' => $slugHint,
                'has_row' => $hasRow,
                'license_state' => $license['state'] ?? '—',
            ];
        }

        return $result;
    }

    /**
     * بازیابی: برای هر ماژولِ روی دیسک بدون رکورد، رکورد installed_modules می‌سازد.
     *
     * @param  int|null  $adminId  برای لاگ
     * @param  bool  $dryRun  فقط گزارش — هیچ تغییری اعمال نمی‌شود
     * @return array{restored: array<int, array>, skipped: array<int, string>, statuses_rebuilt: bool, caches_cleared: bool, dry_run: bool}
     */
    public function recover(?int $adminId = null, bool $dryRun = false): array
    {
        $report = [
            'restored' => [],
            'skipped' => [],
            'statuses_rebuilt' => false,
            'caches_cleared' => false,
            'dry_run' => $dryRun,
        ];

        foreach ($this->scanModuleFolders() as $module) {
            $name = $module['name'];

            // رکورد موجود (با نام کانونی یا slug) → دست نمی‌زنیم
            $existing = InstalledModule::query()
                ->where('name', $name)
                ->orWhere('slug', Str::kebab($name))
                ->first();

            if (! $existing) {
                // تطبیق مقاوم‌تر: slug ثبت‌شده‌ای که نام کانونی دیگری دارد
                $existing = InstalledModule::query()->get()
                    ->first(fn (InstalledModule $row) => strcasecmp(trim($row->name), $name) === 0);
            }

            if ($existing) {
                $report['skipped'][] = $name;

                continue;
            }

            $slug = $this->resolveMarketSlug($name, $module['title']);
            $license = $this->resolveLicense($slug, $name);

            $payload = [
                'name' => $name,
                'version' => $module['version'] ?: '0.0.0',
                'license_key' => $license['license_key'],
                'license_expires_at' => $license['expires_at'],
                'integrity_hash' => md5((string) config('packages.api.token', '').(string) config('packages.api.project_key', '')),
                'last_verified_at' => now(),
                'installed_at' => now(),
                'is_active' => true,
                'status' => InstalledModule::STATUS_INSTALLED,
                'last_error' => $license['state'] === 'active'
                    ? null
                    : ($license['message'] ?? null),
            ];

            if ($dryRun) {
                $report['restored'][] = [
                    'slug' => $slug,
                    'name' => $name,
                    'title' => $module['title'],
                    'version' => $payload['version'],
                    'license_state' => $license['state'],
                ];

                continue;
            }

            try {
                $row = InstalledModule::query()->create($payload + ['slug' => $slug]);

                Log::info('ModuleRecovery: رکورد ماژول از دیسک بازسازی شد', [
                    'slug' => $row->slug,
                    'name' => $row->name,
                    'version' => $row->version,
                    'license_state' => $license['state'],
                    'admin_id' => $adminId,
                ]);

                $report['restored'][] = [
                    'slug' => $row->slug,
                    'name' => $row->name,
                    'title' => $module['title'],
                    'version' => $row->version,
                    'license_state' => $license['state'],
                ];
            } catch (Throwable $e) {
                // slug تکراری یا خطای دیتابیس — با نام فنی دوباره تلاش نمی‌کنیم؛ فقط گزارش
                Log::error('ModuleRecovery: ساخت رکورد ناموفق', [
                    'slug' => $slug,
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);

                $report['skipped'][] = $name.' (خطا: '.Str::limit($e->getMessage(), 80).')';
            }
        }

        if ($dryRun) {
            return $report;
        }

        // فایل وضعیت ماژول‌ها — فقط اگر چیزی بازسازی شد
        if ($report['restored'] !== []) {
            try {
                app(PackageInstallerService::class)->rebuildModulesStatusesFile();
                $report['statuses_rebuilt'] = true;
            } catch (Throwable $e) {
                Log::warning('ModuleRecovery: بازسازی modules_statuses.json ناموفق', ['error' => $e->getMessage()]);
            }

            $this->refreshCaches();
            $report['caches_cleared'] = true;
        }

        return $report;
    }

    /* ===================================================================
     *  اسکن دیسک
     * =================================================================== */

    /**
     * همهٔ پوشه‌های ماژول معتبر (دارای module.json) در مسیر Modules/
     *
     * @return array<int, array{name: string, title: ?string, version: ?string}>
     */
    protected function scanModuleFolders(): array
    {
        $basePath = (string) config('packages.modules.path');
        $modules = [];

        if (! is_dir($basePath)) {
            return $modules;
        }

        foreach (File::directories($basePath) as $dir) {
            $name = basename($dir);

            // پوشه‌های سیستمی (.backups، .tmp-*)
            if (str_starts_with($name, '.')) {
                continue;
            }

            $manifestPath = $dir.'/module.json';

            if (! is_file($manifestPath)) {
                continue; // بدون module.json ماژول معتبر nwidart نیست
            }

            $manifest = json_decode((string) @file_get_contents($manifestPath), true);

            if (! is_array($manifest)) {
                continue;
            }

            $title = $manifest['title'] ?? null;

            $modules[] = [
                'name' => $name,
                'title' => is_string($title) && trim($title) !== '' ? trim($title) : null,
                'version' => isset($manifest['version']) ? (string) $manifest['version'] : null,
            ];
        }

        return $modules;
    }

    /* ===================================================================
     *  نگاشت نام ماژول → slug بازار
     * =================================================================== */

    /**
     * بهترین تلاش برای یافتن slug بازارِ ماژول:
     *   ۱) خرید پرداخت‌شده‌ای که نامش = نام کانونی/عنوان فارسی ماژول است
     *   ۲) کش بازار با نام/عنوان برابر (آخرین sync از API)
     *   ۳) kebab-case نام کانونی (قرارداد رایج بازار)
     */
    protected function resolveMarketSlug(string $moduleName, ?string $title): string
    {
        $candidates = array_values(array_unique(array_filter([$moduleName, (string) $title])));

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $purchase = PackagePurchase::query()
                ->where('status', PackagePurchase::STATUS_PAID)
                ->where('package_name', $candidate)
                ->orderByDesc('paid_at')
                ->first();

            if ($purchase) {
                return $purchase->package_slug;
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $cache = PackageCache::query()->where('name', $candidate)->first();

            if ($cache) {
                return $cache->slug;
            }
        }

        return Str::kebab($moduleName);
    }

    /* ===================================================================
     *  بازیابی لایسنس
     * =================================================================== */

    /**
     * لایسنس از آخرین خرید پرداخت‌شده + راستی‌آزمایی آنلاین (fail-safe).
     *
     * @return array{license_key: ?string, expires_at: ?string, state: string, message: ?string}
     *         state: active | expired | no_purchase | unknown
     */
    protected function resolveLicense(string $slug, string $moduleName): array
    {
        $purchase = PackagePurchase::query()
            ->where(function ($q) use ($slug, $moduleName) {
                $q->where('package_slug', $slug)->orWhere('package_name', $moduleName);
            })
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        if (! $purchase) {
            return [
                'license_key' => null,
                'expires_at' => null,
                'state' => 'no_purchase',
                'message' => 'لایسنس محلی یافت نشد — برای فعال‌سازی کامل، از بازار «بررسی و نصب» را بزنید یا اشتراک را تمدید کنید.',
            ];
        }

        try {
            $verify = app(PackageApiService::class)->verifyLicense($slug, $purchase->license_key);
        } catch (Throwable $e) {
            // API در دسترس نیست — کلید ذخیره می‌شود؛ LicenseGuard ماژول خودش دوباره چک می‌کند
            return [
                'license_key' => $purchase->license_key,
                'expires_at' => $purchase->license_expires_at?->toDateTimeString(),
                'state' => 'unknown',
                'message' => 'بررسی آنلاین لایسنس ناموفق بود ('.Str::limit($e->getMessage(), 80).').',
            ];
        }

        $valid = (bool) ($verify['valid'] ?? false);
        $expires = $verify['expires_at'] ?? $purchase->license_expires_at?->toDateTimeString();

        return [
            'license_key' => $purchase->license_key,
            'expires_at' => $expires,
            'state' => $valid ? 'active' : 'expired',
            'message' => $valid ? null : ($verify['message'] ?? 'لایسنس منقضی یا نامعتبر است — از بازار تمدید کنید.'),
        ];
    }

    /* ===================================================================
     *  پاک‌سازی کش‌ها (نسخهٔ سبک refreshCaches نصب‌کننده)
     * =================================================================== */

    protected function refreshCaches(): void
    {
        try {
            foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $command) {
                try {
                    \Illuminate\Support\Facades\Artisan::call($command);
                } catch (Throwable) {
                    // کش در دسترس نبود — ادامه
                }
            }

            foreach ([
                'modules.php', 'services.php', 'packages.php',
                'routes-v7.php', 'routes.php', 'config.php', 'events.php', 'compiled.php',
            ] as $file) {
                $path = base_path('bootstrap/cache/'.$file);

                if (is_file($path)) {
                    @unlink($path);
                }
            }
        } catch (Throwable $e) {
            Log::warning('ModuleRecovery: پاک‌سازی کش‌ها ناقص ماند', ['error' => $e->getMessage()]);
        }
    }
}
