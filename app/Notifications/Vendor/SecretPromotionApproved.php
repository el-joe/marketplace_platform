<?php

namespace App\Notifications\Vendor;

use App\Models\SecretPromotion;
use App\Notifications\BaseDatabaseBroadcastNotification;

class SecretPromotionApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly SecretPromotion $promotion) {}

    public function notificationType(): string
    {
        return 'secret_promotion_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'        => 'Secret Promotion Approved',
            'message'      => "Your secret promotion has been approved and is now active.",
            'url'          => route('partner.dashboard'),
            'promotion_id' => $this->promotion->id,
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
            'body'  => $data['message'],
            'data'  => [
                'screen' => 'dashboard',
                'id'     => null,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
