<?php

namespace App\Livewire\Seller\Auth;

use App\Support\Finnotech;
use App\Support\MultiVendorSettings;
use App\Support\SellerRegistrationFlow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * گام ۴ ثبت‌نام — فاز ۱۱۰: اطلاعات تکمیلی (کد ملی و مشخصات؛ برای حقوقی
 * اطلاعات شرکت). تأکید اجباری: «شمارهٔ موبایل و کد ملی باید متعلق به یک
 * نفر باشد» — اگر احراز هویت فینوتک (شاهکار) از تنظیمات برنامه‌نویس فعال
 * و در دسترس باشد، تطابق همان‌جا کنترل می‌شود.
 */
#[Title('اطلاعات تکمیلی')]
#[Layout('sellers.layouts.guest')]
class SellerIdentity extends Component
{
    public ?array $draft = null;

    public function mount(): void
    {
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $registration = SellerRegistrationFlow::currentDraft();

        if (! $registration || ! $registration->reached('identity')) {
            $this->redirect(route('seller.registration'), navigate: true);

            return;
        }

        $this->draft = [
            'mobile' => $registration->mobile,
            'type' => $registration->type,
            'national_code' => (string) $registration->national_code,
            'first_name' => (string) $registration->first_name,
            'last_name' => (string) $registration->last_name,
            'gender' => (string) ($registration->gender ?: 'male'),
            'birth' => (string) $registration->birth_date,
            'company_name' => (string) $registration->company_name,
            'company_type' => (string) ($registration->company_type ?: 'سهامی خاص'),
            'company_registration_number' => (string) $registration->company_registration_number,
            'company_national_identity_number' => (string) $registration->company_national_identity_number,
            'company_economic_number' => (string) $registration->company_economic_number,
        ];
    }

    public function render()
    {
        view()->share('sellerIdentityKyc', Finnotech::ready());

        return view('sellers.auth.identity');
    }
}
