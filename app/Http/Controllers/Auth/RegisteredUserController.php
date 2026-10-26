<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $params = null;
        if ($request->has('ref')) {
            $params = "?ref=" . $request->input('ref');
        }

        $loginUrl = route('login') . $params;
        return redirect($loginUrl, 303);
        $view = config('front.pages.register');

        if (!$view) {
            abort(404);
        }

        return view($view);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request)
    {
        $data             = $request->validated();

        $data['password']      = Hash::make($data['password']);
        $data['referral_code'] = Referral::generateCode();

        // ─── فاز ۹۲: گیت معرفی افراد — هسته یا پکیج باشگاه مشتریان ───
        // امکان «معرفی افراد» به پکیج منتقل شده؛ اگر پکیج نصب باشد و امکانش
        // روشن باشد، کد معرف ثبت‌نام پذیرفته می‌شود (تنظیمات: پکیج باشگاه مشتریان).
        $referralProgramOn = option('user_referrals_enable', 0) == 1
            || (class_exists(\Modules\CustomerClub\Models\ClubSetting::class)
                && \Modules\CustomerClub\Models\ClubSetting::enabled('referral_program_enabled'));

        if ($request->referral_code && $referralProgramOn) {
            $data['referral_id'] = User::where('referral_code', $request->referral_code)->first()->id;
        }

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return response('success');
    }
}
