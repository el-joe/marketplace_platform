<?php

namespace App\Http\Resources\Customer;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassifiedListingPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'listing_number'   => $this->listing_number,
            'title_en'         => $this->title_en,
            'title_ar'         => $this->title_ar,
            'price_cents'      => $this->price_cents,
            'price_negotiable' => $this->price_negotiable,
            'primary_image'    => $this->primary_image_url,
            'city'             => $this->relationLoaded('city') ? $this->city?->{'name_' . $lang} : null,
            'seller_label'     => $this->sellerLabel(),
            'listing_purpose'  => $this->listing_purpose,
            'views_count'      => $this->views_count,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }

    private function sellerLabel(): string
    {
        if ($this->seller_type === Vendor::class) {
            if ($this->relationLoaded('seller') && $this->seller) {
                return $this->seller->store_name;
            }
            return 'Vendor';
        }

        return 'Individual';
    }
}
