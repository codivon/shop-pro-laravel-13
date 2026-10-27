<?php

namespace App\Livewire\Seller\Carriers;

use App\Models\Carrier;
use App\Models\Province;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ویرایش روش ارسال (پورت SellerCarrierController@edit).
 *
 * گارد مالکیت: روش ارسال باید متعلق به این فروشنده باشد
 * (پنل قدیم این گارد را نداشت — طبق قرارداد فاز ۱۰۸ گارد اضافه شد).
 *
 * فرم کلاسیک PUT به seller.carriers.update با همان فیلدهای فرم ایجاد.
 */
#[Layout('sellers.layouts.panel')]
#[Title('ویرایش روش ارسال')]
class Edit extends Component
{
    public Carrier $carrier;

    public function mount(Carrier $carrier): void
    {
        // گارد مالکیت — روش ارسالِ فروشندهٔ دیگر ۴۰۴ است
        if ((int) $carrier->seller_id !== (int) sellerID()) {
            abort(404);
        }

        $this->carrier = $carrier;
    }

    public function render()
    {
        $carrier = $this->carrier->load(['cities:id,name', 'province', 'city']);
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();
        $includedCityIds = $carrier->cities->pluck('id')->all();

        return view('sellers.panel.carriers.edit', [
            'carrier' => $carrier,
            'provinces' => $provinces,
            'includedCityIds' => $includedCityIds,
            'includedCities' => $carrier->cities,
            'updateUrl' => route('seller.carriers.update', ['carrier' => $carrier]),
            'indexUrl' => route('seller.carriers.index'),
            'citiesUrl' => route('provinces.get-cities'),
        ]);
    }
}
