<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'vendor_listing_id' => $this->vendor_listing_id,
            'quantity'          => (int) $this->quantity,
            'is_mandatory'      => (bool) $this->is_mandatory,
        ];
    }
}
