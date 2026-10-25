<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Message;
use App\Models\Option;
use App\Models\Product;
use App\Models\Province;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserMobileVerify;
use App\Models\Widget;
use App\Notifications\Sms\NewSellerCodeSent;
use App\Notifications\Sms\NewUserCodeSent;
use App\Notifications\Sms\VerifyCodeSent;
use App\Services\BotDetector;
use App\Services\CaptchaService;
use App\Services\SecuritySettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;

class UserController extends Controller
{
    public function Check_Mobile_Email(Request $request)
    {

        $this->validate($request, [
            'username'   => 'required',
        ],[
            'username.required'=>'لطفا این قسمت را خالی نگذارید'
        ]);

        // ─── فاز ۴۴ — ربات‌گیر: هانی‌پات (مسدودسازی IP) + تلهٔ زمان ───
        $detector = app(BotDetector::class);

        if (SecuritySettings::honeypotEnabled() && ($request->filled('website_url') || $request->filled('confirm_email'))) {
            $detector->trap($request);

            throw ValidationException::withMessages([
                'username' => BotDetector::botMessage(),
            ]);
        }

        if ($detector->isBot($request, $request->input('rendered_at'))) {
            throw ValidationException::withMessages([
                'username' => BotDetector::botMessage(),
            ]);
        }

        // ─── فاز ۴۴ — کپچای ورود مشتری (در صورت فعال بودن از تنظیمات امنیتی) ───
        if (SecuritySettings::captchaEnabled('customer_login') && !app(CaptchaService::class)->verify($request->input('captcha'))) {
            app(CaptchaService::class)->flush();

            throw ValidationException::withMessages([
                'captcha' => 'کد امنیتی وارد شده صحیح نیست یا منقضی شده است.',
            ]);
        }

        if ($this->username($request->username)=="mobile") {
            $this->validate($request, [
                'username' => 'digits:11|regex:/(09)[0-9]{9}/',
            ],[
                'username.regex'=>'شماره موبایل نادرست است',
                'username.digits'=>'شماره موبایل نادرست است',
            ]);
        }else if ($this->username($request->username)=="email") {

            $this->validate($request, [
                'username' => 'string|email|exists:users,email',
            ],[
                'username.email'=>'ایمیل را بطور صحیح وارد کنید',
                'username.exists'=>'حساب کاربری با مشخصات وارد شده وجود ندارد. لطفا از شماره تلفن همراه برای ساخت حساب کاربری استفاده نمایید.',
            ]);

        }

        $params = null;
        if ($request->has('ref')) {
            $params = "?ref=" . $request->input('ref');
        }

        session()->put('ShowPasswordForm_Mobile_Email',$request->username);
        return redirect('/login/password'.$params);


    }
    public function ShowPasswordForm(Request $request,$data=null)
    {

        if (session('SettFirstOnePassword')){

            $params = null;
            if ($request->has('ref')) {
                $params = "?ref=" . $request->input('ref');
            }
            $request=$request->all();
            if (User::where('username',session('SettFirstOnePassword'))->exists()){
                session()->put('ShowPasswordForm_Mobile_Email',session('SettFirstOnePassword'));
                return view('front::auth.password-login',compact('request'));
            }else{
                session()->forget('SettFirstOnePassword');
                session()->forget('ShowPasswordForm_Mobile_Email');
                return redirect('/login'.$params);
            }

        }else{

            if (session('ShowPasswordForm_Mobile_Email')){
                $username=session('ShowPasswordForm_Mobile_Email');
                if (User::where($this->username(session('ShowPasswordForm_Mobile_Email')),$username)->exists()){
                    return view('front::auth.password-login');
                }else{

                    $UserMobileVerify=UserMobileVerify::where('mobile',$username)->first();
                    if (!session('ErrorCode') and session('ErrorCode')!='true'){

                        $code = rand(10000, 99999);
                        if ($UserMobileVerify){
                            $UserMobileVerify->code=$code;
                            $UserMobileVerify->save();
                        }else{
                            $UserMobileVerify=new UserMobileVerify();
                            $UserMobileVerify->mobile=$username;
                            $UserMobileVerify->code=$code;
                            $UserMobileVerify->save();
                        }
                        Notification::send($UserMobileVerify, new NewUserCodeSent($UserMobileVerify));
                    }
                   session()->forget('ErrorCode');

                    $resend_time = $UserMobileVerify->updated_at->addSeconds(120)->timestamp;
                    return view('front::auth.password-register-verify',compact('resend_time'));
                }


            }else{
                return redirect('/login');
            }
        }


    }
    public function CheckPassword(Request $request)
    {
        // ─── فاز ۴۴ — قفل تلاش ورود مشتری (ضد brute-force) ───
        $customerLockKey = 'front-login:'.sha1(mb_strtolower(trim((string) $request->username)).'|'.$request->ip());

        if (SecuritySettings::lockoutEnabled()
            && \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($customerLockKey, SecuritySettings::lockoutMax('customer'))) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($customerLockKey);

            throw ValidationException::withMessages([
                'username' => 'به دلیل تلاش‌های ناموفق زیاد، ورود موقتاً مسدود شده است. لطفاً '.(int) ceil($seconds / 60).' دقیقه دیگر دوباره تلاش کنید.',
            ]);
        }

        session()->put('ShowPasswordForm_Mobile_Email',$request->username);
        $this->validate($request, [
            'username'   => 'required',
            'password' => 'required|min:6',
            'referralCode' => 'nullable|exists:users,referral_code',
        ],[
            'username.required'=>'لطفا این قسمت را خالی نگذارید.',
            'password.required'=>'لطفا این قسمت را خالی نگذارید.',
            'password.min'=>'فیلد رمز ورود 6 کاراکتر الزامی می باشد.',
            'referralCode.exists'=>'کد معرف معتبر نمی باشد.',
        ]);

        // Attempt to log the user in
        session()->forget('ShowPasswordForm_Mobile_Email');

        if (session('SettFirstOnePassword')){
            $referralCode=null;
            if ($request->has('referralCode')){
                $referralCode=User::where('referral_code',$request->referralCode)->first()->id;
            }
            $user=User::where('username',session('SettFirstOnePassword'))->first();
            $user->password=Hash::make($request->password);
            $user->referral_id=$referralCode;
            $user->save();

            //$user=User::where('username',session('SettFirstOnePassword'))->update(['password'=>Hash::make($request->password),'referral_id'=>$referralCode]);
            event(new Registered($user));
            Auth::guard('web')->attempt([$this->username($request->username) => $request->username, 'password' => $request->password], $request->remember);
            $request->session()->regenerate();
            session()->put('UserWelcome',$request->username);
            session()->forget('SettFirstOnePassword');
            return redirect('/welcome');
        }else{

            if (Auth::guard('web')->attempt([$this->username($request->username) => $request->username, 'password' => $request->password], $request->remember)) {
                // if successful, then redirect to their intended location
                if (Auth::user()->status){
                    //Admin::where('id', admin()->id)->update(['updated_at' => Carbon::now()->format('Y-m-d H:m:s')]);
                    $request->session()->regenerate();

                    // فاز ۴۴ — پاک‌سازی شمارندهٔ تلاش‌ها پس از ورود موفق
                    \Illuminate\Support\Facades\RateLimiter::clear($customerLockKey);

                    return redirect('/');
                }else{
                    Auth::guard('web')->logout();
                    session()->put('admin_login_error','حساب کاربری شما غیر فعال می باشد.');
                    return redirect('/login');
                }

            }else{
                // فاز ۴۴ — ثبت تلاش ناموفق (قفل تدریجی)
                if (SecuritySettings::lockoutEnabled()) {
                    \Illuminate\Support\Facades\RateLimiter::hit($customerLockKey, SecuritySettings::lockoutWindowSeconds('customer'));
                }

                session()->put('ShowPasswordForm_Mobile_Email',$request->username);
                session()->put('admin_login_error','رمز عبور وارد شده صحیح نمی باشد.');
                return redirect('/login/password');
            }

        }
    }
    public function Register_Mobile(Request $request)
    {

        session()->put('ShowPasswordForm_Mobile_Email',$request->mobile);
        $this->validate($request, [
            'verify_code'   => 'required|numeric|digits:5',
            'mobile'   => 'required',
        ],[
            'verify_code.required'=>'فیلد کد تایید الزامی است',
            'verify_code.digits'=>'کد تایید باید ۵ رقمی باشد',
            'verify_code.numeric'=>'فقط از اعداد استفاده کنید',
        ]);
        $code=$request->verify_code;
        $UserMobileVerify=UserMobileVerify::where('mobile',$request->mobile)->first();
        if ($UserMobileVerify->code==$code){
            $UserMobileVerify=UserMobileVerify::where('mobile',session('ShowPasswordForm_Mobile_Email'))->first();
            session()->put('SettFirstOnePassword',$UserMobileVerify->mobile);
            if ($UserMobileVerify){
                $user=new User();
                $user->mobile=$UserMobileVerify->mobile;
                $user->username=$UserMobileVerify->mobile;
                $user->level='user';
                $user->referral_code=Referral::generateCode();
                $user->verified_at= Carbon::now();
                $user->save();
                $UserMobileVerify->delete();
            }
            session()->forget('ShowPasswordForm_Mobile_Email');
            return redirect('/login/password');


        }else{
            session()->put('ErrorCode','true');
            return Redirect::route('front.user.CheckMobileEmailPassword')->with(['data' => $request->all()] )->withErrors(['msg' => 'کد وارد شده اشتباه است']);
        }
    }

    public function welcome()
    {
        if (session('UserWelcome')){
            $user=User::where('username',session('UserWelcome'))->first();

            // رفع باگ نوتیف تکراری — نوتیفیکیشن «کاربر جدید» و پیامک خوش‌آمدگویی
            // قبلاً در Listener رویداد Registered (app/Listeners/User/Registered.php)
            // با event(new Registered($user)) در CheckPassword ارسال شده‌اند؛
            // ارسال مجدد در اینجا باعث دوبار آمدن نوتیف در پنل ادمین می‌شد.

            session()->forget('UserWelcome');
            return view('front::auth.welcome');
        }else{
            return redirect('/');
        }
    }

    public function profile()
    {
        $user        = auth()->user();
        $last_orders = $user->orders()->latest()->take(3)->get();
        $special_products=Product::where('special',true)->published()->latest()->take(15)->get();
        $widgets = Widget::with('options')->where('key', 'middle-banners-4')->where('is_active', true)->orderBy('ordering')->first();
        $active="profile";
        return view('front::user.profile', compact('user', 'last_orders','special_products','widgets','active'));
    }
    public function update_profile(Request $request)
    {
        $this->validate($request, [
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'email'        => 'string|email|max:191|unique:users,email,' . auth()->user()->id,
            'national_code'=> 'string|digits:10|max:191|unique:users,national_code,' . auth()->user()->id . '|nullable',
        ]);
        $newsletter=0;
        $birth_date=auth()->user()->birth_date;
        if ($request->day!="date-desc" and $request->month!="date-desc" and $request->year!="date-desc"){
            $birth_date=$request->year.'/'.$request->month.'/'.$request->day;
        }
        if ($request->newsletter){
            $newsletter=1;
        }


        auth()->user()->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'national_code'      => $request->national_code,
            'birth_date'      => $birth_date,
            'card_number'      => $request->card_number,
            'newsletter'      => $newsletter,
        ]);

        return response('success');
    }
    public function changePassword()
    {
        return view('front::auth.passwords.reset');
    }
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'prev_password' => 'required'
        ]);

        if (!Hash::check($request->prev_password, auth()->user()->password)) {
            throw ValidationException::withMessages(['prev_password' => 'رمز عبور قبلی وارد شده اشتباه است']);
        }

        $this->validate($request, [
            'password' => 'required|min:6|confirmed'
        ]);

        $password = Hash::make($request->password);

        auth()->user()->update([
            'password'       => $password,
            'remember_token' => Str::random(60),
        ]);

        DB::table('sessions')->where('user_id', auth()->user()->id)->delete();

        return response('success');
    }

    public function forceChangePassword()
    {
        return view('front::auth.passwords.force-change');
    }

    public function forceUpdatePassword(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:6|confirmed'
        ]);

        auth()->user()->update([
            'password'                 => Hash::make($request->password),
            'remember_token'           => Str::random(60),
            'force_to_password_change' => false,
        ]);

        DB::table('sessions')->where('user_id', auth()->user()->id)->delete();
        Auth::loginUsingId(auth()->user()->id);

        return response('success');
    }

    public function editProfile()
    {
        $user      = auth()->user();
        $provinces = Province::all();
        $active="profileEdit";
        return view('front::user.edit-profile', compact('user', 'provinces','active'));
    }

    public function username($login)
    {
        if(is_numeric($login)){
            $field = 'mobile';
        } else {
            $field = 'email';
        }
        request()->merge([$field => $login]);

        return $field;
    }
    public function notifications()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('notification_manage_users.created_at', 'desc')
            ->paginate(15);

        // علامت‌گذاری همه به عنوان خوانده شده
        auth()->user()->markAllNotificationsAsRead();
        $active="profileEdit";
        return view('front::user.notifications', compact( 'notifications','active'));
    }
    public function messages()
    {
        $messages = auth()->user()->messages()->orderby('id','desc')->paginate(15);
        $active="messages";
        return view('front::user.messages.index', compact( 'messages','active'));
    }

    public function messages_show(Message $message)
    {
        $itemMessage = $message->items()->first();
        $itemMessage->status = "seen";
        $itemMessage->save();

        if (request()->ajax()) {
            return view('front::user.messages.show')->with('message', $message);
        }

        // در غیر این صورت صفحه کامل را رندر کن
        return view('front::user.messages.show')->with(['message' => $message]);
    }

    public function referrals()
    {
        $userId = auth()->id();

        // -----------------------------------------------------------
        // ۱) جوایز و کدهای تخفیف دریافتی کاربر
        //    - به‌عنوان معرف (owner_id) که owner_discount_id یا owner_wallet_history_id دارد
        //    - به‌عنوان معرفی‌شده (user_id) که user_discount_id یا user_wallet_history_id دارد
        // -----------------------------------------------------------
        $refrrals = Referral::query()
            ->where(function ($query) use ($userId) {
                $query->where('owner_id', $userId)
                    ->where(function ($q) {
                        $q->whereNotNull('owner_discount_id')
                            ->orWhereNotNull('owner_wallet_history_id');
                    });
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->whereNotNull('user_discount_id')
                            ->orWhereNotNull('user_wallet_history_id');
                    });
            })
            ->with([
                // اطلاعات مربوط به تخفیف‌ها
                'referralDiscount' => fn($q) => $q->select(['id', 'code', 'amount', 'type', 'end_date']),
                'userDiscount'     => fn($q) => $q->select(['id', 'code', 'amount', 'type', 'end_date']),

                // اطلاعات مربوط به کیف پول
                'ownerWalletHistory' => fn($q) => $q->select(['id', 'amount', 'type', 'description', 'created_at']),
                'userWalletHistory'  => fn($q) => $q->select(['id', 'amount', 'type', 'description', 'created_at']),

                'user'             => fn($q) => $q->select(['id', 'first_name', 'last_name','username']),
                'owner'            => fn($q) => $q->select(['id', 'first_name', 'last_name','username']),
            ])
            ->latest()
            ->paginate(10, ['*'], 'rewards_page');

        // -----------------------------------------------------------
        // ۲) زیرمجموعه‌های مستقیم کاربر (کسانی که با کد معرف این کاربر ثبت‌نام کرده‌اند)
        //    چون فیلد referred_by در جدول users وجود ندارد، از جدول referrals استفاده می‌کنیم.
        // -----------------------------------------------------------
        $directReferralUserIds = Referral::query()
            ->where('owner_id', $userId)
            ->pluck('user_id')
            ->toArray();

        $directReferrals = User::query()
            ->whereIn('id', $directReferralUserIds)
            ->select(['id', 'first_name', 'last_name', 'username', 'mobile', 'created_at'])
            ->latest()
            ->paginate(10, ['*'], 'directs_page');

        // شناسه کاربرانی که خرید موفق داشته‌اند (discount برایشان صادر شده = خرید کامل‌شده)
        $qualifiedUserIds = Referral::query()
            ->where('owner_id', $userId)
            ->whereNotNull('user_discount_id')
            ->pluck('user_id')
            ->toArray();

        // اضافه‌کردن وضعیت has_qualified_purchase به هر کاربر
        $directReferrals->getCollection()->transform(function ($user) use ($qualifiedUserIds) {
            $user->has_qualified_purchase = in_array($user->id, $qualifiedUserIds);
            return $user;
        });

        // -----------------------------------------------------------
        // ۳) آمار برای کارت‌های بالای صفحه
        // -----------------------------------------------------------
        $totalReferrals      = count($directReferralUserIds);
        $successfulReferrals = count($qualifiedUserIds);
        $totalRewards        = $refrrals->total();

        // -----------------------------------------------------------
        // ۴) تنظیمات سامانه معرف (مطابق فیلدهای پنل مدیریت)
        // -----------------------------------------------------------
        $settings = [
            'user_register_gift_credit'       => option('user_register_gift_credit', 0),
            'user_referrals_enable'             => option('user_referrals_enable', 'true'),
            'user_referrals_gift_type'          => option('user_referrals_gift_type', 'discount_code'),
            'user_referrals_gift_discount_type' => option('user_referrals_gift_discount_type', 'amount'),
            'owner_referrals_amount'            => option('owner_referrals_amount', 5),
            'user_referrals_amount'             => option('user_referrals_amount', 10),
            'minimum_amount_gift'             => option('minimum_amount_gift', 0),
            'minimum_product_gift'            => option('minimum_product_gift', 1),
        ];

        $referralCode = auth()->user()->referral_code;
        $active       = 'referrals';

        return view('front::user.referrals.index', compact(
            'refrrals',
            'directReferrals',
            'successfulReferrals',
            'totalReferrals',
            'totalRewards',
            'settings',
            'referralCode',
            'active'
        ));
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required',
        ]);

        $user = auth()->user();


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر یافت نشد'
            ], 401);
        }

        $notificationId = $request->notification_id;
        $updated = $user->notifications()
            ->wherePivot('notification_manage_id', $notificationId)
            ->update([
                'notification_manage_users.read' => true,
                'notification_manage_users.read_at' => now()
            ]);
        return response()->json([
            'success' => true,
            'message' => 'اعلان با موفقیت خوانده شد'
        ]);


    }

}
