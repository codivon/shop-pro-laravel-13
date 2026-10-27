<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Category;
use App\Models\NewSeller;
use App\Models\Province;
use App\Models\SellerEcontract;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام ۳ ثبت‌نام: اطلاعات کسب‌وکار — پورت SellerController@registration_business_details.
 * فرم با fetch (FormData) به seller.registration_business_details_store ارسال می‌شود.
 */
#[Title('اطلاعات کسب‌وکار')]
#[Layout('sellers.layouts.guest')]
class BusinessDetails extends Component
{
    public ?array $seller = null;

    /** قرارداد الکترونیکی (رکورد ۱ — مثل قدیمی) */
    public ?object $econtract = null;

    public $provinces;

    public $categories;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        // مثل قدیمی: اگر مدارک ثبت شده مستقیم به گام مدارک
        if (session('show-seller-documents')) {
            $this->redirect(route('seller.registration_documents'), navigate: true);

            return;
        }

        $newSeller = NewSeller::where(['mobile' => session('show-seller-business-details')])->first();

        if (! $newSeller) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $this->seller = ['mobile' => $newSeller->mobile];
        $this->econtract = SellerEcontract::find(1);
        $this->provinces = Province::all();
        $this->categories = Category::where('category_id', null)->get();
    }

    public function render()
    {
        return view('sellers.auth.business-details');
    }
}
