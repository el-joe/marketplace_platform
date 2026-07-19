<?php

namespace App\Notifications\Admin;

use App\Models\InfluencerDeal;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class DealContentSubmitted extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly InfluencerDeal $deal) {}

    public function notificationType(): string
    {
        return 'deal_content_submitted';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Deal Content Submitted',
            'message' => "\"{$this->deal->deal_name}\" has all deliverables submitted and is ready for review.",
            'deal_id' => $this->deal->id,
            'link'    => route('admin.influencer-deals.show', $this->deal->id),
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
