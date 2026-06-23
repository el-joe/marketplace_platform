<?php

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubOrderDetailResource extends JsonResource
{
    // PII reveal threshold: once status reaches processing or beyond,
    // the vendor needs the full address to fulfil the order.
    private const PII_HIDDEN_STATUSES = ['placed', 'confirmed'];

    public function toArray(Request $request): array
    {
        $address  = $this->order?->shipping_address_snapshot ?? [];
        $showFull = ! in_array($this->status, self::PII_HIDDEN_STATUSES);

        return [
            'sub_order_number'   => $this->sub_order_number,
            'status'             => $this->status,
            'fulfillment_model'  => $this->fulfillment_model,
            'tracking_number'    => $this->tracking_number,
            'carrier_id'         => $this->carrier_id,
            'sla_ship_deadline'  => $this->sla_ship_deadline?->toIso8601String(),
            'sla_breached'       => (bool) $this->sla_breached,
            'estimated_delivery' => $this->estimated_delivery_date?->toIso8601String(),
            'shipped_at'         => $this->shipped_at?->toIso8601String(),
            'delivered_at'       => $this->delivered_at?->toIso8601String(),
            'cancelled_at'       => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,

            'financials' => [
                'subtotal_cents'          => (int) $this->subtotal,
                'shipping_cents'          => (int) $this->shipping,
                'tax_cents'               => (int) $this->tax,
                'platform_commission_cents' => (int) $this->platform_commission,
                'vendor_payout_cents'     => (int) $this->vendor_payout,
            ],

            // Customer contact — PII masked until status >= processing
            'customer' => $this->buildCustomerPayload($address, $showFull),

            'items' => $this->items->map(fn ($item) => $this->buildItemPayload($item)),

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function buildCustomerPayload(array $address, bool $showFull): array
    {
        $firstName = $address['first_name'] ?? '';
        $lastName  = $address['last_name']  ?? '';

        if ($showFull) {
            return [
                'name'    => trim("{$firstName} {$lastName}"),
                'phone'   => $address['phone']   ?? null,
                'address' => $address,
            ];
        }

        // Masked: first name + last initial, city only
        $lastInitial = $lastName ? strtoupper(substr($lastName, 0, 1)) . '.' : '';

        return [
            'name'    => trim("{$firstName} {$lastInitial}"),
            'phone'   => null,
            'address' => [
                'city'    => $address['city']    ?? null,
                'country' => $address['country'] ?? null,
            ],
        ];
    }

    private function buildItemPayload($item): array
    {
        // Snapshot is the source of truth — never re-derive from live product data
        $snapshot = $item->product_snapshot ?? [];

        return [
            'id'                 => $item->id,
            'sku'                => $item->sku,
            'quantity'           => $item->quantity,
            'unit_price_cents'   => (int) $item->unit_price,
            'line_total_cents'   => (int) $item->line_total,
            'fulfillment_status' => $item->fulfillment_status,
            'return_eligible_until' => $item->return_eligible_until?->toIso8601String(),
            'product'            => [
                'name'       => $snapshot['name']        ?? null,
                'image_url'  => $snapshot['image_url']   ?? null,
                'attributes' => $snapshot['attributes']  ?? [],
            ],
        ];
    }
}
