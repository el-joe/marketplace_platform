<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingLinkResource extends JsonResource
{
    // $resource is an array: ['url' => ..., 'referral_code' => ..., ...]
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
