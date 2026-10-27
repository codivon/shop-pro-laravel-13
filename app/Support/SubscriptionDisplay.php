<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * فاز ۱۰۰ — نمایش یکدست روزهای باقی‌ماندهٔ اشتراک (وب + API + هر دو پنل)
 *
 * مشکل قبلی: «round(now()->diffInDays($exp))» برای هر بازهٔ کمتر از ۱۲ ساعت
 * (و همچنین لحظهٔ انقضا) عدد ۰ تولید می‌کرد و کاربر می‌دید «۰ روز باقی‌مانده»
 * در حالی‌که اشتراک هنوز فعال بود (مثلاً ۹ ساعت مانده).
 *
 * راه‌حل: علاوه بر عدد صحیح روزها، پرچم «less_than_one_day» تولید می‌شود —
 * اشتراک هنوز فعال است ولی کمتر از یک روز (۲۴ ساعت) باقی مانده؛ ویوها در این
 * حالت به‌جای «۰ روز» عبارت «کمتر از ۱ روز» را نمایش می‌دهند.
 */
class SubscriptionDisplay
{
    /** عدد صحیح روز باقی‌مانده (۰ برای کمتر از یک روز یا منقضی) */
    public static function daysRemaining(CarbonInterface $exp): int
    {
        return max(0, (int) round(now()->diffInDays($exp)));
    }

    /** آیا اشتراک هنوز فعال است ولی کمتر از یک روز (۲۴ ساعت) باقی مانده؟ */
    public static function isLessThanOneDay(CarbonInterface $exp): bool
    {
        return $exp->isFuture() && now()->diffInMinutes($exp) < 1440;
    }

    /**
     * افزودن کلیدهای نمایشی به آرایهٔ اشتراک (per-package یا summary)
     *
     * @param  array  $subscription  بلوک اشتراک (per-package: subscription_expires_at | summary: expires_at)
     * @param  string  $expiryKey  نام کلید تاریخ انقضا در آرایه
     * @return array همان آرایه با days_remaining + less_than_one_day (+ is_expired)
     */
    public static function annotate(array $subscription, string $expiryKey): array
    {
        if (empty($subscription[$expiryKey])) {
            return $subscription;
        }

        try {
            $exp = Carbon::parse($subscription[$expiryKey]);
        } catch (\Throwable) {
            return $subscription;
        }

        $subscription['days_remaining'] = self::daysRemaining($exp);
        $subscription['less_than_one_day'] = self::isLessThanOneDay($exp);
        $subscription['is_expired'] = $exp->isPast();

        return $subscription;
    }
}
