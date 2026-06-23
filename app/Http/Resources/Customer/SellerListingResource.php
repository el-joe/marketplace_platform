<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stock = $this->warehouseInventories->sum('quantity_available');

        return [
            'id'                => $this->id,
            'seller_name'       => $this->vendor?->store_name,
            'seller_slug'       => $this->vendor?->store_slug,
            'price'             => round($this->price / 100, 2),
            'currency'          => $this->currency,
            'condition'         => $this->condition,
            'condition_notes'   => $this->condition_notes,
            'fulfillment_model' => $this->fulfillment_model,
            'delivery_estimate' => $this->fulfillment_model === 'fbn' ? '1-2 days' : '3-7 days',
            'is_in_stock'       => $stock > 0,
            'stock_level'       => $stock > 10 ? 'high' : ($stock > 0 ? 'low' : 'out_of_stock'),
            'max_order_quantity' => $this->max_order_quantity,
            'is_buy_box_winner' => $this->score !== null && $this->score > 0,
            // NOTE: cost_price and vendor_notes are NEVER exposed here
        ];
    }
}
