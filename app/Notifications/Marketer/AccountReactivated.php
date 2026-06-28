<?php

namespace App\Notifications\Marketer;

use App\Models\Marketer;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class AccountReactivated extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly Marketer $marketer) {}

    public function notificationType(): string
    {
        return 'marketer_account_reactivated';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Account Reactivated',
            'message' => 'Your marketer account has been reactivated. Welcome back!',
            'url'     => route('marketer.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->marketer->id)];
    }
}
