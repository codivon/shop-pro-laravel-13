<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CustomerClub\Models\ReferralTier;

class ReferralsController extends Controller
{
    /**
     * گزارش معرفی دوستان — معرف‌های دارای دعوت‌شده + آمار معرفی موفق.
     * بدون پچ هسته: همهٔ آمارها با زیرکوئری روی users.referral_id.
     */
    public function index(Request $request)
    {
        $referredTotalSql = '(SELECT COUNT(*) FROM users ru WHERE ru.referral_id = users.id)';
        $referredPaidSql  = "(SELECT COUNT(*) FROM users ru JOIN orders o ON o.user_id = ru.id
                              WHERE ru.referral_id = users.id AND o.status = 'paid')";

        $query = User::query()
            ->whereIn('id', User::query()->select('referral_id')->whereNotNull('referral_id'))
            ->select('users.*')
            ->selectRaw("{$referredTotalSql} as referred_total")
            ->selectRaw("{$referredPaidSql} as referred_paid")
            ->orderByDesc(DB::raw($referredPaidSql))
            ->orderByDesc(DB::raw($referredTotalSql));

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn ($q) => $q->where('referral_code', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%"));
        }

        return view('customer-club::back.referrals.index', [
            'rows'    => $query->paginate(30)->withQueryString(),
            'tiers'   => ReferralTier::query()->orderBy('min_referrals')->get(),
            'term'    => $request->q,
            'bonusOn' => \Modules\CustomerClub\Models\ClubSetting::enabled('referral_bonus_enabled'),
        ]);
    }
}
