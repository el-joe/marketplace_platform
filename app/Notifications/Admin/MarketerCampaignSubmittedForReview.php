<?php

namespace App\Notifications\Admin;

use App\Models\MarketerCampaign;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class MarketerCampaignSubmittedForReview extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerCampaign $campaign) {}

    public function notificationType(): string
    {
        return 'marketer_campaign_submitted_for_review';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'       => 'Marketer Campaign Pending Review',
            'message'     => "Marketer campaign \"{$this->campaign->name}\" has been submitted and requires review.",
            'campaign_id' => $this->campaign->id,
            'link'        => route('admin.marketers.campaigns.show', $this->campaign->id),
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
