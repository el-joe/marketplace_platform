<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = [
            'ar' => $this->name_override_ar ?? $this->name_ar,
            'en' => $this->name_override_en ?? $this->name_en,
        ];

        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'name'         => $name,
            'primary_image' => $this->whenLoaded('images', function () {
                $primary = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
                return $primary ? \Storage::disk($primary->disk)->url($primary->path) : null;
            }),
            'price_range'  => [
                'min' => $this->min_price !== null ? round($this->min_price / 100, 2) : null,
                'max' => $this->max_price !== null ? round($this->max_price / 100, 2) : null,
            ],
            'rating_avg'   => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'seller_count' => (int) ($this->active_seller_count ?? $this->seller_count),
            'is_in_stock'  => (int) ($this->total_stock ?? 0) > 0,
            'is_sponsored' => (bool) ($this->is_sponsored ?? false),
            'is_wishlisted' => (bool) ($this->is_wishlisted ?? false),
        ];
    }
}
