<?php

namespace App\Livewire\Seller\Auth;

use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ورود فروشندگان پنل مدرن (پورت SellerController@login).
 * فرم با fetch به همان اندپوینت JSON قدیمی (seller.login_check) ارسال می‌شود —
 * منطق احراز هویت فقط یک منبع حقیقت دارد.
 */
#[Title('ورود فروشندگان')]
#[Layout('sellers.layouts.guest')]
class Login extends Component
{
    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        session()->forget([
            'show-seller-business-details',
            'show-seller-verification-form',
            'show-seller-documents',
            'show-seller-checkout',
        ]);

        // مثل قدیمی: فروشندهٔ لاگین‌شده مستقیم به داشبورد
        if (Auth::guard('seller')->check()) {
            $this->redirect(route('seller.dashboard'), navigate: true);
        }
    }

    public function render()
    {
        return view('sellers.auth.login');
    }
}
