<?php

namespace Modules\CustomerClub\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Modules\CustomerClub\Models\ClubSetting;

class GuideController extends Controller
{
    /**
     * راهنمای مشتری — درون‌صفحه‌ای (درس فاز ۸۹: لینکش در منوی فرانت نیست).
     */
    public function index()
    {
        if (!ClubSetting::enabled('club_enabled')) {
            return redirect('/')->with('toast-error', 'باشگاه مشتریان در حال حاضر غیرفعال است.');
        }

        return view('customer-club::front.guide.index', [
            'rate'        => (int) ClubSetting::get('redeem_rate_toman', '1000'),
            'minPoints'   => (int) ClubSetting::get('redeem_min_points', '100'),
            'expiryDays'  => (int) ClubSetting::get('points_expiry_days', '0'),
            'loyaltyOn'   => ClubSetting::enabled('loyalty_enabled'),
            'giftcardsOn' => ClubSetting::enabled('giftcards_enabled'),
            'referralOn'  => ClubSetting::enabled('referral_program_enabled'),
        ]);
    }
}
