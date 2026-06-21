<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketerDashboardResource extends JsonResource
{
    // $resource is the plain array returned by DashboardService::getSnapshot()
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
