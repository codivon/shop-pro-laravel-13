<?php

namespace App\Services;

use App\Mail\SendMessage;
use App\Models\Admin;
use App\Models\Sms;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Throwable;

/*
 * ورود دومرحله‌ای مدیر (2FA) — فاز ۴۴
 *
 * جریان: اعتبارنامهٔ مدیر در هر دو پنل (جدید/قدیمی) تأیید می‌شود اما «login» کامل نمی‌شود؛
 * به‌جای آن وضعیت چالش در سشن ذخیره و کد ۶ رقمی یک‌بارمصرف ارسال می‌شود. پس از تأیید کد
 * در صفحهٔ چالش، ورود واقعی انجام می‌گیرد.
 *
 * قواعد (قابل تنظیم از تب «امنیت» — مقادیر ثابت طبق بریفینگ):
 *  - کد ۶ رقمی، یک‌بارمصرف، اعتبار ۵ دقیقه (فقط هش ذخیره می‌شود)
 *  - قفل: ۵ تلاش اشتباه در ۵ دقیقه ⇒ چالش قفل و سشن پاک می‌شود (باید دوباره وارد شود)
 *  - ارسال مجدد: هر ۱ دقیقه یک‌بار
 *  - کانال: ایمیل / پیامک / هر دو (پیامک به admin_mobile_number از تنظیمات پیامک)
 */
class AdminTwoFactorService
{
    /** اعتبار کد (ثانیه) */
    public const CODE_TTL = 300;

    /** حداکثر تلاش اشتباه */
    public const MAX_ATTEMPTS = 5;

    /** پنجرهٔ قفل تلاش‌ها (ثانیه) */
    public const LOCK_WINDOW = 300;

    /** فاصلهٔ ارسال مجدد (ثانیه) */
    public const RESEND_SECONDS = 60;

    /** کلید سشن وضعیت چالش */
    private const SESSION_KEY = 'admin_2fa';

    /** کلید شمارندهٔ تلاش‌های اشتباه */
    private const ATTEMPTS_KEY = 'admin-2fa-attempts:';

    /** کلید محدودساز ارسال مجدد */
    private const RESEND_KEY = 'admin-2fa-resend:';

    /**
     * شروع چالش برای مدیری که اعتبارنامه‌اش تأیید شده است.
     *
     * @return array{email: bool, sms: bool} وضعیت ارسال هر کانال
     *
     * @throws RuntimeException اگر هیچ کانالی قابل ارسال نبود
     */
    public function startChallenge(Admin $admin, bool $remember, string $redirect): array
    {
        // حفاظت در برابر session fixation — سشن تازه قبل از ذخیرهٔ وضعیت چالش
        Session::regenerate();

        $code    = (string) random_int(100000, 999999);
        $channel = SecuritySettings::twoFactorChannel();

        Session::put(self::SESSION_KEY, [
            'admin_id' => $admin->id,
            'remember' => $remember,
            'redirect' => $redirect,
            'code_hash' => $this->hash($code),
            'expires_at' => now()->addSeconds(self::CODE_TTL)->getTimestamp(),
            'channel' => $channel,
            'email' => $admin->email,
            'mobile' => SecuritySettings::twoFactorMobile(),
            'sent_at' => now()->getTimestamp(),
        ]);

        RateLimiter::clear($this->attemptsKey());
        RateLimiter::clear($this->resendKey());
        RateLimiter::hit($this->resendKey(), self::RESEND_SECONDS);

        return $this->deliver($admin, $code, $channel);
    }

    /** آیا سشن جاری در انتظار تأیید دومرحله‌ای است؟ */
    public function pending(): ?Admin
    {
        $state = Session::get(self::SESSION_KEY);

        if (! is_array($state) || empty($state['admin_id']) || empty($state['expires_at'])) {
            return null;
        }

        // انقضای کد ⇒ وضعیت باطل (مدیر باید دوباره وارد شود)
        if (now()->getTimestamp() > (int) $state['expires_at']) {
            $this->flush();

            return null;
        }

        /** @var Admin|null $admin */
        $admin = Admin::query()->find((int) $state['admin_id']);

        if (! $admin) {
            $this->flush();

            return null;
        }

        return $admin;
    }

    /** ثانیه‌های باقی‌ماندهٔ اعتبار کد جاری */
    public function expiresIn(): int
    {
        $state = Session::get(self::SESSION_KEY);

        if (! is_array($state)) {
            return 0;
        }

        return max(0, (int) $state['expires_at'] - now()->getTimestamp());
    }

    /** آیا به‌خاطر تلاش‌های زیاد قفل شده است؟ */
    public function isLocked(): bool
    {
        return RateLimiter::tooManyAttempts($this->attemptsKey(), self::MAX_ATTEMPTS);
    }

    /** ثانیه‌های باقی‌مانده تا باز شدن قفل */
    public function lockedFor(): int
    {
        return max(1, RateLimiter::availableIn($this->attemptsKey()));
    }

    /** تعداد تلاش‌های باقی‌مانده */
    public function attemptsLeft(): int
    {
        return max(0, self::MAX_ATTEMPTS - RateLimiter::attempts($this->attemptsKey()));
    }

    /** ثانیه‌های باقی‌مانده تا امکان ارسال مجدد */
    public function resendAvailableIn(): int
    {
        return max(0, RateLimiter::availableIn($this->resendKey()));
    }

    /**
     * تأیید کد واردشده — در موفقیت ورود کامل انجام و true برمی‌گردد.
     *
     * @throws RuntimeException اگر قفل فعال باشد
     */
    public function verify(Admin $admin, string $code): bool
    {
        if ($this->isLocked()) {
            $this->flush();

            throw new RuntimeException('به دلیل تلاش‌های ناموفق زیاد، تأیید دومرحله‌ای موقتاً مسدود شده است. لطفاً دوباره وارد شوید.');
        }

        $state = Session::get(self::SESSION_KEY);

        if (! is_array($state) || empty($state['code_hash'])) {
            throw new RuntimeException('چالش ورود دومرحله‌ای یافت نشد. لطفاً دوباره وارد شوید.');
        }

        if (now()->getTimestamp() > (int) $state['expires_at']) {
            $this->flush();

            throw new RuntimeException('کد تأیید منقضی شده است. لطفاً دوباره وارد شوید.');
        }

        $ok = hash_equals((string) $state['code_hash'], $this->hash($code));

        if (! $ok) {
            RateLimiter::hit($this->attemptsKey(), self::LOCK_WINDOW);

            if (RateLimiter::tooManyAttempts($this->attemptsKey(), self::MAX_ATTEMPTS)) {
                $this->flush();
            }

            return false;
        }

        // ─── موفق ───
        $remember = (bool) ($state['remember'] ?? false);
        $redirect = (string) ($state['redirect'] ?? '');

        $this->flush();
        RateLimiter::clear($this->attemptsKey());
        RateLimiter::clear($this->resendKey());

        // ورود واقعی پس از پاک‌سازی وضعیت چالش + سشن تازه
        auth('adminPanel')->login($admin, $remember);
        Session::regenerate();

        Session::put('admin_2fa_redirect', $redirect);

        return true;
    }

    /** مقصد ریدایرکت پس از تأیید موفق (یک‌بارمصرف) */
    public function takeRedirect(): string
    {
        $redirect = (string) Session::pull('admin_2fa_redirect', '');

        return $redirect;
    }

    /**
     * ارسال مجدد کد — حداکثر هر ۱ دقیقه.
     *
     * @return array{email: bool, sms: bool}
     *
     * @throws RuntimeException در حالت قفل یا ارسال زودتر از موعد
     */
    public function resend(): array
    {
        $admin = $this->pending();

        if (! $admin) {
            throw new RuntimeException('چالش ورود دومرحله‌ای یافت نشد. لطفاً دوباره وارد شوید.');
        }

        if ($this->isLocked()) {
            $this->flush();

            throw new RuntimeException('به دلیل تلاش‌های ناموفق زیاد، تأیید دومرحله‌ای موقتاً مسدود شده است. لطفاً دوباره وارد شوید.');
        }

        if (RateLimiter::tooManyAttempts($this->resendKey(), 1)) {
            throw new RuntimeException('برای ارسال مجدد کد، '.$this->resendAvailableIn().' ثانیه دیگر تلاش کنید.');
        }

        $state = Session::get(self::SESSION_KEY);

        $code    = (string) random_int(100000, 999999);
        $channel = (string) ($state['channel'] ?? SecuritySettings::twoFactorChannel());

        $state['code_hash']  = $this->hash($code);
        $state['expires_at'] = now()->addSeconds(self::CODE_TTL)->getTimestamp();
        $state['sent_at']    = now()->getTimestamp();
        Session::put(self::SESSION_KEY, $state);

        RateLimiter::hit($this->resendKey(), self::RESEND_SECONDS);

        return $this->deliver($admin, $code, $channel);
    }

    /** پاک‌سازی کامل وضعیت چالش سشن جاری */
    public function flush(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget('admin_2fa_redirect');
    }

    /* ================= ارسال کد ================= */

    /**
     * ارسال کد از طریق کانال(های) انتخاب‌شده.
     *
     * @return array{email: bool, sms: bool}
     *
     * @throws RuntimeException اگر هیچ کانالی موفق نشد
     */
    private function deliver(Admin $admin, string $code, string $channel): array
    {
        $sent = ['email' => false, 'sms' => false];

        if (in_array($channel, ['email', 'both'], true) && $email = SecuritySettings::twoFactorEmailTargets($admin)) {
            $sent['email'] = $this->sendByEmail($email, $code);
        }

        if (in_array($channel, ['sms', 'both'], true) && $mobile = SecuritySettings::twoFactorMobile()) {
            $sent['sms'] = $this->sendBySms($mobile, $code);
        }

        if (! $sent['email'] && ! $sent['sms']) {
            // کانال پیامک انتخاب شده اما شمارهٔ مدیر ثبت نشده؟ پیام راهنما
            $hint = SecuritySettings::twoFactorMobile()
                ? 'ارسال کد تأیید ناموفق بود. تنظیمات ایمیل/پیامک پنل را بررسی کنید.'
                : 'شماره تلفن مدیر (admin_mobile_number) در تنظیمات پیامک ثبت نشده یا ایمیل مدیر خالی است.';

            throw new RuntimeException($hint);
        }

        return $sent;
    }

    private function sendByEmail(string $email, string $code): bool
    {
        try {
            Mail::to($email)->send(new SendMessage(
                'کد ورود دومرحله‌ای — '.option('info_site_title', config('app.name')),
                'کد تأیید ورود شما به پنل مدیریت: <strong dir="ltr" style="font-size:22px;letter-spacing:6px;">'.$code.'</strong>'
                .'<br><small>این کد ۵ دقیقه اعتبار دارد و یک‌بارمصرف است. اگر شما درخواست نداده‌اید، این ایمیل را نادیده بگیرید.</small>'
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function sendBySms(string $mobile, string $code): bool
    {
        try {
            // از همان نوع VERIFY_CODE (کد تأیید) با پترن تنظیم‌شده در پنل پیامک استفاده می‌شود
            (new SmsService($mobile, ['code' => $code], Sms::TYPES['VERIFY_CODE']))->sendSms();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** هش کد با نمک کلید اپ — کد خام هرگز ذخیره نمی‌شود */
    private function hash(string $code): string
    {
        return hash('sha256', config('app.key').'|admin-2fa|'.trim($code));
    }

    private function attemptsKey(): string
    {
        return self::ATTEMPTS_KEY.sha1((string) Session::getId());
    }

    private function resendKey(): string
    {
        return self::RESEND_KEY.sha1((string) Session::getId());
    }
}
