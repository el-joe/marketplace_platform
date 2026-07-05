<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $method = $this->resource['method'];

        return [
            'id' => $method->id,
            'code' => $method->code,
            'name' => $method->name,
            'description' => $method->description,
            'is_express_type' => $method->is_express_type,
            'delivery_days_min' => $method->min_delivery_days,
            'delivery_days_max' => $method->max_delivery_days,
            'delivery_label_en' => $method->delivery_label_en,
            'delivery_label_ar' => $method->delivery_label_ar,
            'badge_label_en' => $method->badge_label_en,
            'badge_label_ar' => $method->badge_label_ar,
            'badge_color_hex' => $method->badge_color_hex,
            'badge_text_color_hex' => $method->badge_text_color_hex,
            'show_estimated_price' => $method->show_estimated_price,
            'currency' => $this->resource['currency'],
            'cost' => round($this->resource['cost_cents'] / 100, 2),
            'is_free' => $this->resource['is_free'],
            'cod_extra_fee' => round($this->resource['cod_extra_fee_cents'] / 100, 2),
            'free_shipping_threshold' => $this->resource['free_shipping_threshold_cents'] !== null
                ? round($this->resource['free_shipping_threshold_cents'] / 100, 2)
                : null,
        ];
    }
}
