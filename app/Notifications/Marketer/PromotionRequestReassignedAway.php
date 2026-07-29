<?php

namespace App\Notifications\Marketer;

use App\Models\VendorInfluencerPromotionRequestItem;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class PromotionRequestReassignedAway extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerPromotionRequestItem $item) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_request_reassigned_away';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Promotion Request Reassigned',
            'message' => 'A product promotion request you did not respond to in time has been reassigned to another influencer.',
            'promotion_request_item_id' => $this->item->id,
            'promotion_request_id' => $this->item->promotion_request_id,
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->item->marketer_id)];
    }
}
