<?php

namespace App\Notifications\Vendor;

use App\Notifications\BaseDatabaseBroadcastNotification;

class VendorApproved extends BaseDatabaseBroadcastNotification
{
    public function notificationType(): string
    {
        return 'vendor_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Account Approved',
            'message' => 'Congratulations! Your vendor account has been approved. You can now start listing products.',
            'url'     => route('partner.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
