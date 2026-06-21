<?php

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'today_revenue_cents'        => $data['today_revenue_cents'],
            'pending_orders_count'       => $data['pending_orders_count'],
            'low_stock_count'            => $data['low_stock_count'],
            'unread_notifications_count' => $data['unread_notifications_count'],

            'recent_orders' => collect($data['recent_orders'])->map(fn ($so) => [
                'sub_order_number' => $so->sub_order_number,
                'item_count'       => $so->items->count(),
                'total_cents'      => (int) $so->subtotal,
                'status'           => $so->status,
                'sla_ship_deadline' => $so->sla_ship_deadline?->toIso8601String(),
                'created_at'       => $so->created_at->toIso8601String(),
            ]),

            'performance' => [
                'on_time_ship_rate'  => $data['performance']['on_time_ship_rate'],
                'cancellation_rate'  => $data['performance']['cancellation_rate'],
                'avg_rating'         => $data['performance']['avg_rating'],
            ],

            'sla_breaches' => [
                'count' => $data['sla_breaches']['count'],
            ],
        ];
    }
}
