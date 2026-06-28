<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class AccountReactivated extends BaseDatabaseBroadcastNotification
{
    public function notificationType(): string
    {
        return 'account_reactivated';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Account Reactivated',
            'message' => 'Your vendor account has been reactivated. You can now resume selling on the platform.',
            'url'     => route('partner.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
