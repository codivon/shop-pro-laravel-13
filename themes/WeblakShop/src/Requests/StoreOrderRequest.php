<?php

namespace Themes\WeblakShop\src\Requests;

use App\Models\Gateway;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     *  این rules طوری طراحی شده که هم درگاه‌های موجود در جدول gateways
     *  رو قبول می‌کنه و هم ماژول‌های پرداخت اختصاصی (مثل DigiPay) که در
     *  gateways ثبت می‌شن. ماژول‌های پرداخت اختصاصی هنگام فعال‌سازی به‌صورت
     *  خودکار به جدول gateways اضافه می‌شن، پس با implode(',', $gateways)
     *  خودکار پشتیبانی می‌شن.
     */
    public function rules()
    {
        $gateways = Gateway::active()->pluck('key')->toArray();
        $privateGateway=$this->paymentModuleGateways();

        $cart = get_cart();

        // بررسی وجود محصولات فیزیکی در سبد خرید
        $hasPhysical = $cart && $cart->products->filter(fn($p) => $p->type === 'physical')->isNotEmpty();

        // ===== ترکیب wallet + درگاه‌های فعال =====
        // همه‌ی gatewayها (شامل درگاه‌های شتابی و ماژول‌های پرداخت اختصاصی
        // مثل DigiPay که هنگام فعال‌سازی به جدول gateways اضافه می‌شن) در
        // این لیست هستن.
        $allowedGateways = array_merge(['wallet'], $gateways,$privateGateway);

        $rules = [
            'gateway'     => 'required|in:' . implode(',', $allowedGateways),
            'description' => 'nullable|string|max:1000',
        ];

        // اگر محصول فیزیکی وجود دارد، آدرس الزامی است
        // امنیتی (IDOR): آدرس باید متعلق به خود کاربر باشد نه کاربر دیگر
        $userId = $this->user() ? $this->user()->id : 0;

        if ($hasPhysical) {
            $rules['address'] = 'required|exists:addresses,id,user_id,' . $userId;
        } else {
            // اگر فقط محصول دانلودی است، آدرس اختیاری است
            $rules['address'] = 'nullable|exists:addresses,id,user_id,' . $userId;
        }

        return $rules;
    }

    protected function paymentModuleGateways(): array
    {
        $gateway=[];
        if(function_exists('module_is_active') && module_is_active('DigiPay')){
            $gateway[]='digipay';
        }
        // فاز ۸۱ — اسنپ‌پی داخل پکیج DigiPay است (درگاه اقساطی BNPL)
        if(function_exists('module_is_active') && module_is_active('DigiPay')){
            $gateway[]='snappay';
        }
        return $gateway;
    }
    /**
     * پیام‌های خطای سفارشی.
     */
    public function messages()
    {
        return [
            'gateway.required' => 'انتخاب روش پرداخت الزامی است.',
            'gateway.in'        => 'روش پرداخت انتخاب‌شده معتبر نیست.',
            'address.required'  => 'انتخاب آدرس برای محصولات فیزیکی الزامی است.',
            'address.exists'    => 'آدرس انتخاب‌شده یافت نشد.',
        ];
    }
}
