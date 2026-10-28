<?php

namespace App\Livewire\Seller\Auth;

use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۳ ثبت‌نام — فاز ۱۱۲: تنظیم رمز عبور (فقط رمز و تکرارش).
 *
 * بعد از تأیید کد موبایل می‌آید و رمز هش‌شده در پیش‌نویس ذخیره می‌شود.
 * فروشنده‌ای که رمز را ست کرده و ثبت‌نام را نیمه‌کاره رها کرده، با برگشتن
 * (کد موبایل جدید) دیگر این صفحه را نمی‌بیند و دقیقاً به مرحلهٔ خودش می‌رود
 * (گیت resume در mount + redirect سروری verifyPhoneOtp).
 */
#[Title('تنظیم رمز عبور')]
#[Layout('sellers.layouts.guest')]
class SellerPassword extends Component
{
    public ?array $seller = null;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $draft = SellerRegistrationFlow::currentDraft();

        // فقط وقتی به گام رمز رسیده باشد؛ وگرنه صفحهٔ اول ثبت‌نام
        if (! $draft || ! $draft->reached('password')) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        // ⑨-۲ — گام رمز یک‌بارمصرف است: پس از عبور (گام جلوتر)، بازگشت به
        // این گام ممنوع و به مرحلهٔ فعلیِ پیش‌نویس هدایت می‌شود؛ سایر گام‌ها
        // بازگشت‌پذیر می‌مانند.
        if ($draft->step !== 'password') {
            $this->redirect(SellerRegistrationFlow::resumeUrl($draft), navigate: true);

            return;
        }

        $this->seller = ['mobile' => $draft->mobile];
    }

    public function render()
    {
        return view('sellers.auth.password');
    }
}
