<?php

namespace App\Notifications\Vendor;

use App\Models\VendorInfluencerPromotionRequest;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class PromotionRequestCancelled extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerPromotionRequest $request) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_request_cancelled';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Promotion Request Cancelled',
            'message' => 'No eligible influencer could be found to fulfil one of your promotion request slots, so the request was cancelled.',
            'promotion_request_id' => $this->request->id,
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
                'id' => $this->request->id,
                'type' => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('vendor.' . $this->request->vendor_id)];
    }
}
