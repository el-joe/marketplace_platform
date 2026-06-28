<?php

namespace App\Notifications\Vendor;

use App\Models\AdCampaign;
use App\Notifications\BaseDatabaseBroadcastNotification;

class AdCampaignApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly AdCampaign $campaign) {}

    public function notificationType(): string
    {
        return 'ad_campaign_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'       => 'Ad Campaign Approved',
            'message'     => "Your ad campaign \"{$this->campaign->name}\" has been approved and is now live.",
            'url'         => route('partner.ads.show', $this->campaign->id),
            'campaign_id' => $this->campaign->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
