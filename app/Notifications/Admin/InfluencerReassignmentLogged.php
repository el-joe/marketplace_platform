<?php

namespace App\Notifications\Admin;

use App\Models\VendorInfluencerReassignmentLog;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class InfluencerReassignmentLogged extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerReassignmentLog $log) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_reassignment_logged';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Influencer Promotion Reassigned',
            'message' => "Promotion request item {$this->log->promotion_request_item_id} was reassigned ({$this->log->reason}).",
            'reassignment_log_id' => $this->log->id,
            'promotion_request_id' => $this->log->promotion_request_id,
        ];
    }

    public function broadcastOn(mixed $notifiable = null): array
    {
        if (! $notifiable) {
            return [];
        }

        return [new PrivateChannel('admin.' . $notifiable->id)];
    }
}
