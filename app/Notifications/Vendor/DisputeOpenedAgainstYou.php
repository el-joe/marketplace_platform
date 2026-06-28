<?php

namespace App\Notifications\Vendor;

use App\Models\Dispute;
use App\Notifications\BaseDatabaseBroadcastNotification;

class DisputeOpenedAgainstYou extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly Dispute $dispute) {}

    public function notificationType(): string
    {
        return 'dispute_opened_against_you';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'      => 'Dispute Opened Against Your Order',
            'message'    => "A customer has opened dispute #{$this->dispute->dispute_number} against one of your orders. Please respond promptly.",
            'url'        => route('partner.disputes.show', $this->dispute->dispute_number),
            'dispute_id' => $this->dispute->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
