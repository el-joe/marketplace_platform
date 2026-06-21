<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'campaign_id'      => $this->campaign_id,
            'status'           => $this->status,
            'notes'            => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'dispatched_at'    => $this->dispatched_at?->toIso8601String(),
            'received_at'      => $this->received_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'items'            => $this->whenLoaded('items', fn() =>
                SampleItemResource::collection($this->items)
            ),
        ];
    }
}
