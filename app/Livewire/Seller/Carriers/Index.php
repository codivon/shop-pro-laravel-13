<?php

namespace App\Livewire\Seller\Carriers;

use App\Models\Carrier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — روش‌های ارسال فروشنده (پورت SellerCarrierController@index).
 *
 * مبنای کوئری عیناً قدیم: Carrier::detectLang()->forCurrentSeller()->latest()->paginate(20).
 * حذف: فرم کلاسیک @method('DELETE') به seller.carriers.destroy (کنترلر قدیمی
 * response('success') می‌دهد — با fetch ارسال و در موفقیت رفرش فهرست می‌شود).
 */
#[Layout('sellers.layouts.panel')]
#[Title('روش‌های ارسال')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $carriers = Carrier::detectLang()->forCurrentSeller()->latest()
            ->with(['province:id,name', 'city:id,name', 'cities:id,name'])
            ->paginate(20);

        return view('sellers.panel.carriers.index', [
            'carriers' => $carriers,
            'createUrl' => route('seller.carriers.create'),
        ]);
    }
}
