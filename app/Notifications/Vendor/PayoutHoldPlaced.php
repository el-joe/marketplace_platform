<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class PayoutHoldPlaced extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly string $reason = '') {}

    public function notificationType(): string
    {
        return 'payout_hold_placed';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Payout Hold Placed',
            'message' => 'A hold has been placed on your payouts.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'     => route('partner.payouts.index'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
