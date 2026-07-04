<?php

namespace App\Http\Resources\Customer;

use App\Services\Customer\ListingIdentifierService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $listing = $this->relationLoaded('bestListing') ? $this->bestListing : null;

        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'added_at'      => $this->added_at,
            'product'       => $product ? [
                'name_en'   => $product->name_en,
                'name_ar'   => $product->name_ar,
                'slug'      => $product->slug,
                'thumbnail' => $product->images?->first()?->url ?? null,
            ] : null,
            'best_listing'  => $listing ? [
                'listing_id'      => $listing->id,
                'listing_ref'     => app(ListingIdentifierService::class)->buildListingRef($listing),
                'sku'             => $listing->productVariant->sku,
                'price_cents'     => $listing->price,
                'price_formatted' => number_format($listing->price / 100, 2),
                'currency'        => $request->route('country')?->currency_code,
                'is_admin_listing' => $listing->global_system_type === 'express_fbn',
                'shipping_badge'  => $listing->primaryShippingMethod ? [
                    'label_en'          => $listing->primaryShippingMethod->badge_label_en,
                    'label_ar'          => $listing->primaryShippingMethod->badge_label_ar,
                    'color_hex'         => $listing->primaryShippingMethod->badge_color_hex,
                    'text_color_hex'    => $listing->primaryShippingMethod->badge_text_color_hex,
                    'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                    'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
                ] : null,
            ] : null,
        ];
    }
}
