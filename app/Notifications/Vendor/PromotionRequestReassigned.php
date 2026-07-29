<?php

namespace App\Notifications\Vendor;

use App\Models\VendorInfluencerPromotionRequestItem;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class PromotionRequestReassigned extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerPromotionRequestItem $newItem) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_request_reassigned';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Promotion Slot Reassigned',
            'message' => 'One of your promotion request slots was declined or timed out and has been reassigned to another influencer.',
            'promotion_request_item_id' => $this->newItem->id,
            'promotion_request_id' => $this->newItem->promotion_request_id,
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('vendor.' . $this->newItem->promotionRequest->vendor_id)];
    }
}
