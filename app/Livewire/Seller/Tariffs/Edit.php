<?php

namespace App\Livewire\Seller\Tariffs;

use App\Models\Tariff;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ویرایش تعرفهٔ ارسال (پورت SellerTariffController@edit).
 * فرم کلاسیک PUT به seller.tariffs.update (UpdateTariffRequest قدیمی).
 */
#[Title('ویرایش تعرفه')]
#[Layout('sellers.layouts.panel')]
class Edit extends Component
{
    public ?object $tariff = null;

    public function mount(Tariff $tariff): void
    {
        $this->tariff = $tariff;
    }

    public function render()
    {
        return view('sellers.panel.tariffs.edit');
    }
}
