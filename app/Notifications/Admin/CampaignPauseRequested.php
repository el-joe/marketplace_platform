<?php

namespace App\Notifications\Admin;

use App\Models\MarketerCampaign;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class CampaignPauseRequested extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerCampaign $campaign) {}

    public function notificationType(): string
    {
        return 'marketer_campaign_pause_requested';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Campaign Pause Requested',
            'message' => "Marketer \"{$this->campaign->marketer->name}\" requested to pause campaign \"{$this->campaign->name}\".",
            'campaign_id' => $this->campaign->id,
            'link' => route('admin.marketers.campaigns.show', $this->campaign->id),
        ];
    }

    public function broadcastOn(mixed $notifiable = null): array
    {
        if (! $notifiable) {
            return [];
        }

        return [new PrivateChannel('admin.' . $notifiable->id)];
    }
}
