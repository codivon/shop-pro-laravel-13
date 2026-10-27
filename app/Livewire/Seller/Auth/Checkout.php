<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Category;
use App\Models\Seller;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام پایانی ثبت‌نام: انتخاب طرح/کمیسیون — پورت SellerController@registration_checkout.
 * پایان با fetch به seller.registration_checkout_store (JSON همان کنترلر قدیمی).
 * فاز ۱۱۰ — رزومه: با گم‌شدن سشن، از پیش‌نویس (کوکی توکن‌دار) بازیابی می‌شود.
 */
#[Title('انتخاب طرح همکاری')]
#[Layout('sellers.layouts.guest')]
class Checkout extends Component
{
    public $categories;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $seller = Seller::where(['mobile' => session('show-seller-checkout')])->first();

        // فاز ۱۱۰ — بازیابی از پیش‌نویس وقتی سشن گم شده
        if (! $seller) {
            $draft = SellerRegistrationFlow::currentDraft();

            if ($draft && $draft->reached('checkout')) {
                session()->put('show-seller-checkout', $draft->mobile);
                $seller = Seller::where('mobile', $draft->mobile)->first();
            }
        }

        if (! $seller) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $this->categories = Category::where('commission', '!=', null)->get();
    }

    public function render()
    {
        return view('sellers.auth.checkout');
    }
}
