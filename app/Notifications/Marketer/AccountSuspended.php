<?php

namespace App\Notifications\Marketer;

use App\Models\Marketer;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class AccountSuspended extends BaseDatabaseBroadcastNotification
{
    public function __construct(
        private readonly Marketer $marketer,
        private readonly string $reason = '',
    ) {}

    public function notificationType(): string
    {
        return 'marketer_account_suspended';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Account Suspended',
            'message' => 'Your marketer account has been suspended.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'     => route('marketer.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->marketer->id)];
    }
}
