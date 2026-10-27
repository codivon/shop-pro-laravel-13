<?php

namespace App\Livewire\Seller\Auth;

use App\Models\NewSeller;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۲ ثبت‌نام — فاز ۱۱۰: کد تأیید ۶ رقمی.
 *
 * تایمر «ارسال مجدد» (۲ دقیقه) از updated_at رکورد دیتابیس محاسبه
 * می‌شود؛ بنابراین با رفرش صفحه زمان از دست نمی‌رود و گیت واقعی آن
 * سمت سرور (seller.registration_phone_resend) کنترل می‌شود.
 */
#[Title('تأیید شمارهٔ موبایل')]
#[Layout('sellers.layouts.guest')]
class RegisterMobile extends Component
{
    public ?array $seller = null;

    public int $resendTime = 0;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $draft = SellerRegistrationFlow::currentDraft();

        // ادامهٔ نیمه‌کارهٔ جلوتر از تأیید کد؟ → همان مرحله
        if ($draft && $draft->reached('type')) {
            $this->redirect(SellerRegistrationFlow::resumeUrl($draft), navigate: true);

            return;
        }

        // فاز ۱۱۰ — با گم‌شدن سشن (مثلاً رفت‌وبرگشت به لندینگ)، موبایل از
        // پیش‌نویس بازیابی می‌شود تا صفحهٔ کد نتواند با ثبت‌نام حلقه بزند
        $mobile = session(SellerRegistrationFlow::SESSION_VERIFICATION) ?: $draft?->mobile;

        $newSeller = $mobile ? NewSeller::where(['mobile' => $mobile])->first() : null;

        // مثل قدیمی: بدون گام ارسال کد → برگرد به ثبت‌نام (پیش‌نویس بی‌اعتبار پاک می‌شود)
        if (! $newSeller) {
            if ($draft) {
                session()->forget(SellerRegistrationFlow::SESSION_DRAFT);
            }

            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        if ($mobile !== $newSeller->mobile) {
            $mobile = $newSeller->mobile;
        }

        session([SellerRegistrationFlow::SESSION_VERIFICATION => $mobile]);

        $this->seller = ['mobile' => $newSeller->mobile];
        $this->resendTime = max(0, $newSeller->updated_at->addSeconds(SellerRegistrationFlow::RESEND_SECONDS)->timestamp - now()->timestamp);
    }

    public function render()
    {
        return view('sellers.auth.register-mobile');
    }
}
