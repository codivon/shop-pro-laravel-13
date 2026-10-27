<?php

namespace App\Http\Requests\SellerPanel;

use App\Models\Gateway;
use Illuminate\Foundation\Http\FormRequest;

class Store_registration_business_details extends FormRequest
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
        if($this->input('private_business')=="private") {
            $rules = [
                'first_name' => 'required|persian_alpha',
                'last_name' => 'required|persian_alpha',
                'birth_day' => 'required',
                'birth_month' => 'required',
                'birth_year' => 'required',
                'gender' => 'required',
                'identity_card_number' => 'required',
                'national_identity_number' => 'required|digits:10|unique:sellers_info',
                'state_id' => 'required',
                'city_id' => 'required',
                'address' => 'required|persian_alpha_eng_num',
                'post_code' => 'required|digits:10',
                'phone' => 'required|digits:11',
                'business_name' => 'required|unique:sellers_info|persian_alpha_eng_num',
                'shaba_number' => 'required',
                'main_supply_category_id' => 'required',
                'number_of_products' => 'required',
                'econtract' => 'required',
            ];
        }elseif($this->input('private_business')=="business") {
            $rules = [
                'company_name' => 'required|persian_alpha',
                'company_registration_number' => 'required',
                'company_national_identity_number' => 'required|digits:11',
                'company_economic_number' => 'nullable|digits:12',
                'state_id' => 'required',
                'city_id' => 'required',
                'address' => 'required|persian_alpha_num',
                'post_code' => 'required',
                'phone' => 'required',
                'business_name' => 'required|unique:sellers_info|persian_alpha_num',
                'shaba_number' => 'required',
                'main_supply_category_id' => 'required',
                'number_of_products' => 'required',
                'econtract' => 'required',
            ];
        }


        return $rules;
    }
    public function messages()
    {
        return [
            'business_name.unique' => 'نام فروشگاه قبلا انتخاب شده',
            'national_identity_number.unique' => 'کدملی قبلا ثبت شده',
        ];
    }
}
