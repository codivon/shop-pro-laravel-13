<?php

namespace App\Livewire\Seller\Auth;

use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۳ ثبت‌نام — فاز ۱۱۰: انتخاب وضعیت فروشنده (حقیقی/حقوقی)
 * مثل کارت‌های دیجی‌کالا؛ انتخابِ حقوقی فیلدهای شرکت را به گام بعد اضافه می‌کند.
 */
#[Title('وضعیت فروشنده')]
#[Layout('sellers.layouts.guest')]
class SellerType extends Component
{
    public ?string $type = null;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $draft = SellerRegistrationFlow::currentDraft();

        if (! $draft || ! $draft->reached('type')) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $this->type = $draft->type;
    }

    public function render()
    {
        return view('sellers.auth.type');
    }
}
