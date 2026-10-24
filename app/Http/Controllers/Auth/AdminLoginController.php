<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminAuthResolver;
use App\Services\AdminTwoFactorService;
use App\Services\BotDetector;
use App\Services\CaptchaService;
use App\Services\LoginThrottle;
use App\Services\SecuritySettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
            'captcha'    => SecuritySettings::captchaEnabled('admin_login') ? ['required', 'string'] : ['nullable'],
            'rendered_at'=> 'nullable|string',
        ],[
            'username.required'   =>'فیلد ایمیل الزامی می باشد.',
            'password.required'   =>'فیلد رمز ورود الزامی می باشد.',
            'password.min'        =>'فیلد رمز ورود 6 کاراکتر الزامی می باشد.',
            'captcha.required'    =>'فیلد کد امنیتی الزامی می باشد.',
        ]);

        $detector = app(BotDetector::class);

        // ─── ربات‌گیر: تله‌ی فیلد مخفی ⇒ مسدودسازی کامل IP (فقط وقتی هانی‌پات روشن است) ───
        if (SecuritySettings::honeypotEnabled() && ($request->filled('website_url') || $request->filled('confirm_email'))) {
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
        if (SecuritySettings::captchaEnabled('admin_login') && !app(CaptchaService::class)->verify($request->input('captcha'))) {
            app(CaptchaService::class)->flush(); // کد جدید برای تلاش بعدی

            session()->put('admin_login_error', 'کد امنیتی وارد شده اشتباه یا منقضی شده است.');
            return redirect('/admin-old/'.admin_route_prefix().'/login')
                ->withInput($request->only('username', 'remember'));
        }

        // ─── تأیید اعتبارنامه (بدون ورود کامل — تا 2FA در صورت فعال بودن اجرا شود) ───
        $admin = AdminAuthResolver::resolve((string) $request->username, (string) $request->password);

        if (! $admin) {
            // ثبت تلاش ناموفق (قفل تدریجی)
            LoginThrottle::hitFailure((string) $request->username, (string) $request->ip());
            app(CaptchaService::class)->flush(); // کد جدید برای تلاش بعدی

            session()->put('admin_login_error','مشخصات وارد شده با اطلاعات ما سازگار نیست.');
            // if unsuccessful, then redirect back to the login with the form data
            return redirect()->back()->withInput($request->only('username', 'remember'));
        }

        LoginThrottle::clear((string) $request->username, (string) $request->ip());
        app(CaptchaService::class)->flush();

        // ─── ورود دومرحله‌ای (فاز ۴۴) ───
        if (SecuritySettings::twoFactorEnabled()) {
            try {
                app(AdminTwoFactorService::class)->startChallenge(
                    $admin,
                    $request->boolean('remember'),
                    '/admin-old/'.admin_route_prefix()
                );
            } catch (\RuntimeException $e) {
                session()->put('admin_login_error', $e->getMessage());

                return redirect('/admin-old/'.admin_route_prefix().'/login');
            }

            // چالش در پنل جدید میزبانی می‌شود (طراحی مشترک)
            return redirect()->to(Route::has('np.two-factor.challenge')
                ? route('np.two-factor.challenge')
                : '/admin/'.admin_route_prefix().'/two-factor');
        }

        // ورود کامل
        Auth::guard('adminPanel')->login($admin, $request->boolean('remember'));

        if ($admin->status == 'ACTIVE'){
            Admin::where('id', $admin->id)->update(['updated_at' => Carbon::now()->format('Y-m-d H:m:s')]);
            $request->session()->regenerate();

            return redirect('/admin-old/'.admin_route_prefix());
        }

        Auth::guard('adminPanel')->logout();
        session()->put('admin_login_error','حساب کاربری شما غیر فعال می باشد.');

        return redirect('/admin-old/'.admin_route_prefix().'/login');
    }

    public function logout()
    {
        // پاک‌سازی وضعیت چالش دومرحله‌ای در صورت وجود
        app(AdminTwoFactorService::class)->flush();

        Auth::guard('adminPanel')->logout();

        return redirect('/admin-old/'.admin_route_prefix().'/login');
    }
}
