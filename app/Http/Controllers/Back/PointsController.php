<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\LoyaltyPoint;

class PointsController extends Controller
{
    /**
     * دفتر کل امتیازها با فیلتر نوع/رویداد/کاربر.
     */
    public function index(Request $request)
    {
        $query = LoyaltyPoint::query()->with('user')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user')) {
            $term = $request->string('user');
            $query->whereHas('user', fn ($q) => $q->where('username', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));
        }

        return view('customer-club::back.points.index', [
            'rows'  => $query->paginate(30)->withQueryString(),
            'types' => [
                LoyaltyPoint::TYPE_AWARD  => 'کسب خودکار',
                LoyaltyPoint::TYPE_MANUAL => 'دستی/سرویس',
                LoyaltyPoint::TYPE_ADJUST => 'تنظیم ادمین',
                LoyaltyPoint::TYPE_REDEEM => 'کسر/تبدیل',
                LoyaltyPoint::TYPE_EXPIRE => 'انقضا',
            ],
            'events' => [
                LoyaltyPoint::EVENT_PURCHASE      => 'خرید',
                LoyaltyPoint::EVENT_REGISTER      => 'ثبت‌نام',
                LoyaltyPoint::EVENT_REVIEW        => 'نظر',
                LoyaltyPoint::EVENT_BIRTHDAY      => 'تولد',
                LoyaltyPoint::EVENT_REFERRAL_TIER => 'پاداش معرفی',
            ],
            'filterType'  => $request->type,
            'filterEvent' => $request->event,
            'filterUser'  => $request->user,
        ]);
    }
}
