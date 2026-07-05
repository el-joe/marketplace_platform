<?php

namespace App\Http\Resources\Customer;

use App\Services\Customer\ListingIdentifierService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $listing = $this->vendorListing;
        $variant = $listing?->productVariant;
        $product = $variant?->product;

        return [
            'id'              => $this->id,
            'added_at'        => $this->added_at,
            'listing_id'      => $listing?->id,
            'listing_ref'     => $listing ? app(ListingIdentifierService::class)->buildListingRef($listing) : null,
            'sku'             => $variant?->sku,
            'price_cents'     => $listing?->price,
            'price_formatted' => $listing ? number_format($listing->price / 100, 2) : null,
            'currency'        => $listing?->currency,
            'status'          => $listing?->status,
            'is_admin_listing' => $listing?->global_system_type === 'express_fbn',
            'product'         => $product ? [
                'id'        => $product->id,
                'name_en'   => $product->name_en,
                'name_ar'   => $product->name_ar,
                'slug'      => $product->slug,
                'thumbnail' => $product->images?->first()?->url ?? null,
            ] : null,
            'vendor'          => $listing?->vendor ? [
                'id'         => $listing->vendor->id,
                'store_name' => $listing->vendor->store_name,
            ] : null,
            'shipping_badge'  => $listing?->primaryShippingMethod ? [
                'label_en'          => $listing->primaryShippingMethod->badge_label_en,
                'label_ar'          => $listing->primaryShippingMethod->badge_label_ar,
                'color_hex'         => $listing->primaryShippingMethod->badge_color_hex,
                'text_color_hex'    => $listing->primaryShippingMethod->badge_text_color_hex,
                'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
            ] : null,
        ];
    }
}
