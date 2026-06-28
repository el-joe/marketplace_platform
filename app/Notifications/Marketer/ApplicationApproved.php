<?php

namespace App\Notifications\Marketer;

use App\Models\Marketer;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class ApplicationApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly Marketer $marketer) {}

    public function notificationType(): string
    {
        return 'marketer_application_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'   => 'Application Approved',
            'message' => 'Welcome! Your marketer application has been approved. You can now create campaigns and start earning.',
            'url'     => route('marketer.dashboard'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->marketer->id)];
    }
}
