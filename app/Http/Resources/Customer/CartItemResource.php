<?php

namespace App\Http\Resources\Customer;

use App\Services\Customer\ListingIdentifierService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $listing = $this->vendorListing;
        $variant = $listing?->productVariant;
        $product = $variant?->product;
        $available = $listing?->warehouseInventories->sum('quantity_available') ?? 0;

        return [
            'cart_item_id'       => $this->id,
            'listing_id'         => $listing?->id,
            'listing_ref'        => $listing ? app(ListingIdentifierService::class)->buildListingRef($listing) : null,
            'sku'                => $variant?->sku,
            'vendor_sku'         => $listing?->vendor_sku,
            'name_en'            => $product?->name_en,
            'name_ar'            => $product?->name_ar,
            'thumbnail'          => $product?->images?->firstWhere('is_primary', true)?->url
                                        ?? $product?->images?->first()?->url,
            'unit_price_cents'   => $this->unit_price,
            'quantity'           => $this->quantity,
            'line_total_cents'   => $this->unit_price * $this->quantity,
            'max_order_quantity' => $listing?->max_order_quantity,
            'vendor'             => $listing?->vendor ? [
                'id'         => $listing->vendor->id,
                'store_name' => $listing->vendor->store_name,
            ] : null,
            'is_admin_listing' => $listing?->global_system_type === 'express_fbn',
            'shipping_badge'   => $listing?->primaryShippingMethod ? [
                'label_en'          => $listing->primaryShippingMethod->badge_label_en,
                'label_ar'          => $listing->primaryShippingMethod->badge_label_ar,
                'color_hex'         => $listing->primaryShippingMethod->badge_color_hex,
                'text_color_hex'    => $listing->primaryShippingMethod->badge_text_color_hex,
                'delivery_days_min' => $listing->primaryShippingMethod->min_delivery_days,
                'delivery_days_max' => $listing->primaryShippingMethod->max_delivery_days,
            ] : null,
            'in_stock'      => $listing ? $available >= $this->quantity : false,
            'price_changed' => (bool) ($this->price_changed ?? false),
        ];
    }
}
