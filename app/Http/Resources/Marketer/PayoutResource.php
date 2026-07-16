<?php

namespace App\Http\Resources\Marketer;

use App\Enums\MarketerPayoutStatus;
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
            'gross_commission'  => (int) $this->gross_commission,
            'tax_deduction'     => (int) $this->tax_deduction,
            'net_amount'        => (int) $this->net_amount,
            'currency'                => $this->currency,
            'status'                  => $this->status?->value,
            'payment_reference'       => $this->payment_reference,
            'processed_at'            => $this->processed_at?->toIso8601String(),
            'paid_at'                 => $this->status === MarketerPayoutStatus::Paid ? $this->processed_at?->toIso8601String() : null,
            'created_at'              => $this->created_at?->toIso8601String(),
        ];
    }
}
