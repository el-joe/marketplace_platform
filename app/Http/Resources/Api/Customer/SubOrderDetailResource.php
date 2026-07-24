<?php

namespace App\Http\Resources\Api\Customer;

use App\Models\OrderItem;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subOrder = $this->resource;

        return [
            'sub_order_number' => $subOrder->sub_order_number,
            'status' => $subOrder->status?->value,
            'fulfillment_model' => $subOrder->fulfillment_model,
            'vendor' => [
                'id' => $subOrder->vendor?->id,
                'store_name' => $subOrder->vendor?->store_name,
            ],
            'subtotal' => $subOrder->subtotal,
            'shipping' => $subOrder->shipping,
            'tax' => $subOrder->tax,
            'is_exceptional_zone' => (int) ($subOrder->shipping_gap ?? 0) > 0,
            'carrier' => $subOrder->carrier?->name,
            'tracking_number' => $subOrder->tracking_number,
            'estimated_delivery_date' => $subOrder->estimated_delivery_date?->toDateString(),
            'shipped_at' => $subOrder->shipped_at?->toIso8601String(),
            'delivered_at' => $subOrder->delivered_at?->toIso8601String(),
            'items' => $subOrder->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name_en' => $item->product_snapshot['name_en'] ?? null,
                'name_ar' => $item->product_snapshot['name_ar'] ?? null,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_subtotal' => $item->line_subtotal,
                'line_discount' => $item->line_discount,
                'line_tax' => $item->line_tax,
                'line_total' => $item->line_total,
                'fulfillment_status' => $item->fulfillment_status?->value,
                'warranty' => $item->warrantyPurchase ? (new OrderWarrantySummaryResource($item->warrantyPurchase))->toArray($request) : null,
            ]),
            'shipments' => $subOrder->relationLoaded('shipments')
                ? $subOrder->shipments->map(fn (Shipment $shipment) => (new OrderShipmentResource($shipment))->toArray($request))
                : [],
        ];
    }
}
