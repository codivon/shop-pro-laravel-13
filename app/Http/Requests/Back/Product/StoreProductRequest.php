<?php

namespace App\Http\Requests\Back\Product;

use App\Rules\CheckJdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'title'           => 'required|string|max:191',
            'title_en'        => 'nullable|string|max:191',
            'category_id'     => 'required|exists:categories,id',
            'image'           => 'image',
            'image_alt'       => 'nullable|string|max:191',
            'slug'            => "nullable|unique:products,slug",
            'publish_date'    => 'nullable|date',
            'special_end_date' => 'nullable|date',
            'spec_type'       => 'required_with:specification_group',
            'categories'      => 'nullable|array',
            'categories.*'    => 'exists:categories,id',
            'type'            => 'required|in:physical,download',
            'discount_expire' => 'nullable',
            'rounding_amount' => 'required|in:default,no,100,1000,10000,100000',
            'rounding_type'   => 'required|in:default,close,up,down',
            'currency_id'     => 'nullable|exists:currencies,id'
        ];

        if ($this->input('type') == 'physical') {
            $rules = array_merge($rules, [
                'weight'                => 'required|integer',
                'unit'                  => 'required|string',
                'prices'                => 'required_if:type,physical|array',
                'prices.*.warehouse'    => 'required|exists:warehouses,id',
                'prices.*.price'        => 'required|numeric|min:0',
                'prices.*.stock'        => 'required|integer',
                'prices.*.attributes'   => "nullable|array",
                'prices.*.attributes.*' => "nullable|exists:attributes,id",
                'prices.*.cart_max'     => 'nullable|integer',
                'prices.*.discount'     => 'nullable|min:0|max:100',
                'prices.*.discount_expire_at' => ['nullable', new CheckJdate('Y-m-d H:i:s')],
            ]);
        }

        if ($this->input('type') == 'download') {
            $rules = array_merge($rules, [
                'download_files'                => 'required_if:type,download|array',
                'download_files.*.title'        => 'required|string',
                'download_files.*.file'         => ['required_without:download_files.*.price_id', 'file', 'max:102400'], // امنیتی: حداکثر ۱۰۰MB
                'download_files.*.price'        => 'required|numeric|min:0',
                'download_files.*.discount'     => 'nullable|min:0|max:100',
                'download_files.*.status'       => 'required|in:active,inactive',
                'download_files.*.price_id'     => 'nullable'
            ]);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            // فیلدهای اصلی
            'title.required' => 'عنوان محصول الزامی است',
            'title.string' => 'عنوان محصول باید متن باشد',
            'title.max' => 'عنوان محصول حداکثر 191 کاراکتر می‌تواند باشد',
            'title_en.string' => 'عنوان انگلیسی باید متن باشد',
            'title_en.max' => 'عنوان انگلیسی حداکثر 191 کاراکتر می‌تواند باشد',
            'category_id.required' => 'دسته‌بندی محصول الزامی است',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'image.image' => 'فایل آپلود شده باید تصویر باشد',
            'slug.unique' => 'این Slug قبلاً ثبت شده است',
            'publish_date.date' => 'فرمت تاریخ انتشار صحیح نیست',
            'special_end_date.date' => 'فرمت تاریخ پایان ویژه صحیح نیست',
            'spec_type.required_with' => 'نوع مشخصات در صورت انتخاب گروه مشخصات الزامی است',
            'categories.array' => 'دسته‌بندی‌ها باید به صورت آرایه ارسال شوند',
            'categories.*.exists' => 'دسته‌بندی انتخاب شده معتبر نیست',
            'type.required' => 'نوع محصول الزامی است',
            'type.in' => 'نوع محصول باید فیزیکی یا دانلودی باشد',
            'rounding_amount.required' => 'مقدار گرد کردن قیمت الزامی است',
            'rounding_amount.in' => 'مقدار گرد کردن قیمت نامعتبر است',
            'rounding_type.required' => 'نوع گرد کردن قیمت الزامی است',
            'rounding_type.in' => 'نوع گرد کردن قیمت نامعتبر است',
            'currency_id.exists' => 'واحد پول انتخاب شده معتبر نیست',

            // محصولات فیزیکی
            'weight.required' => 'وزن محصول الزامی است',
            'weight.integer' => 'وزن محصول باید عدد صحیح باشد',
            'unit.required' => 'واحد وزن الزامی است',
            'prices.required_if' => 'قیمت‌ها برای محصولات فیزیکی الزامی است',
            'prices.array' => 'فرمت قیمت‌ها صحیح نیست',
            'prices.*.warehouse.required' => 'انتخاب انبار برای ردیف :position الزامی است',
            'prices.*.warehouse.exists' => 'انبار انتخاب شده برای ردیف :position معتبر نیست',
            'prices.*.price.required' => 'قیمت برای ردیف :position الزامی است',
            'prices.*.price.numeric' => 'قیمت ردیف :position باید عدد باشد',
            'prices.*.price.min' => 'قیمت ردیف :position نمی‌تواند کمتر از 0 باشد',
            'prices.*.stock.required' => 'موجودی برای ردیف :position الزامی است',
            'prices.*.stock.integer' => 'موجودی ردیف :position باید عدد صحیح باشد',
            'prices.*.attributes.array' => 'ویژگی‌های ردیف :position باید به صورت آرایه باشد',
            'prices.*.attributes.*.exists' => 'ویژگی انتخاب شده در ردیف :position معتبر نیست',
            'prices.*.cart_max.integer' => 'حداکثر خرید ردیف :position باید عدد صحیح باشد',
            'prices.*.discount.min' => 'درصد تخفیف ردیف :position نمی‌تواند کمتر از 0 باشد',
            'prices.*.discount.max' => 'درصد تخفیف ردیف :position نمی‌تواند بیشتر از 100 باشد',
            'prices.*.discount_expire_at.*' => 'فرمت تاریخ انقضای تخفیف ردیف :position صحیح نیست',

            // محصولات دانلودی
            'download_files.required_if' => 'فایل‌های دانلودی برای محصولات دانلودی الزامی است',
            'download_files.array' => 'فرمت فایل‌های دانلودی صحیح نیست',
            'download_files.*.title.required' => 'عنوان فایل دانلودی ردیف :position الزامی است',
            'download_files.*.title.string' => 'عنوان فایل دانلودی باید متن باشد',
            'download_files.*.file.required_without' => 'فایل دانلودی ردیف :position الزامی است',
            'download_files.*.file.file' => 'فایل آپلود شده در ردیف :position معتبر نیست',
            'download_files.*.price.required' => 'قیمت فایل دانلودی ردیف :position الزامی است',
            'download_files.*.price.numeric' => 'قیمت فایل دانلودی ردیف :position باید عدد باشد',
            'download_files.*.price.min' => 'قیمت فایل دانلودی ردیف :position نمی‌تواند کمتر از 0 باشد',
            'download_files.*.discount.min' => 'درصد تخفیف فایل دانلودی ردیف :position نمی‌تواند کمتر از 0 باشد',
            'download_files.*.discount.max' => 'درصد تخفیف فایل دانلودی ردیف :position نمی‌تواند بیشتر از 100 باشد',
            'download_files.*.status.required' => 'وضعیت فایل دانلودی ردیف :position الزامی است',
            'download_files.*.status.in' => 'وضعیت فایل دانلودی ردیف :position باید active یا inactive باشد',
        ];
    }
}
