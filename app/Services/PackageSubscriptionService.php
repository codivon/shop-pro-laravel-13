<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * فاز ۱۵۰ — حل‌کنندهٔ پوشش اشتراک برای پکیج‌های نصب‌شده.
 *
 * گزارش کاربر: پکیج‌هایی که با اشتراک فعال نصب شده‌اند، با انقضای لایسنسِ تکی
 * (license_expires_at محلی) در صفحهٔ نصب‌شده‌ها «منقضی شده» نشان داده می‌شدند؛
 * در حالی که تا پایان اشتراکِ پوشش‌دهنده باید معتبر بمانند. پکیجِ تک‌خرید‌شده
 * هم باید فقط با لایسنس خودش قضاوت شود — منبعِ حقیقتِ «این پکیج در اشتراک هست
 * یا نه» سرور بازار است (فیلد subscription در GET /api/v1/packages/{slug}).
 *
 * قرارداد کش: پاسخ خام subscription هر اسلاگ ۳۰ دقیقه کش می‌شود (شامل پاسخ
 * منفی «در اشتراک نیست» تا هر لود صفحه N بار زنگ API نزند). خطای ارتباط کش
 * نمی‌شود تا بار بعدی دوباره تلاش کند و محلی به رفتار قدیمی (فقط لایسنس) برمی‌گردیم.
 */
class PackageSubscriptionService
{
    /** تایم‌اوت کوتاه مثل verifyLicense — فراخوانی درون‌صفحه‌ای است */
    private const API_TIMEOUT = 6;

    private const CACHE_TTL_MINUTES = 30;

    public function __construct(private PackageApiService $api) {}

    /**
     * آبجکت خام subscription بازار برای یک پکیج (یا null = در اشتراک نیست/داده نامشخص)
     * @return array<string,mixed>|null
     */
    public function subscriptionFor(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $key = 'packages.subscription.'.md5($slug);

        $cached = Cache::get($key);

        if (is_array($cached)) {
            // نشانگر منفیِ «در اشتراک نیست» هم آرایه است ولی subscription واقعی نیست
            return $cached['subscription'] ?? null;
        }

        try {
            $package = $this->api->getPackage($slug, self::API_TIMEOUT);
            $data    = $package['data'] ?? $package;
            $sub     = $data['subscription'] ?? null;

            if (is_array($sub) && $sub !== []) {
                Cache::put($key, ['subscription' => $sub], now()->addMinutes(self::CACHE_TTL_MINUTES));

                return $sub;
            }

            // پاسخ منفی هم کش می‌شود تا هر لود صفحه زنگ نزند
            Cache::put($key, ['none' => true], now()->addMinutes(self::CACHE_TTL_MINUTES));

            return null;
        } catch (Throwable) {
            // خطای ارتباط — بدون کش منفی؛ دفعهٔ بعد دوباره تلاش می‌شود و تا آن‌موقع
            // رفتار محلی (فقط لایسنس) حفظ می‌شود.
            return null;
        }
    }

    /**
     * اگر اشتراکِ فعالِ بازار این پکیج را پوشش می‌دهد، تاریخِ پایانِ آن پوشش را
     * برمی‌گرداند؛ در غیر این صورت null — معنایی: «تا این تاریخ منقضی نشمار».
     */
    public function coverageUntil(string $slug): ?CarbonInterface
    {
        $sub = $this->subscriptionFor($slug);

        if (! $sub || empty($sub['is_free_with_subscription'])) {
            return null;
        }

        $expiresAt = $sub['subscription_expires_at'] ?? null;

        if (empty($expiresAt)) {
            // اشتراک بدون تاریخ انقضا = پوشش نامحدود تا هر زمان
            return Carbon::now()->addYears(100);
        }

        try {
            $coverage = Carbon::parse($expiresAt);
        } catch (Throwable) {
            return null;
        }

        return $coverage->isFuture() ? $coverage : null;
    }
}
