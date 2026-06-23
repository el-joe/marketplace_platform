<?php

namespace App\Services\Marketer;

use App\Models\Marketer;
use App\Models\MarketerCampaign;

class TrackingLinkService
{
    public function baseLink(Marketer $marketer): string
    {
        return rtrim(config('app.url'), '/') . '/?ref=' . $marketer->referral_code;
    }

    public function campaignLink(Marketer $marketer, MarketerCampaign $campaign): string
    {
        return $this->baseLink($marketer) . '&campaign=' . $campaign->id;
    }
}
