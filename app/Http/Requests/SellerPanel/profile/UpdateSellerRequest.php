<?php

namespace App\Http\Requests\SellerPanel\profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
            $rules = [
                'email'     => 'required|email|unique:sellers,id,'.$this->seller->id,
                'state_id' => 'required',
                'city_id' => 'required',
                'address' => 'required|persian_alpha_eng_num',
                'post_code' => 'required|digits:10',
                'phone' => 'required|digits:11',
                'shaba_number' => 'required',
                'main_supply_category_id' => 'required',
                'number_of_products' => 'required',
                'password'   => ['nullable', 'string', 'min:6', 'confirmed:confirmed'],
            ];

        if ($this->input('private_business')=="private") {
            $rules = array_merge($rules, [
                'first_name' => 'required|persian_alpha',
                'last_name' => 'required|persian_alpha',
                'birth_day' => 'required',
                'gender' => 'required',
                'identity_card_number' => 'required',
                'national_identity_number' => 'required|digits:10',
            ]);
        }

        if ($this->input('private_business')=="business") {
            $rules = array_merge($rules, [
                'company_name' => 'required|persian_alpha',
                'company_type' => 'required',
                'company_registration_number' => 'required',
                'company_national_identity_number' => 'required|digits:11',
                'company_economic_number' => 'nullable|digits:12',
            ]);
        }

        return $rules;
    }

    /** ⑨-۳ — برچسب فارسی فیلدها (پیام‌های پیش‌فرض انگلیسی مثل «فیلد shaba number» جلوهٔ پنل را می‌شکند) */
    public function attributes(): array
    {
        return [
            'email' => 'ایمیل',
            'state_id' => 'استان',
            'city_id' => 'شهر',
            'address' => 'آدرس',
            'post_code' => 'کد پستی',
            'phone' => 'تلفن ثابت',
            'shaba_number' => 'شماره شبا',
            'main_supply_category_id' => 'قصد فروش کالا',
            'number_of_products' => 'تعداد تنوع کالا',
            'password' => 'رمز عبور',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'birth_day' => 'روز تولد',
            'gender' => 'جنسیت',
            'identity_card_number' => 'شماره شناسنامه',
            'national_identity_number' => 'کد ملی',
            'company_name' => 'نام شرکت',
            'company_type' => 'نوع شرکت',
            'company_registration_number' => 'شماره ثبت شرکت',
            'company_national_identity_number' => 'شناسه ملی شرکت',
            'company_economic_number' => 'شماره اقتصادی',
        ];
    }
}
