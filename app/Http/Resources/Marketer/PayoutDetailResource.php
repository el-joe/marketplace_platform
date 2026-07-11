<?php

namespace App\Http\Resources\Marketer;

use App\Enums\MarketerPayoutStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutDetailResource extends JsonResource
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
            'status'                  => $this->status?->value,
            'bank_name'               => $this->bank_name,
            'bank_iban'               => $this->bank_iban,
            'payment_reference'       => $this->payment_reference,
            'processed_at'            => $this->processed_at?->toIso8601String(),
            'paid_at'                 => $this->status === MarketerPayoutStatus::Paid ? $this->processed_at?->toIso8601String() : null,
            'created_at'              => $this->created_at?->toIso8601String(),
            // Conversions included in this payout (set via ->additional on the resource)
            'conversions'             => ConversionResource::collection(
                $this->whenLoaded('payoutConversions')
            ),
        ];
    }
}
