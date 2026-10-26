<?php

namespace App\Http\Resources\Api\V1\Checkout;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * فاز ۹۵ — ریسپانس GET /api/v1/checkout.
 * ساختار خروجی همان داده‌های CheckoutService::summary است (بدون دوباره‌کاری)
 * تا قرارداد API پایدار و مستند بماند.
 */
class CheckoutResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'items'          => $this->resource['items'] ?? [],
            'download_items' => $this->resource['download_items'] ?? [],
            'groups'         => $this->resource['groups'] ?? [],
            'totals'         => $this->resource['totals'] ?? [],
            'weight'         => $this->resource['weight'] ?? 0,
            'addresses'      => $this->resource['addresses'] ?? [],
            'payment'        => $this->resource['payment'] ?? [],
            'discount'       => $this->resource['discount'] ?? null,
            'city_id'        => $this->resource['city_id'] ?? null,
        ];
    }
}
