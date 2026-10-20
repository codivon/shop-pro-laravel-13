<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\BotDetector;
use App\Services\CaptchaService;
use App\Services\LoginThrottle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:adminPanel')->except('logout');
    }

    public function showLoginForm()
    {
        if (count(Admin::all())){
            if (Auth::guard('adminPanel')->user()){
                return redirect('/admin-old/'.admin_route_prefix());
            }else{
                return view('back.auth.login');
            }
        }else{
            // فاز ۲۵ — هدایت به صفحه‌ی جدید ثبت‌نام مدیر (/setup)
            return redirect()->route('register');
        }
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username'   => 'required',
            'password'   => 'required|min:6',
            'captcha'    => config('security.login_captcha', true) ? ['required', 'string'] : ['nullable'],
            'rendered_at'=> 'nullable|string',
        ],[
            'username.required'   =>'فیلد ایمیل الزامی می باشد.',
            'password.required'   =>'فیلد رمز ورود الزامی می باشد.',
            'password.min'        =>'فیلد رمز ورود 6 کاراکتر الزامی می باشد.',
            'captcha.required'    =>'فیلد کد امنیتی الزامی می باشد.',
        ]);

        $detector = app(BotDetector::class);

        // ─── ربات‌گیر: تله‌ی فیلد مخفی ⇒ مسدودسازی کامل IP ───
        if ($request->filled('website_url') || $request->filled('confirm_email')) {
            $detector->trap($request);

            session()->put('admin_login_error', BotDetector::botMessage());
            return redirect('/admin-old/'.admin_route_prefix().'/login');
        }

        // ─── ربات‌گیر: تله‌ی زمان (فقط رد می‌شود) ───
        if ($detector->isBot($request, $request->input('rendered_at'))) {
            session()->put('admin_login_error', BotDetector::botMessage());
            return redirect('/admin-old/'.admin_route_prefix().'/login');
        }

        // ─── ضد brute-force: قفل بر اساس نام‌کاربری + IP ───
        if (LoginThrottle::tooManyAttempts((string) $request->username, (string) $request->ip())) {
            $seconds = LoginThrottle::availableIn((string) $request->username, (string) $request->ip());
            $minutes = (int) ceil($seconds / 60);

            session()->put('admin_login_error', "به دلیل تلاش‌های ناموفق زیاد، ورود موقتاً مسدود شده است. لطفاً {$minutes} دقیقه دیگر دوباره تلاش کنید.");
            return redirect('/admin-old/'.admin_route_prefix().'/login');
        }

        // ─── کپچا (ضد ربات و credential stuffing) ───
        if (config('security.login_captcha', true) && !app(CaptchaService::class)->verify($request->input('captcha'))) {
            app(CaptchaService::class)->flush(); // کد جدید برای تلاش بعدی

            session()->put('admin_login_error', 'کد امنیتی وارد شده اشتباه یا منقضی شده است.');
            return redirect('/admin-old/'.admin_route_prefix().'/login')
                ->withInput($request->only('username', 'remember'));
        }

        // Attempt to log the user in
        if (Auth::guard('adminPanel')->attempt(['username' => $request->username, 'password' => $request->password], $request->remember)) {
            // if successful, then redirect to their intended location
            if (admin()->status=="ACTIVE"){
                Admin::where('id', admin()->id)->update(['updated_at' => Carbon::now()->format('Y-m-d H:m:s')]);
                $request->session()->regenerate();
                LoginThrottle::clear((string) $request->username, (string) $request->ip());
                app(CaptchaService::class)->flush();
                return redirect('/admin-old/'.admin_route_prefix());
            }else{
                Auth::guard('adminPanel')->logout();
                session()->put('admin_login_error','حساب کاربری شما غیر فعال می باشد.');
                return redirect('/admin-old/'.admin_route_prefix().'/login');
            }

        }

        // ثبت تلاش ناموفق (قفل تدریجی)
        LoginThrottle::hitFailure((string) $request->username, (string) $request->ip());
        app(CaptchaService::class)->flush(); // کد جدید برای تلاش بعدی

        session()->put('admin_login_error','مشخصات وارد شده با اطلاعات ما سازگار نیست.');
        // if unsuccessful, then redirect back to the login with the form data
        return redirect()->back()->withInput($request->only('username', 'remember'));
    }
    public function logout()
    {
        Auth::guard('adminPanel')->logout();
        return redirect('/admin-old/'.admin_route_prefix().'/login');
    }
}
