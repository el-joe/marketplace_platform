<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $listing = $this->vendorListing;
        $variant = $listing?->productVariant;
        $product = $variant?->product;

        return [
            'id'                => $this->id,
            'vendor_listing_id' => $this->vendor_listing_id,
            'quantity'          => $this->quantity,
            'unit_price'        => round($this->unit_price / 100, 2),
            'line_total'        => round(($this->unit_price * $this->quantity) / 100, 2),
            'product' => [
                'name'    => $product?->name_en ?? $product?->name_ar ?? '',
                'sku'     => $variant?->sku ?? $listing?->vendor_sku ?? '',
                'image'   => $product?->images?->where('is_primary', true)->first()?->path ?? null,
            ],
            'seller_name'        => $listing?->vendor?->store_name,
            'fulfillment_model'  => $listing?->fulfillment_model,
            'max_order_quantity' => $listing?->max_order_quantity,
            'added_at'           => $this->added_at,
        ];
    }
}
