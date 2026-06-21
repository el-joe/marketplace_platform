<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'product_id'         => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product'            => $this->whenLoaded('product', fn() =>
                (new ProductListResource($this->product))->toArray($request)
            ),
            'added_at'           => $this->added_at,
        ];
    }
}
