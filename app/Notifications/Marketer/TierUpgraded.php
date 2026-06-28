<?php

namespace App\Notifications\Marketer;

use App\Models\Marketer;
use App\Models\MarketerCommissionTier;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class TierUpgraded extends BaseDatabaseBroadcastNotification
{
    // VERIFY trigger point: fire this after a conversion moves a marketer's
    // non-reversed conversion count across a tier boundary. The tier's
    // commission_rate is used here; do NOT name the underlying mechanism.
    public function __construct(
        private readonly Marketer $marketer,
        private readonly MarketerCommissionTier $newTier,
    ) {}

    public function notificationType(): string
    {
        return 'marketer_tier_upgraded';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Commission Tier Upgraded',
            'message' => "Congratulations! You've reached a new commission tier. Your commission rate is now {$this->newTier->commission_rate}%.",
            'url'     => route('marketer.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->marketer->id)];
    }
}
