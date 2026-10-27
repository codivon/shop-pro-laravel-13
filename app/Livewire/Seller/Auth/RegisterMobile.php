<?php

namespace App\Livewire\Seller\Auth;

use App\Models\NewSeller;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام ۲ ثبت‌نام: تأیید کد موبایل — پورت SellerController@registration_mobile.
 * ارسال با fetch به seller.registration_mobile_check و ارسال مجدد به seller.get-new-code-for-seller.
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

        $newSeller = NewSeller::where(['mobile' => session('show-seller-verification-form')])->first();

        // مثل قدیمی: بدون سشن گام ۱ → برگرد به ثبت‌نام
        if (! $newSeller) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $this->seller = ['mobile' => $newSeller->mobile];
        $this->resendTime = max(0, $newSeller->updated_at->addSeconds(120)->timestamp - now()->timestamp);
    }

    public function render()
    {
        return view('sellers.auth.register-mobile');
    }
}
