<?php

namespace App\Http\Resources\Api\Customer;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminListingResource extends JsonResource
{
    public function __construct($resource, private readonly Country $country)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $listing = $this->resource;
        $product = $listing->productVariant->product;

        return [
            'listing_id' => $listing->id,
            'price' => (int) $listing->getRawOriginal('price'),
            'currency' => $listing->currency ?? $this->country->currency_code,
            'payment_options' => $listing->payment_options,
            'fulfillment_type' => $listing->fulfillment_type,
            'is_exclusive' => (bool) $listing->is_exclusive,
            'status' => $listing->status?->value,
            'rating_avg' => (float) $listing->rating_avg,
            'rating_count' => (int) $listing->rating_count,
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name_ar' => $product->category->name_ar,
                    'name_en' => $product->category->name_en,
                ] : null,
                'images' => $product->images->map(fn ($image) => [
                    'url' => $image->url,
                    'is_primary' => (bool) $image->is_primary,
                ])->values()->all(),
            ],
            'variant' => [
                'id' => $listing->productVariant->id,
                'sku' => $listing->productVariant->sku,
                'name_ar' => $listing->productVariant->name_ar,
                'name_en' => $listing->productVariant->name_en,
            ],
        ];
    }
}
