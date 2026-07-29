<?php

namespace App\Notifications\Marketer;

use App\Models\VendorInfluencerPromotionRequestItem;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class NewPromotionRequestReceived extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerPromotionRequestItem $item) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_request_received';
    }

    public function notificationData(object $notifiable): array
    {
        $vendorName = $this->item->promotionRequest->vendor->store_name;

        return [
            'title' => 'New Product Promotion Request',
            'message' => "{$vendorName} invited you to promote a product. Respond before it expires.",
            'promotion_request_item_id' => $this->item->id,
            'promotion_request_id' => $this->item->promotion_request_id,
            'expires_at' => $this->item->expires_at?->toIso8601String(),
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'push'];
    }

    public function toPush(object $notifiable): array
    {
        $data = $this->notificationData($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['message'],
            'data' => [
                'screen' => 'promotion_request_detail',
                'id' => $this->item->id,
                'type' => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->item->marketer_id)];
    }
}
