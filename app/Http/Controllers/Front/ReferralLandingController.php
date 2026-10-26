<?php

namespace Modules\CustomerClub\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\CustomerClub\Models\ClubSetting;

class ReferralLandingController extends Controller
{
    /**
     * صفحهٔ فرود لینک دعوت — عمومی.
     * کد معتبر → ذخیرهٔ session + ریدایرکت به فلوی ثبت‌نام هسته (login?ref=)
     * دقیقاً مثل رفتار خود هسته؛ کد نامعتبر → ۴۰۴.
     */
    public function show(string $code)
    {
        if (!ClubSetting::enabled('club_enabled')
            || !ClubSetting::enabled('referral_program_enabled')) {
            abort(404);
        }

        $code = trim($code);

        $owner = User::query()
            ->where('referral_code', $code)
            ->first();

        if (!$owner) {
            abort(404);
        }

        session(['cc_referral_code' => $code]);

        return redirect()->to(route('login') . '?ref=' . urlencode($code));
    }
}
