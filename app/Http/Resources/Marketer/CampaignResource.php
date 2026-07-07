<?php

namespace App\Http\Resources\Marketer;

use App\Models\ClassifiedListing;
use App\Models\TravelPackage;
use App\Models\Vendor;
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
            'commission_type'          => $this->commission_type,
            'attribution_model'        => $this->attribution_model,
            'whatsapp_sharing_enabled' => (bool) $this->whatsapp_sharing_enabled,
            'samples_required'         => (int) $this->samples_required,
            'budget_cents'             => $this->budget_cents,
            'budget_spent_cents'       => (int) $this->budget_spent_cents,
            'budget_remaining_cents'   => $this->getBudgetRemainingCents(),
            'tracking_url_slug'        => $this->tracking_url_slug,
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
            'campaignable_type'        => $this->campaignable_type,
            'is_admin_listing'         => $this->campaignable_type !== null && $this->campaignable_type !== Vendor::class,
            'campaignable_summary'     => $this->resolveCampaignableSummary(),
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

    private function resolveCampaignableSummary(): ?string
    {
        if (!$this->relationLoaded('campaignable') || $this->campaignable === null) {
            return null;
        }

        $campaignable = $this->campaignable;

        return match (true) {
            $campaignable instanceof ClassifiedListing => $campaignable->title_en,
            $campaignable instanceof TravelPackage     => $campaignable->title_en,
            default                                    => $campaignable->store_name ?? $campaignable->name ?? null,
        };
    }
}
