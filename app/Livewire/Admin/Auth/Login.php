<?php

namespace App\Livewire\Admin\Auth;

use App\Services\AdminAuthResolver;
use App\Services\AdminTwoFactorService;
use App\Services\BotDetector;
use App\Services\CaptchaService;
use App\Services\LoginThrottle;
use App\Services\SecuritySettings;
use App\Http\Middleware\CheckBlockedDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layouts.login')]
#[Title('ورود به پنل مدیریت')]
class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    /** ربات‌گیر: فیلد تله‌ی مخفی — کاربر واقعی آن را نمی‌بیند */
    public string $website_url = '';

    /** کپچای تصویری */
    public string $captcha = '';

    /** نسخه‌ی کپچا برای رفرش تصویر بدون رفرش صفحه */
    public int $captchaVersion = 1;

    /** زمان رندر فرم — برای تله‌ی زمان */
    public string $renderedAt = '';

    protected function rules(): array
    {
        $rules = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ];

        if (SecuritySettings::captchaEnabled('admin_login')) {
            $rules['captcha'] = ['required', 'string', 'min:3', 'max:10'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'username.required' => 'نام کاربری الزامی است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min'      => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'captcha.required'  => 'وارد کردن کد امنیتی الزامی است.',
            'captcha.min'       => 'کد امنیتی وارد شده معتبر نیست.',
        ];
    }

    public function mount()
    {
        if (Auth::guard('adminPanel')->check()) {
            return $this->redirectIntended(route('np.dashboard', absolute: false), navigate: true);
        }

        // فاز ۴۴ — اگر سشن در انتظار تأیید دومرحله‌ای است، مستقیم به چالش برو
        if (SecuritySettings::twoFactorEnabled() && app(AdminTwoFactorService::class)->pending()) {
            return $this->redirect(route('np.two-factor.challenge', absolute: false), navigate: true);
        }

        // ربات‌گیر: ثبت زمان رندر فرم برای تله‌ی زمان
        $this->renderedAt = (string) now()->getTimestamp();
    }

    public function refreshCaptcha()
    {
        $this->reset('captcha');
        app(CaptchaService::class)->generate();
        $this->captchaVersion++;
    }

    public function authenticate()
    {
        $this->validate();

        $ip       = (string) request()->ip();
        $username = $this->username;
        $detector = app(BotDetector::class);

        // ─── ربات‌گیر: تله‌ی فیلد مخفی ⇒ مسدودسازی کامل IP (فقط وقتی هانی‌پات روشن است) ───
        if (SecuritySettings::honeypotEnabled() && filled($this->website_url)) {
            $detector->trap(request());
            $this->reset('password', 'captcha');
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'username' => BotDetector::botMessage(),
            ]);
        }

        // ─── ربات‌گیر: تله‌ی زمان (فقط رد می‌شود؛ مسدودسازی نمی‌کنیم) ───
        if ($detector->isBot(request(), $this->renderedAt)) {
            $this->reset('password', 'captcha');
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'username' => BotDetector::botMessage(),
            ]);
        }

        // ─── ضد brute-force: قفل بر اساس نام‌کاربری + IP ───
        if (LoginThrottle::tooManyAttempts($username, $ip)) {
            $seconds = LoginThrottle::availableIn($username, $ip);
            $minutes = (int) ceil($seconds / 60);

            throw ValidationException::withMessages([
                'username' => "به دلیل تلاش‌های ناموفق زیاد، ورود موقتاً مسدود شده است. لطفاً {$minutes} دقیقه دیگر دوباره تلاش کنید.",
            ]);
        }

        // ─── کپچا (ضد ربات و credential stuffing) ───
        if (SecuritySettings::captchaEnabled('admin_login') && !app(CaptchaService::class)->verify($this->captcha)) {
            $this->reset('captcha');
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'captcha' => 'کد امنیتی وارد شده اشتباه یا منقضی شده است.',
            ]);
        }

        // ─── تأیید اعتبارنامه (بدون ورود کامل — تا 2FA در صورت فعال بودن اجرا شود) ───
        $admin = AdminAuthResolver::resolve($username, $this->password);

        if (! $admin) {
            // ثبت تلاش ناموفق (قفل تدریجی)
            LoginThrottle::hitFailure($username, $ip);

            $this->reset('password', 'captcha');
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'username' => 'اطلاعات ورود نادرست است.',
            ]);
        }

        LoginThrottle::clear($username, $ip);
        app(CaptchaService::class)->flush();

        // ─── فاز ۷۲ — مدیر مسدود (بن اکانت/دستگاه/آیپی/مرورگر) همان‌جا در همین صفحه پیام می‌گیرد ───
        if ($blocked = CheckBlockedDevice::activeBlockFor($admin, request())) {
            $this->reset('password', 'captcha');
            $this->refreshCaptcha();

            throw ValidationException::withMessages([
                'username' => CheckBlockedDevice::blockMessage($blocked),
            ]);
        }

        // ─── ورود دومرحله‌ای (فاز ۴۴) — مدیر می‌تواند برای خودش آن را خاموش کند (فاز ۷۲) ───
        if (SecuritySettings::twoFactorEnabled() && ! $admin->two_factor_disabled) {
            try {
                app(AdminTwoFactorService::class)->startChallenge(
                    $admin,
                    $this->remember,
                    route('np.dashboard')
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'username' => $e->getMessage(),
                ]);
            }

            return $this->redirect(route('np.two-factor.challenge', absolute: false), navigate: true);
        }

        // ورود موفق ⇒ رفرش سشن و هدایت به داشبورد
        session()->regenerate();
        Auth::guard('adminPanel')->login($admin, $this->remember);

        return $this->redirectIntended(route('np.dashboard', absolute: false), navigate: true);
    }

    public function render()
    {
        // اطمینان از وجود کد کپچا در سشن برای نمایش تصویر
        if (SecuritySettings::captchaEnabled('admin_login') && !session('captcha')) {
            app(CaptchaService::class)->generate();
        }

        return view('admin-panel::livewire.admin.auth.login');
    }
}
