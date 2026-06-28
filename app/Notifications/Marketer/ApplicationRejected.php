<?php

namespace App\Notifications\Marketer;

use App\Models\Marketer;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class ApplicationRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(
        private readonly Marketer $marketer,
        private readonly string $reason = '',
    ) {}

    public function notificationType(): string
    {
        return 'marketer_application_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Application Not Approved',
            'message' => 'Unfortunately your marketer application was not approved.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'     => route('marketer.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->marketer->id)];
    }
}
