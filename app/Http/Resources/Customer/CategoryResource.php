<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'id'        => $this->id,
            'slug'      => $this->slug,
            'name'      => $this->{'name_' . $lang},
            'image_url' => $this->image_url,
            'product_count' => (int) $this->product_count,
            'brands'    => $this->brandsInSubtree()->get()->map(fn ($brand) => [
                'id'       => $brand->id,
                'name'     => $brand->{'name_' . $lang},
                'slug'     => $brand->slug,
                'logo_url' => $brand->logo_url,
            ])->values()->all(),
            'children'  => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
