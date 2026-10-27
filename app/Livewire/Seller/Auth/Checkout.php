<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Category;
use App\Models\Seller;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام ۵ (پایانی) ثبت‌نام: انتخاب طرح/کمیسیون — پورت SellerController@registration_checkout.
 * پایان با fetch به seller.registration_checkout_store (JSON همان کنترلر قدیمی).
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
