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

    public function broadcastOn(): array
    {
        return [];
    }
}
