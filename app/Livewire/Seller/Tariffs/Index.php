<?php

namespace App\Livewire\Seller\Tariffs;

use App\Models\Carrier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — لیست تعرفه‌های ارسال یک شرکت حمل (پورت SellerTariffController@index).
 * ?carrier= اجباری است (مثل قدیمی) — لیست شرکت‌ها از سایدبار/صفحهٔ carriers.
 */
#[Title('تعرفه‌های ارسال')]
#[Layout('sellers.layouts.panel')]
class Index extends Component
{
    public ?object $carrier = null;

    public function mount(): void
    {
        // مثل قدیمی: carrier اجباری — نبود/نامعتبر → 404 (میدل‌ور Seller نگه می‌دارد)
        $this->carrier = Carrier::findOrFail(request()->query('carrier'));
    }

    public function render()
    {
        $tariffs = $this->carrier->tariffs()
            ->orderBy('type')
            ->orderBy('max_weight')
            ->paginate(20);

        return view('sellers.panel.tariffs.index', ['tariffs' => $tariffs]);
    }
}
