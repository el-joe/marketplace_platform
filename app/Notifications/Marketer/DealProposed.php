<?php

namespace App\Notifications\Marketer;

use App\Models\InfluencerDeal;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class DealProposed extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly InfluencerDeal $deal) {}

    public function notificationType(): string
    {
        return 'marketer_deal_proposed';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'New Deal Proposal',
            'message' => "You have received a new deal proposal: \"{$this->deal->deal_name}\".",
            'url'     => route('marketer.dashboard'),
            'deal_id' => $this->deal->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->deal->marketer_id)];
    }
}
