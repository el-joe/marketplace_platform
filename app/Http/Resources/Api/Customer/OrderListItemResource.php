<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'currency' => $this->currency,
            'total' => $this->total,
            'sub_orders_count' => $this->sub_orders_count,
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }
}
