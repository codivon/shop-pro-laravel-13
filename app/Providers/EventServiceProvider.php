<?php

namespace Modules\CustomerClub\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\CustomerClub\Listeners\AwardPurchasePoints;
use Modules\CustomerClub\Listeners\AwardReferralTierBonus;
use Modules\CustomerClub\Listeners\AwardRegisterPoints;

/**
 * گوش دادن به رویدادهای هسته — بدون هیچ پچ دستی در هسته.
 *
 * OrderPaid (App\Events\OrderPaid — dispatch در فلوی پرداخت تم و کیف پول):
 *  - AwardPurchasePoints:      امتیاز خرید برای خریدار
 *  - AwardReferralTierBonus:   پاداش سطح معرفی برای معرفِ دعوت‌کننده
 *
 * Registered (Illuminate\Auth\Events\Registered):
 *  - AwardRegisterPoints:      امتیاز خوش‌آمدگویی ثبت‌نام
 *
 * ⚠️ همان Listener را همزمان با Event::listen در boot ثبت نکنید (اجرای دوبل —
 * هشدار مستند CreditPay v1.0.2).
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\OrderPaid::class => [
            AwardPurchasePoints::class,
            AwardReferralTierBonus::class,
        ],
        \Illuminate\Auth\Events\Registered::class => [
            AwardRegisterPoints::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
