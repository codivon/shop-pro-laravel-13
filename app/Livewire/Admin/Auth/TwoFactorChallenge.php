<?php

namespace App\Livewire\Admin\Auth;

use App\Services\AdminTwoFactorService;
use App\Services\SecuritySettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/*
 * صفحهٔ چالش ورود دومرحله‌ای مدیر (فاز ۴۴)
 *
 * بین تأیید اعتبارنامه و ورود کامل قرار می‌گیرد؛ هر دو پنل (جدید/قدیمی)
 * به این صفحه می‌آیند و پس از تأیید کد به مقصد خودشان (np.dashboard یا
 * /admin-old/{prefix}) برمی‌گردند.
 */
#[Layout('admin-panel::layouts.login')]
#[Title('تأیید دومرحله‌ای')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    /** وضعیت چالش برای ویو */
    public array $challenge = [
        'pending'      => false,
        'email'        => null,
        'mobile'       => null,
        'channel'      => 'email',
        'expiresIn'    => 0,
        'attemptsLeft' => 5,
        'resendIn'     => 0,
        'locked'       => false,
        'lockedFor'    => 0,
    ];

    public function mount(AdminTwoFactorService $service)
    {
        // مدیر کامل لاگین کرده؟ → داشبورد
        if (Auth::guard('adminPanel')->check()) {
            return $this->redirectIntended(route('np.dashboard', absolute: false), navigate: true);
        }

        if (! SecuritySettings::twoFactorEnabled()) {
            return $this->redirect(route('np.login', absolute: false), navigate: true);
        }

        $admin = $service->pending();

        if (! $admin) {
            return $this->redirect(route('np.login', absolute: false), navigate: true);
        }

        $this->syncChallenge($service);
    }

    public function verify(AdminTwoFactorService $service)
    {
        $this->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'وارد کردن کد ۶ رقمی الزامی است.',
            'code.digits'   => 'کد تأیید باید ۶ رقم باشد.',
        ]);

        $admin = $service->pending();

        if (! $admin) {
            return $this->redirect(route('np.login', absolute: false), navigate: true);
        }

        try {
            $ok = $service->verify($admin, $this->code);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        if (! $ok) {
            $this->syncChallenge($service);
            $this->reset('code');

            $left = $service->attemptsLeft();

            throw ValidationException::withMessages([
                'code' => 'کد وارد شده اشتباه است.'
                    .($left > 0 ? " {$left} تلاش باقی مانده است." : ''),
            ]);
        }

        // ورود موفق — ریدایرکت به مقصد (پنل جدید یا قدیمی)
        $redirect = $service->takeRedirect();

        if ($redirect && str_contains($redirect, 'admin-old')) {
            // پنل قدیمی — ریدایرکت کامل (خارج از SPA)
            return $this->redirect($redirect);
        }

        return $this->redirectIntended(
            $redirect !== '' ? $redirect : route('np.dashboard', absolute: false),
            navigate: true
        );
    }

    public function resend(AdminTwoFactorService $service)
    {
        try {
            $sent = $service->resend();
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        $this->syncChallenge($service);

        $via = match (true) {
            $sent['email'] && $sent['sms'] => 'ایمیل و پیامک',
            $sent['sms']                   => 'پیامک',
            default                        => 'ایمیل',
        };

        $this->dispatch('toast', message: "کد تأیید جدید از طریق {$via} ارسال شد.", type: 'success');
    }

    public function cancel(AdminTwoFactorService $service)
    {
        $service->flush();

        return $this->redirect(route('np.login', absolute: false), navigate: true);
    }

    private function syncChallenge(AdminTwoFactorService $service): void
    {
        $state = session('admin_2fa', []);

        $this->challenge = [
            'pending'      => true,
            'email'        => $this->maskEmail($state['email'] ?? null),
            'mobile'       => $this->maskMobile($state['mobile'] ?? null),
            'channel'      => $state['channel'] ?? 'email',
            'expiresIn'    => $service->expiresIn(),
            'attemptsLeft' => $service->attemptsLeft(),
            'resendIn'     => $service->resendAvailableIn(),
            'locked'       => $service->isLocked(),
            'lockedFor'    => $service->lockedFor(),
        ];
    }

    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$name, $domain] = explode('@', $email, 2);
        $masked = mb_substr($name, 0, 2).str_repeat('*', max(1, mb_strlen($name) - 2));

        return $masked.'@'.$domain;
    }

    private function maskMobile(?string $mobile): ?string
    {
        if (! $mobile || mb_strlen($mobile) < 6) {
            return null;
        }

        return mb_substr($mobile, 0, 4).str_repeat('*', mb_strlen($mobile) - 7).mb_substr($mobile, -3);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.auth.two-factor-challenge');
    }
}
