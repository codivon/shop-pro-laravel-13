<?php

namespace App\Livewire\Seller\Auth;

use App\Models\SellerCommission;
use App\Models\SellerHero;
use App\Models\SellerQuestion;
use App\Models\Slider;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — لندینگ «فروشنده شوید» پنل مدرن (پورت SellerController@index).
 */
#[Title('فروشنده شوید')]
#[Layout('sellers.layouts.guest')]
class Landing extends Component
{
    public $sliders;

    public $sellersHeroes;

    public $sellersCommissions;

    public $sellersQuestions;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        // مثل قدیمی: ورود به لندینگ سشن‌های ثبت‌نام نیمه‌کاره را پاک می‌کند
        session()->forget([
            'show-seller-business-details',
            'show-seller-verification-form',
            'show-seller-documents',
            'show-seller-checkout',
        ]);

        $this->sliders = Slider::whereJsonContains('pages', 'sellers')
            ->where('published', true)
            ->orderBy('ordering')
            ->get();

        $this->sellersHeroes = SellerHero::all();
        $this->sellersCommissions = SellerCommission::all();
        $this->sellersQuestions = SellerQuestion::all();
    }

    public function render()
    {
        return view('sellers.auth.landing');
    }
}
