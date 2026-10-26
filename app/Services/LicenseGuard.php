<?php

namespace Modules\CustomerClub\Services;

use App\Models\InstalledModule;
use App\Services\PackageApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\CustomerClub\Models\ClubSetting;

/**
 * گارد لایسنس ماژول باشگاه مشتریان — الگوی PayYar/MarketingMessaging (سرخودکفا).
 *
 * CLI همیشه true (تست‌ها و کامندها بدون قطع همکار می‌کنند)؛ کلید
 * license_skip برای حالت توسعه؛ در وب: رکورد InstalledModule + چک
 * دوره‌ای ۲۴ ساعته با API مرکزی و ریکاوری ۱۵ دقیقه‌ای.
 */
class LicenseGuard
{
    private const CHECK_INTERVAL_HOURS   = 24;
    private const RECOVERY_RETRY_MINUTES = 15;
    private const FAIL_RETRY_HOURS       = 6;

    public static function check(string $slug = 'customer-club'): bool
    {
        // در محیط CLI (artisan) چک نکن که کامندهای نصب/تست ارور ندهند
        if (app()->runningInConsole()) {
            return true;
        }

        try {
            $licenseSkip = ClubSetting::get('license_skip', (string) config('customer-club.license_skip', '0')) === '1';
        } catch (\Throwable $e) {
            $licenseSkip = (string) config('customer-club.license_skip', '0') === '1';
        }

        if ($licenseSkip) {
            return true;
        }

        $module = self::resolveModule($slug);

        if (!$module) {
            return false; // نصب نشده
        }

        $cacheKey  = "module.{$slug}.license_checked_at";
        $lastCheck = Cache::get($cacheKey);

        $locallyExpired = $module->isExpired();
        $dueForCheck    = !$lastCheck || now()->diffInHours($lastCheck) >= self::CHECK_INTERVAL_HOURS;

        if ($locallyExpired || $dueForCheck) {
            self::verifyWithApi($module, $cacheKey, $locallyExpired);

            $module->refresh();

            if ($module->isExpired()) {
                return false;
            }
        }

        return true;
    }

    /**
     * فاز ۸۵ — یافتن مقاوم رکورد ماژول در installed_modules
     * (slug دقیق → نام کانونی ماژول → تطبیق جزئی kebab-case).
     */
    private static function resolveModule(string $slug): ?InstalledModule
    {
        // ۱) تطبیق دقیق slug
        $module = InstalledModule::where('slug', $slug)->first();
        if ($module) {
            return $module;
        }

        // ۲) نام کانونی ماژول (ستون name در installed_modules از module.json می‌آید)
        $module = InstalledModule::where('name', 'CustomerClub')->first();
        if ($module) {
            return $module;
        }

        // ۳) تطبیق جزئی kebab-case (مثلاً 'customer-club-module')
        $kebab = str($slug)->kebab()->toString();

        return InstalledModule::where(function ($q) use ($kebab) {
            $q->where('slug', 'like', "%{$kebab}%")
              ->orWhere('name', 'like', '%CustomerClub%');
        })->first();
    }

    private static function verifyWithApi(InstalledModule $module, string $cacheKey, bool $isRecovery = false): void
    {
        try {
            $api    = app(PackageApiService::class);
            $result = $api->verifyLicense($module->slug, $module->license_key);

            if (!is_array($result) || !array_key_exists('valid', $result)) {
                Cache::put($cacheKey, now(), now()->addHours(self::FAIL_RETRY_HOURS));

                return;
            }

            if ($result['valid']) {
                $updateData = [];

                if (!empty($result['license_key']) && $result['license_key'] !== $module->license_key) {
                    $updateData['license_key'] = $result['license_key'];
                }

                if (isset($result['expires_at'])) {
                    $updateData['license_expires_at'] = $result['expires_at'];
                }

                if (!empty($updateData)) {
                    $module->update($updateData);
                }

                Cache::put($cacheKey, now(), now()->addHours(self::CHECK_INTERVAL_HOURS));
            } else {
                if ($module->status !== 'failed') {
                    $module->update(['license_expires_at' => now()->subMinute()]);
                }

                $ttl = $isRecovery
                    ? now()->addMinutes(self::RECOVERY_RETRY_MINUTES)
                    : now()->addHours(self::CHECK_INTERVAL_HOURS);
                Cache::put($cacheKey, now(), $ttl);
            }
        } catch (\Exception $e) {
            $ttl = $isRecovery
                ? now()->addMinutes(30)
                : now()->addHours(self::FAIL_RETRY_HOURS);
            Cache::put($cacheKey, now(), $ttl);

            Log::warning("LicenseGuard: API check failed for {$module->slug}: " . $e->getMessage());
        }
    }
}
