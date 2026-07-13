<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_id'  => $this->id,
            'currency' => $this->currency,
            'summary'  => [
                'subtotal_cents'           => $this->subtotal,
                'discount_cents'           => $this->discount,
                'estimated_shipping_cents' => $this->estimated_shipping,
                'estimated_tax_cents'      => $this->estimated_tax,
                'estimated_total_cents'    => $this->estimated_total,
                'item_count'               => $this->items->count(),
            ],
            'coupon' => $this->coupon ? [
                'code'        => $this->coupon->code,
                'type'        => $this->coupon->type?->value,
                'description' => $this->coupon->description,
            ] : null,
            'items'      => CartItemResource::collection($this->items),
            'expires_at' => $this->expires_at,
        ];
    }
}
