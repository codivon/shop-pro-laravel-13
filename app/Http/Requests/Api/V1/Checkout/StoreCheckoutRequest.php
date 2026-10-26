<?php

namespace App\Http\Requests\Api\V1\Checkout;

use App\Models\Gateway;
use App\Services\Checkout\CheckoutService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * فاز ۹۵ — اعتبارسنجی ثبت سفارش از API (POST /api/v1/checkout).
 *
 *  • gateway ∈ wallet | درگاه‌های فعال جدول gateways | کلیدهای ماژولی فعال
 *  • آدرس: برای محصولات فیزیکی الزامی + امنیت IDOR (باید مال خود کاربر باشد)
 *  • carriers: آرایه‌ای با کلید شناسهٔ گروه (store | seller_N) → شناسهٔ روش ارسال
 *  • delivery_dates: آرایه‌ای با کلید شناسهٔ گروه → تاریخ Y-m-d (فقط user_select)
 *  • اقساطی/اعتباری: در v1 از API پشتیبانی نمی‌شود (prohibited → ۴۲۲ پیام فارسی)
 */
class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var CheckoutService $checkout */
        $checkout = app(CheckoutService::class);

        $gateways = Gateway::active()->pluck('key')->toArray();
        $allowedGateways = array_values(array_unique(array_merge(['wallet'], $gateways, $checkout->privateGatewayKeys())));

        $cart = $this->user()?->cart;

        // اگر محصول فیزیکی در سبد است، آدرس الزامی است (و باید مال خود کاربر باشد)
        $hasPhysical = $cart && $cart->products->filter(fn ($p) => $p->type === 'physical')->isNotEmpty();

        $rules = [
            'gateway' => ['required', 'in:' . implode(',', $allowedGateways)],
            'description' => ['nullable', 'string', 'max:1000'],

            // v1 — اقساطی و اعتباری از طریق API پشتیبانی نمی‌شود
            'installment_enabled' => ['prohibited'],
            'credit_enabled' => ['prohibited'],

            'carriers' => ['nullable', 'array', 'max:10'],
            'carriers.*' => ['integer', 'exists:carriers,id'],

            'delivery_dates' => ['nullable', 'array', 'max:10'],
            'delivery_dates.*' => ['nullable', 'date_format:Y-m-d'],
        ];

        $userId = $this->user()?->id ?? 0;

        if ($hasPhysical) {
            $rules['address'] = ['required', 'exists:addresses,id,user_id,' . $userId];
        } else {
            $rules['address'] = ['nullable', 'exists:addresses,id,user_id,' . $userId];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'gateway.required' => 'انتخاب روش پرداخت الزامی است.',
            'gateway.in' => 'روش پرداخت انتخاب‌شده معتبر نیست.',
            'address.required' => 'انتخاب آدرس برای محصولات فیزیکی الزامی است.',
            'address.exists' => 'آدرس انتخاب‌شده یافت نشد.',
            'carriers.array' => 'ساختار روش‌های ارسال معتبر نیست.',
            'carriers.*.integer' => 'شناسهٔ روش ارسال معتبر نیست.',
            'carriers.*.exists' => 'روش ارسال انتخاب‌شده یافت نشد.',
            'delivery_dates.array' => 'ساختار تاریخ‌های تحویل معتبر نیست.',
            'delivery_dates.*.date_format' => 'قالب تاریخ تحویل باید Y-m-d باشد.',
            'installment_enabled.prohibited' => 'پرداخت اقساطی در حال حاضر از طریق API پشتیبانی نمی‌شود.',
            'credit_enabled.prohibited' => 'خرید اعتباری در حال حاضر از طریق API پشتیبانی نمی‌شود.',
        ];
    }
}
