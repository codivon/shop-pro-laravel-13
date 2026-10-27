<?php

namespace App\Livewire\Seller\Auth;

use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام ۱ ثبت‌نام (اطلاعات پایه: ایمیل/موبایل/رمز) — پورت SellerController@registration.
 * ارسال با fetch به seller.registration_new_seller (JSON همان کنترلر قدیمی).
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

        session()->forget([
            'show-seller-business-details',
            'show-seller-verification-form',
            'show-seller-documents',
            'show-seller-checkout',
        ]);

        if (Auth::guard('seller')->check()) {
            $this->redirect(route('seller.dashboard'), navigate: true);
        }
    }

    public function render()
    {
        return view('sellers.auth.register');
    }
}
