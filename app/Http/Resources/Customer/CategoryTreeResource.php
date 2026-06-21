<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full recursive category tree node for nav/menu responses.
 * product_count comes from the column maintained by RecalculateCategoryStatsJob.
 */
class CategoryTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'id'            => $this->id,
            'name'          => $this->{'name_' . $lang},
            'slug'          => $this->slug,
            'parent_id'     => $this->parent_id,
            'image_url'     => $this->image_url,
            'product_count' => (int) $this->product_count,
            'children'      => CategoryTreeResource::collection($this->children),
        ];
    }
}
