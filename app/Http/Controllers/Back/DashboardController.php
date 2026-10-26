<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\GiftCard;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Models\ReferralTier;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'members'          => $this->membersQuery()->count(),
            'points_live'      => (int) LoyaltyPoint::query()->where('remaining', '>', 0)->sum('remaining'),
            'points_issued'    => (int) LoyaltyPoint::query()->where('points', '>', 0)->sum('points'),
            'points_redeemed'  => (int) abs((int) LoyaltyPoint::query()->where('type', LoyaltyPoint::TYPE_REDEEM)->where('points', '<', 0)->sum('points')),
            'points_expired'   => (int) abs((int) LoyaltyPoint::query()->where('type', LoyaltyPoint::TYPE_EXPIRE)->where('points', '<', 0)->sum('points')),
            'giftcards_total'  => GiftCard::query()->count(),
            'giftcards_active' => GiftCard::query()->where('status', GiftCard::STATUS_ACTIVE)->count(),
            'referral_orders'  => $this->referralOrdersQuery(),
        ];

        // روند ۱۴ روز اخیر کسب امتیاز
        $trend = LoyaltyPoint::query()
            ->where('points', '>', 0)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw("DATE(created_at) as day, SUM(points) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->day => (int) $row->total]);

        $days = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $chart = $days->map(fn ($day) => [
            'day'   => $day,
            'total' => $trend[$day] ?? 0,
        ]);

        return view('customer-club::back.dashboard.index', [
            'stats'     => $stats,
            'chart'     => $chart,
            'rules'     => LoyaltyRule::query()->orderBy('id')->get(),
            'tiers'     => ReferralTier::query()->orderBy('min_referrals')->get(),
            'clubOn'    => ClubSetting::enabled('club_enabled'),
        ]);
    }

    private function membersQuery()
    {
        // بدون پچ هسته: اعضای دارای لات زنده با زیرکوئری
        return User::query()->whereIn('id', LoyaltyPoint::query()->select('user_id')->where('remaining', '>', 0));
    }

    private function referralOrdersQuery(): int
    {
        return Order::query()
            ->where('status', 'paid')
            ->whereHas('user', fn ($q) => $q->whereNotNull('referral_id'))
            ->count();
    }
}
