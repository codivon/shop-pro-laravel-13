<?php

namespace App\Livewire\Seller\Auth;

use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۱ ثبت‌نام — فاز ۱۱۰: فقط شمارهٔ موبایل (ایمیل/رمز در گام کسب‌وکار
 * جمع می‌شود). ارسال با fetch به seller.registration_phone_send (کنترلر
 * جدید) و ارسال کد ۶ رقمی.
 *
 * ذخیرهٔ مرحله‌ای: اگر پیش‌نویسِ ثبت‌نام از قبل وجود داشته باشد، کاربر
 * دقیقاً به همان مرحلهٔ رهاکرده برمی‌گردد (رفرش/قطع اینترنت پیشرفت را
 * نمی‌پراند).
 */
#[Title('ثبت‌نام فروشنده')]
#[Layout('sellers.layouts.guest')]
class Register extends Component
{
    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        if (Auth::guard('seller')->check()) {
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        // ادامهٔ ثبت‌نام نیمه‌کاره — دقیقاً همان مرحله
        $draft = SellerRegistrationFlow::activeDraft();

        if ($draft) {
            $this->redirect(SellerRegistrationFlow::resumeUrl($draft), navigate: true);

            return;
        }
    }

    public function render()
    {
        return view('sellers.auth.register');
    }
}
