<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'vendor_listing_id' => $this->vendor_listing_id,
            'position'          => $this->position,
        ];
    }
}
