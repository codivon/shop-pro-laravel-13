<?php

namespace Modules\CustomerClub\Observers;

use App\Models\Review;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * امتیاز تأیید نظر — Observer پکیجی (بدون پچ هسته).
 *
 * فقط گذار وضعیت pending→accepted (یا هر وضعیت دیگری به accepted) امتیاز
 * می‌گیرد؛ ذخیرهٔ دوباره بدون تغییر وضعیت امتیاز نمی‌دهد (isDirty) و
 * یگانهٔ منبع هم مضاعف‌گرفتن را ناممکن می‌کند.
 */
class ReviewPointsObserver
{
    public function updated(Review $review): void
    {
        if (!$review->isDirty('status')) {
            return;
        }

        if ($review->status !== 'accepted') {
            return;
        }

        if ($review->getOriginal('status') === 'accepted') {
            return; // قبلاً تأیید بوده — گذار نیست
        }

        if (!$review->user_id) {
            return;
        }

        if (!LoyaltyService::engineEnabled()) {
            return;
        }

        $points = LoyaltyService::fixedPointsFor(\Modules\CustomerClub\Models\LoyaltyRule::EVENT_REVIEW);

        if ($points <= 0) {
            return;
        }

        LoyaltyService::award(
            (int) $review->user_id,
            $points,
            LoyaltyPoint::TYPE_AWARD,
            "امتیاز تأیید نظر #{$review->id}",
            'review',
            (int) $review->id,
            null,
            true,
            LoyaltyPoint::EVENT_REVIEW
        );
    }
}
