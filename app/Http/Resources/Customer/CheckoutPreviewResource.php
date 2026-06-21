<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subOrders = collect($this->resource['sub_orders'])->map(function ($bp) {
            return [
                'vendor_name'        => $bp['vendor']->store_name ?? '',
                'vendor_id'          => $bp['vendor_id'],
                'fulfillment_model'  => $bp['fulfillment_model'],
                'items'              => $bp['items']->map(fn($item) => [
                    'vendor_listing_id' => $item->vendor_listing_id,
                    'quantity'          => $item->quantity,
                    'unit_price'        => round($item->unit_price / 100, 2),
                    'line_total'        => round(($item->unit_price * $item->quantity) / 100, 2),
                ]),
                'subtotal'           => round($bp['subtotal'] / 100, 2),
                'shipping'           => round($bp['shipping'] / 100, 2),
                'tax'                => round($bp['tax'] / 100, 2),
            ];
        });

        return [
            'sub_orders'      => $subOrders,
            'currency'        => $this->resource['currency'],
            'subtotal'        => round($this->resource['subtotal'] / 100, 2),
            'discount'        => round($this->resource['discount'] / 100, 2),
            'shipping_total'  => round($this->resource['shipping_total'] / 100, 2),
            'tax_total'       => round($this->resource['tax_total'] / 100, 2),
            'cod_fee'         => round($this->resource['cod_fee'] / 100, 2),
            'grand_total'     => round($this->resource['grand_total'] / 100, 2),
            'locks_expire_at' => $this->resource['locks_expire_at'],
        ];
    }
}
