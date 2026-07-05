<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Travel category tree node, shaped to match CategoryTreeResource so both
 * can be merged into a single customer-facing category array.
 */
class TravelCategoryTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'id'        => $this->id,
            'type'      => 'travel',
            'name'      => $this->{'name_' . $lang},
            'slug'      => $this->slug,
            'parent_id' => $this->parent_id,
            'icon'      => $this->icon,
            'children'  => TravelCategoryTreeResource::collection($this->whenLoaded('children'))->resolve(),
        ];
    }
}
