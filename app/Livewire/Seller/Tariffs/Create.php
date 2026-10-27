<?php

namespace App\Livewire\Seller\Tariffs;

use App\Models\Carrier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ایجاد تعرفهٔ ارسال (پورت SellerTariffController@create).
 * فرم کلاسیک POST به seller.tariffs.store (StoreTariffRequest قدیمی اعتبارسنجی می‌کند).
 */
#[Title('تعرفهٔ جدید')]
#[Layout('sellers.layouts.panel')]
class Create extends Component
{
    public ?object $carrier = null;

    public function mount(): void
    {
        $this->carrier = Carrier::findOrFail(request()->query('carrier'));
    }

    public function render()
    {
        return view('sellers.panel.tariffs.create');
    }
}
