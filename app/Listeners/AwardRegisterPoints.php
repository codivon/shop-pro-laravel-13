<?php

namespace Modules\CustomerClub\Listeners;

use Illuminate\Auth\Events\Registered;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * امتیاز خوش‌آمدگویی ثبت‌نام — رویداد Registered هسته.
 *
 * گاردها: club_enabled + loyalty_enabled + قانون register فعال.
 * idempotent با یگانهٔ (user, register).
 */
class AwardRegisterPoints
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (!$user || !$user->id) {
            return;
        }

        if (!LoyaltyService::engineEnabled()) {
            return;
        }

        $points = LoyaltyService::fixedPointsFor(\Modules\CustomerClub\Models\LoyaltyRule::EVENT_REGISTER);

        if ($points <= 0) {
            return;
        }

        LoyaltyService::award(
            $user->id,
            $points,
            LoyaltyPoint::TYPE_AWARD,
            "پاداش خوش‌آمدگویی ثبت‌نام در باشگاه مشتریان",
            'user',
            (int) $user->id,
            null,
            true,
            LoyaltyPoint::EVENT_REGISTER
        );
    }
}
