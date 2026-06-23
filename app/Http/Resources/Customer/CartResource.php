<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'currency'            => $this->currency,
            'item_count'          => $this->items->count(),
            'items'               => CartItemResource::collection($this->items),
            'subtotal'            => round($this->subtotal / 100, 2),
            'discount'            => round($this->discount / 100, 2),
            'estimated_shipping'  => round($this->estimated_shipping / 100, 2),
            'estimated_tax'       => round($this->estimated_tax / 100, 2),
            'estimated_total'     => round($this->estimated_total / 100, 2),
            'coupon'              => $this->when($this->coupon, fn() => [
                'code'        => $this->coupon->code,
                'type'        => $this->coupon->type,
                'description' => $this->coupon->description,
            ]),
        ];
    }
}
