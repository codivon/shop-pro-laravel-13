<?php

namespace App\Livewire\Seller;

use App\Models\Category;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerEcontract;
use App\Models\SellerInfo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — پروفایل فروشنده (پورت ProfileSellerController@index).
 *
 * فرم کلاسیک PUT به seller.profile.update با «عیناً» همان فیلدهای ویوی قدیمی
 * (resources/views/sellers-old/panel/profile/index.blade.php) — کنترلر قدیمی
 * به‌جای redirect با response("success") + session toast پاسخ می‌دهد، پس فرم با
 * fetch ارسال و در موفقیت به seller.profile.index بازگردانده می‌شود (هم‌الگوی JS قدیمی).
 *
 * امضای قرارداد الکترونیک: POST کلاسیک به seller.profile.set_econtract
 * (کنترلر قدیمی JSON {status} برمی‌گرداند — پاسخ با fetch مدیریت می‌شود).
 *
 * نکته: روت seller.profile.store در کنترلر قدیمی متدی ندارد (از ابتدا
 * نوشته نشده) و فرم قدیمی فقط update را صدا می‌زد — پس این صفحه هم فقط
 * update/set_econtract را صدا می‌زند (انحراف آگاهانه از قرارداد).
 */
#[Layout('sellers.layouts.panel')]
#[Title('پروفایل شما')]
class Profile extends Component
{
    public function render()
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $seller_info = SellerInfo::where('seller_id', $seller->id)->first();
        $categories = Category::where('category_id', null)->get();
        $econtract = SellerEcontract::find(1);
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        return view('sellers.panel.profile.index', [
            'seller' => $seller,
            'seller_info' => $seller_info,
            'categories' => $categories,
            'provinces' => $provinces,
            'econtract' => $econtract,
            'updateUrl' => route('seller.profile.update', ['seller' => $seller]),
            'setEcontractUrl' => route('seller.profile.set_econtract'),
            'citiesUrl' => route('provinces.get-cities'),
        ]);
    }
}
