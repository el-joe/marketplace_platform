<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class AccountSuspended extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly string $reason = '') {}

    public function notificationType(): string
    {
        return 'account_suspended';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Account Suspended',
            'message' => 'Your vendor account has been suspended.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'     => route('partner.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
