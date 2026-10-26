<?php

namespace App\Http\Resources\Api\V1\Checkout;

use App\Http\Resources\Api\V1\Order\OrderResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * فاز ۹۵ — ریسپانس POST /api/v1/checkout: سفارش + بلوک پرداخت.
 *
 *  • paid == true (کیف پول / مبلغ صفر) → payment = null
 *  • درگاه شتابی → payment = {type: form, url, method, inputs, token}
 *  • درگاه ماژولی → payment = {type: url|html, url?|html?}
 */
class CheckoutOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order'   => new OrderResource($this->resource['order']),
            'paid'    => (bool) ($this->resource['paid'] ?? false),
            'payment' => $this->resource['payment'] ?? null,
        ];
    }
}
