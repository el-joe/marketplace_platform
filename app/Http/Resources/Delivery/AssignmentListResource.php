<?php

namespace App\Http\Resources\Delivery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'failed_at'   => $this->failed_at?->toIso8601String(),
            'sub_order'   => $this->whenLoaded('subOrder', fn () => [
                'id'              => $this->subOrder->id,
                'sub_order_number' => $this->subOrder->sub_order_number,
                'status'          => $this->subOrder->status,
            ]),
            'shipment'    => $this->whenLoaded('shipment', fn () => [
                'id'              => $this->shipment->id,
                'tracking_number' => $this->shipment->tracking_number,
                'status'          => $this->shipment->status,
            ]),
        ];
    }
}
