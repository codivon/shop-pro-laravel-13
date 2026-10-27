<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Seller;
use App\Models\SellerInfo;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام ۴ ثبت‌نام: بارگذاری مدارک — پورت SellerController@registration_documents.
 * بارگذاری/حذف تصاویر با fetch به همان اندپوینت‌های قدیمی (documents_store/delete/check).
 */
#[Title('بارگذاری مدارک')]
#[Layout('sellers.layouts.guest')]
class Documents extends Component
{
    /** تصاویر ثبت‌شدهٔ فعلی (card_image / card_image_back / vat_image) */
    public array $images = [];

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $seller = Seller::where(['mobile' => session('show-seller-documents')])->first();

        if (! $seller) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();

        $this->images = [
            'card_image' => $sellerInfo->card_image ?? '',
            'card_image_back' => $sellerInfo->card_image_back ?? '',
            'vat_image' => $sellerInfo->vat_image ?? '',
        ];
    }

    public function render()
    {
        return view('sellers.auth.documents');
    }
}
