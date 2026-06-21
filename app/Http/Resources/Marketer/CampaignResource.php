<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'name'                     => $this->name,
            'description'              => $this->description,
            'status'                   => $this->status,
            'campaign_type'            => $this->campaign_type,
            'commission_rate'          => $this->resolveCommissionRate(),
            'attribution_model'        => $this->attribution_model,
            'whatsapp_sharing_enabled' => (bool) $this->whatsapp_sharing_enabled,
            'samples_required'         => (int) $this->samples_required,
            'budget_cents'             => $this->budget_cents,
            'budget_spent_cents'       => (int) $this->budget_spent_cents,
            'budget_remaining_cents'   => $this->getBudgetRemainingCents(),
            'total_clicks'             => (int) $this->total_clicks,
            'total_conversions'        => (int) $this->total_conversions,
            'total_revenue_cents'      => (int) $this->total_revenue_cents,
            'conversion_rate'          => $this->getConversionRate(),
            'auto_approve_at'          => $this->auto_approve_at?->toIso8601String(),
            'auto_approved'            => (bool) $this->auto_approved,
            'starts_at'                => $this->starts_at?->toIso8601String(),
            'ends_at'                  => $this->ends_at?->toIso8601String(),
            'approved_at'              => $this->approved_at?->toIso8601String(),
            'created_at'               => $this->created_at?->toIso8601String(),
            'products'                 => $this->whenLoaded('products', fn() =>
                CampaignProductResource::collection($this->products)
            ),
        ];
    }

    private function resolveCommissionRate(): string
    {
        if ($this->secret_promotion_id && $this->relationLoaded('secretPromotion') && $this->secretPromotion) {
            return (string) $this->secretPromotion->marketer_share_pct;
        }

        return (string) $this->commission_rate;
    }
}
