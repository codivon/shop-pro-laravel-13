<?php

namespace Modules\CustomerClub\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Models\ReferralTier;
use Modules\CustomerClub\Services\GiftCardService;
use Modules\CustomerClub\Services\LoyaltyService;

class ClubController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!ClubSetting::enabled('club_enabled')) {
            return redirect('/')->with('toast-error', 'باشگاه مشتریان در حال حاضر غیرفعال است.');
        }

        // کد معرفی — اگر کاربر ندارد، خودکار بساز (مطابق هسته)
        if (empty($user->referral_code)) {
            $user->update(['referral_code' => Referral::generateCode()]);
            $user->refresh();
        }

        $balance = LoyaltyService::balance($user->id);

        return view('customer-club::front.index', [
            'user'       => $user,
            'balance'    => $balance,
            'rate'       => max(1, (int) ClubSetting::get('redeem_rate_toman', '1000')),
            'minPoints'  => max(1, (int) ClubSetting::get('redeem_min_points', '100')),
            'expiryDays' => (int) ClubSetting::get('points_expiry_days', '0'),
            // پرچم‌های شخصی‌سازی (الزام: هر قابلیت از تنظیمات کنترل شود)
            'loyaltyOn'   => ClubSetting::enabled('loyalty_enabled'),
            'giftcardsOn' => ClubSetting::enabled('giftcards_enabled'),
            'referralOn'  => ClubSetting::enabled('referral_program_enabled'),
            'ledger'      => LoyaltyPoint::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(12)
                ->get(),
            'successfulReferrals' => User::query()
                ->where('referral_id', $user->id)
                ->whereHas('orders', fn ($q) => $q->where('status', 'paid'))
                ->count(),
            'totalReferrals' => User::query()
                ->where('referral_id', $user->id)
                ->count(),
            'tiers' => ReferralTier::query()->orderBy('min_referrals')->get(),
            'purchaseRule' => LoyaltyRule::for(LoyaltyRule::EVENT_PURCHASE),
        ]);
    }

    /** تبدیل امتیاز به کیف پول هسته (POST) */
    public function redeem(Request $request)
    {
        if (!LoyaltyService::engineEnabled()) {
            return back()->with('toast-error', 'امتیاز وفاداری در حال حاضر غیرفعال است.');
        }

        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
        ]);

        [$ok, $message] = LoyaltyService::redeemToWallet(auth()->user(), (int) $data['points']);

        return back()->with($ok ? 'toast-success' : 'toast-error', $message);
    }

    /** فعال‌سازی کارت هدیه (POST) */
    public function redeemGiftCard(Request $request)
    {
        if (!GiftCardService::enabled()) {
            return back()->with('toast-error', 'کارت هدیه در حال حاضر غیرفعال است.');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:24'],
        ]);

        [$ok, $message] = GiftCardService::redeem(auth()->user(), $data['code']);

        return back()->with($ok ? 'toast-success' : 'toast-error', $message);
    }
}
