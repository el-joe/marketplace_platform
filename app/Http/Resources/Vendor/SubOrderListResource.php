<?php

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $address = $this->order?->shipping_address_snapshot ?? [];

        return [
            'sub_order_number'  => $this->sub_order_number,
            'item_count'        => $this->items_count ?? $this->items->count(),
            'total_cents'       => (int) $this->subtotal,
            'status'            => $this->status,
            'fulfillment_model' => $this->fulfillment_model,
            'sla_ship_deadline' => $this->sla_ship_deadline?->toIso8601String(),
            'sla_breached'      => (bool) $this->sla_breached,
            // City only at list level — customer name/phone withheld
            'customer_city'     => $address['city'] ?? null,
            'created_at'        => $this->created_at->toIso8601String(),
        ];
    }
}
