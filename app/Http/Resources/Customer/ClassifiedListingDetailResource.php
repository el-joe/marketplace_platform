<?php

namespace App\Http\Resources\Customer;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassifiedListingDetailResource extends JsonResource
{
    public array $sellerInfo = [];

    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'listing_number'   => $this->listing_number,
            'slug'             => $this->slug,
            'title_en'         => $this->title_en,
            'title_ar'         => $this->title_ar,
            'description_en'   => $this->description_en,
            'description_ar'   => $this->description_ar,
            'listing_purpose'  => $this->listing_purpose,
            'price_cents'      => $this->price_cents,
            'currency'         => $this->currency,
            'price_negotiable' => (bool) $this->price_negotiable,
            'attributes'       => $this->attributes ?? [],
            'location'         => [
                'city' => $this->relationLoaded('city') ? $this->city?->{'name_' . $lang} : null,
                // lat/lng intentionally omitted from public detail — approximate area only
            ],
            'category'         => $this->relationLoaded('classifiedCategory') ? [
                'id'      => $this->classifiedCategory?->id,
                'name_en' => $this->classifiedCategory?->name_en,
                'name_ar' => $this->classifiedCategory?->name_ar,
            ] : null,
            'images'           => $this->relationLoaded('images')
                ? $this->images->map(fn ($img) => [
                    'id'       => $img->id,
                    'url'      => asset('storage/' . $img->file_path),
                    'position' => $img->position,
                ])->values()
                : [],
            'seller'           => $this->sellerInfo,
            'views_count'      => $this->views_count,
            'expires_at'       => $this->expires_at?->toDateString(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
