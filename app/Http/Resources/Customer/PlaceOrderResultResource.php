<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Top-level payload for the placeOrder() success response.
 * Wraps an Order model with subOrders.items.vendorListing loaded.
 */
class PlaceOrderResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'total' => $this->total,
            'warranty_total' => $this->warranty_total,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'sub_orders' => PlacedSubOrderResource::collection($this->subOrders),
        ];
    }
}
