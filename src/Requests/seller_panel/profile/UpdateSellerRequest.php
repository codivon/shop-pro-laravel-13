<?php

namespace Themes\SaniarTheme\src\Requests\seller_panel\profile;

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
}
