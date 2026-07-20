<?php

namespace App\Notifications\Admin;

use App\Models\AdminMarketerInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class AdminMarketerInvitationAccepted extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly AdminMarketerInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'admin_marketer_invitation_accepted';
    }

    public function notificationData(object $notifiable): array
    {
        $marketerName = $this->invitation->marketer->name;

        return [
            'title'         => 'Marketer Accepted Campaign Invitation',
            'message'       => "{$marketerName} has accepted your campaign invitation '{$this->invitation->title}' and is now promoting it.",
            'url'           => route('admin.marketers.all.show', $this->invitation->marketer_id),
            'invitation_id' => $this->invitation->id,
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
