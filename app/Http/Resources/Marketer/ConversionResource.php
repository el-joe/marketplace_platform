<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'                      => $this->id,
            'order_ref'               => $this->maskedOrderRef(),
            'commission_rate'         => (float) $this->commission_rate,
            'commission_amount' => (int) $this->commission_amount,
            'currency'                => $this->currency,
            'status'                  => $this->status?->value,
            'attribution_model'       => $this->attribution_model?->value,
            'is_whatsapp_conversion'  => (bool) $this->is_whatsapp_conversion,
            'approved_at'             => $this->approved_at?->toIso8601String(),
            'paid_at'                 => $this->paid_at?->toIso8601String(),
            'reversed_at'             => $this->reversed_at?->toIso8601String(),
            'reversal_reason'         => $this->reversal_reason,
            'created_at'              => $this->created_at?->toIso8601String(),
        ];

        // Only surface campaign name — never secret_promotion_id or its admin_share_pct
        if ($this->relationLoaded('campaign') && $this->campaign) {
            $data['campaign_id']   = $this->campaign->id;
            $data['campaign_name'] = $this->campaign->name;
            $data['campaign'] = [
                'id'   => $this->campaign->id,
                'name' => $this->campaign->name,
            ];
        }

        return $data;
    }

    private function maskedOrderRef(): string
    {
        // Expose last 4 chars of the order_number if loaded, otherwise mask the UUID
        if ($this->relationLoaded('order') && $this->order?->order_number) {
            return '****' . substr($this->order->order_number, -4);
        }

        return '****' . substr((string) $this->order_id, -4);
    }
}
