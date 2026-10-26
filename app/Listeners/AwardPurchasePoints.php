<?php

namespace Modules\CustomerClub\Listeners;

use App\Events\OrderPaid;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * امتیاز خرید — پس از پرداخت موفق سفارش.
 *
 * گاردها (الزام شخصی‌سازی از تنظیمات): club_enabled + loyalty_enabled +
 * قانون purchase فعال. امتیاز = floor(مبلغ کل / amount_step) و idempotent
 * با یگانهٔ (user, order) — دیسپچ دوبارهٔ رویداد امتیاز دوباره نمی‌دهد.
 */
class AwardPurchasePoints
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        if (!$order || !$order->user_id) {
            return;
        }

        if (!LoyaltyService::engineEnabled()) {
            return;
        }

        $points = LoyaltyService::purchasePointsForOrder($order);

        if ($points <= 0) {
            return;
        }

        LoyaltyService::award(
            $order->user_id,
            $points,
            LoyaltyPoint::TYPE_AWARD,
            "امتیاز خرید سفارش #{$order->id} — ".number_format((float) $order->price).' تومان',
            'order',
            (int) $order->id,
            null,
            true,
            LoyaltyPoint::EVENT_PURCHASE
        );

        // قانون خرید — فقط برای اطمینان از دست‌نخوردن seed (نه استفادهٔ مستقیم)
        LoyaltyRule::for(LoyaltyRule::EVENT_PURCHASE);
    }
}
