<?php

namespace App\Services\Marketer;

use App\Models\MarketerCampaign;
use App\Models\MarketerWhatsappLink;
use Illuminate\Support\Str;

class WhatsappLinkService
{
    public function create(MarketerCampaign $campaign, array $data): MarketerWhatsappLink
    {
        $discountPct  = $data['discount_pct'] ?? null;
        $freeShipping = (bool) ($data['free_shipping'] ?? false);

        $linkType = match (true) {
            $discountPct && $freeShipping => 'both',
            $discountPct                  => 'discount',
            $freeShipping                 => 'free_shipping',
            default                       => 'custom',
        };

        $trackingUrl = $this->buildTrackingUrl($campaign, $discountPct, $freeShipping);
        $template    = $this->buildMessageTemplate($campaign, $trackingUrl, $discountPct, $freeShipping);

        return MarketerWhatsappLink::create([
            'marketer_id'              => $campaign->marketer_id,
            'campaign_id'              => $campaign->id,
            'link_type'                => $linkType,
            'discount_pct'             => $discountPct,
            'free_shipping'            => $freeShipping,
            'coupon_code'              => null,
            'whatsapp_message_template'=> $template,
            'tracking_url'             => $trackingUrl,
            'is_active'                => true,
        ]);
    }

    private function buildTrackingUrl(
        MarketerCampaign $campaign,
        ?float $discountPct,
        bool $freeShipping,
    ): string {
        $base = rtrim(config('app.url'), '/');
        $params = [
            'ref'      => $campaign->marketer->referral_code,
            'campaign' => $campaign->id,
        ];

        if ($discountPct) {
            $params['discount'] = $discountPct;
        }
        if ($freeShipping) {
            $params['free_shipping'] = 1;
        }

        return $base . '/?' . http_build_query($params);
    }

    private function buildMessageTemplate(
        MarketerCampaign $campaign,
        string $trackingUrl,
        ?float $discountPct,
        bool $freeShipping,
    ): string {
        $incentive = '';
        if ($discountPct && $freeShipping) {
            $incentive = "{$discountPct}% off + free shipping";
        } elseif ($discountPct) {
            $incentive = "{$discountPct}% off";
        } elseif ($freeShipping) {
            $incentive = 'free shipping';
        }

        $vendorName = $campaign->vendor?->name ?? 'our store';

        return "Check out {$vendorName}! Get {$incentive} on your order. Shop now: {{link}}";
    }
}
