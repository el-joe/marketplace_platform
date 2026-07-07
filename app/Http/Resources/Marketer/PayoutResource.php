<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'payout_number'           => $this->payout_number,
            'period_start'            => $this->period_start?->toDateString(),
            'period_end'              => $this->period_end?->toDateString(),
            'total_conversions'       => (int) $this->total_conversions,
            'gross_commission_cents'  => (int) $this->gross_commission_cents,
            'tax_deduction_cents'     => (int) $this->tax_deduction_cents,
            'net_amount_cents'        => (int) $this->net_amount_cents,
            'currency'                => $this->currency,
            'status'                  => $this->status,
            'payment_reference'       => $this->payment_reference,
            'processed_at'            => $this->processed_at?->toIso8601String(),
            'paid_at'                 => $this->status === 'paid' ? $this->processed_at?->toIso8601String() : null,
            'created_at'              => $this->created_at?->toIso8601String(),
        ];
    }
}
