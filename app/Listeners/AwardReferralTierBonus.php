<?php

namespace Modules\CustomerClub\Listeners;

use App\Events\OrderPaid;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\ReferralTier;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * پاداش سطوح معرفی دوستان — پس از پرداخت سفارشِ دعوت‌شده.
 *
 * گاردها: club_enabled + referral_program_enabled + referral_bonus_enabled.
 * معرفِ کاربرِ خریدار پیدا می‌شود (users.referral_id)؛ تعداد «معرفی موفق»
 * معرف = دعوت‌شدگانی که دست‌کم یک سفارش پرداخت‌شده دارند؛ بهترین سطحِ
 * حاصل‌شده به معرف پاداش یک‌بارمصرف می‌گیرد (idempotent با یگانهٔ
 * (owner, referral_tier, tier_id) در دفتر امتیاز).
 */
class AwardReferralTierBonus
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        if (!$order || !$order->user_id) {
            return;
        }

        if (!\Modules\CustomerClub\Models\ClubSetting::enabled('club_enabled')) {
            return;
        }

        if (!\Modules\CustomerClub\Models\ClubSetting::enabled('referral_program_enabled')
            || !\Modules\CustomerClub\Models\ClubSetting::enabled('referral_bonus_enabled')) {
            return;
        }

        $buyer = $order->user;

        if (!$buyer || !$buyer->referral_id) {
            return; // خریدار با لینک دعوت ثبت‌نام نکرده
        }

        $ownerId = (int) $buyer->referral_id;

        // خودداری از حلقهٔ معرفی (کسی به خودش معرف نباشد)
        if ($ownerId === (int) $buyer->id) {
            return;
        }

        $successful = \App\Models\User::query()
            ->where('referral_id', $ownerId)
            ->whereHas('orders', fn ($q) => $q->where('status', 'paid'))
            ->count();

        $tier = ReferralTier::bestFor($successful);

        if (!$tier) {
            return;
        }

        LoyaltyService::award(
            $ownerId,
            (int) $tier->bonus_points,
            LoyaltyPoint::TYPE_AWARD,
            "پاداش سطح «{$tier->title}» معرفی دوستان — {$successful} معرفی موفق",
            'referral_tier',
            (int) $tier->id,
            null,
            true,
            LoyaltyPoint::EVENT_REFERRAL_TIER
        );
    }
}
