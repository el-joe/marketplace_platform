<?php

namespace App\Policies;

use App\Enums\MarketerCampaignStatus;
use App\Models\Marketer;
use App\Models\MarketerCampaign;

class CampaignPolicy
{
    public function view(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }

    public function update(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }

    public function generateTrackingLink(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id
            && $campaign->status === MarketerCampaignStatus::Active;
    }

    public function viewAnalytics(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }

    public function manageWhatsappLinks(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }

    public function manageSamples(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }

    public function viewConversions(Marketer $marketer, MarketerCampaign $campaign): bool
    {
        return $campaign->marketer_id === $marketer->id;
    }
}
