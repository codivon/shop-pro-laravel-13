<?php

namespace App\Livewire\Seller\Auth;

use App\Models\Seller;
use App\Models\SellerInfo;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — گام مدارک: بارگذاری مدارک — پورت SellerController@registration_documents.
 * بارگذاری/حذف تصاویر با fetch به همان اندپوینت‌های قدیمی (documents_store/delete/check).
 * فاز ۱۱۰ — رزومه: با گم‌شدن سشن، از پیش‌نویس (کوکی توکن‌دار) بازیابی می‌شود.
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

        // فاز ۱۱۰ — بازیابی از پیش‌نویس وقتی سشن گم شده (رفرش/قطع اینترنت)
        if (! $seller) {
            $draft = SellerRegistrationFlow::currentDraft();

            if ($draft && $draft->reached('documents')) {
                session()->put('show-seller-documents', $draft->mobile);
                $seller = Seller::where('mobile', $draft->mobile)->first();
            }
        }

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
