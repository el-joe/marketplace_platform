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
            'id'       => $this->id,
            'slug'     => $this->slug,
            'name'     => $this->{'name_' . $lang},
            'icon'     => $this->icon,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
