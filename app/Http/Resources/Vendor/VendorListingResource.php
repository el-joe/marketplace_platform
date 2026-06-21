<?php

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'status'               => $this->status,
            'condition'            => $this->condition,
            'fulfillment_model'    => $this->fulfillment_model,
            'price'                => (int) $this->price,
            'compare_at_price'     => $this->compare_at_price ? (int) $this->compare_at_price : null,
            'total_sold'           => (int) $this->total_sold,
            'rejection_reason'     => $this->rejection_reason,
            'product_variant'      => $this->whenLoaded('productVariant', fn () => [
                'id'           => $this->productVariant->id,
                'sku'          => $this->productVariant->sku,
                'variant_name' => $this->productVariant->variant_name,
                'product'      => $this->productVariant->relationLoaded('product') ? [
                    'id'      => $this->productVariant->product->id,
                    'name_en' => $this->productVariant->product->name_en,
                    'name_ar' => $this->productVariant->product->name_ar,
                ] : null,
            ]),
            'country'              => $this->whenLoaded('country', fn () => [
                'id'   => $this->country->id,
                'name' => $this->country->name,
            ]),
            'created_at'           => $this->created_at->toIso8601String(),
            'updated_at'           => $this->updated_at->toIso8601String(),
        ];
    }
}
