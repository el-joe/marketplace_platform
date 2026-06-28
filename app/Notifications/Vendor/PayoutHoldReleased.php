<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class PayoutHoldReleased extends BaseDatabaseBroadcastNotification
{
    public function notificationType(): string
    {
        return 'payout_hold_released';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Payout Hold Released',
            'message' => 'Your payout hold has been released. Payouts will resume on the next cycle.',
            'url'     => route('partner.payouts.index'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
