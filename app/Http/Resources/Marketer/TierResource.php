<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TierResource extends JsonResource
{
    // $resource is the array produced by TierService::getProgress()
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
