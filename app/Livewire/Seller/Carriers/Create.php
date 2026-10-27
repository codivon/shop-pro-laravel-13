<?php

namespace App\Livewire\Seller\Carriers;

use App\Models\Province;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — ایجاد روش ارسال (پورت SellerCarrierController@create).
 *
 * فرم کلاسیک POST به seller.carriers.store با عیناً همان فیلدهای ویوی قدیمی
 * (title / image / is_active / waiting_time / max_package_weight / min_package_weight /
 * description / province_id / city_id / carrige_forward / free_shipping_weight /
 * free_shipping_price / extra_cost / covered_cities / included_cities[] /
 * delivery_time_type / start_days_after_order / default_delivery_range /
 * user_select_ranges / disable_fridays / disable_holidays)
 * — کنترلر قدیمی response('success') می‌دهد پس ارسال با fetch و در موفقیت
 * بازگشت به فهرست انجام می‌شود (هم‌الگوی create.js قدیمی).
 */
#[Layout('sellers.layouts.panel')]
#[Title('روش ارسال جدید')]
class Create extends Component
{
    public function render()
    {
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        return view('sellers.panel.carriers.create', [
            'provinces' => $provinces,
            'storeUrl' => route('seller.carriers.store'),
            'indexUrl' => route('seller.carriers.index'),
            'citiesUrl' => route('provinces.get-cities'),
        ]);
    }
}
