<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'campaign_id'      => $this->campaign_id,
            'link_type'        => $this->link_type?->value,
            'discount_pct'     => $this->discount_pct,
            'free_shipping'    => (bool) $this->free_shipping,
            'tracking_url'     => $this->tracking_url,
            'whatsapp_url'     => $this->getWhatsappShareUrl(),
            'message_template' => $this->whatsapp_message_template,
            'share_count'      => (int) $this->share_count,
            'click_count'      => (int) $this->click_count,
            'conversion_count' => (int) $this->conversion_count,
            'is_active'        => (bool) $this->is_active,
            'expires_at'       => $this->expires_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
