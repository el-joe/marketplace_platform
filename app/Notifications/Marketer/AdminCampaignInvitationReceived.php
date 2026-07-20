<?php

namespace App\Notifications\Marketer;

use App\Models\AdminMarketerInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class AdminCampaignInvitationReceived extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly AdminMarketerInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'admin_campaign_invitation_received';
    }

    public function notificationData(object $notifiable): array
    {
        $commission = number_format($this->invitation->commission_rate_percent, 2);
        $deadline   = $this->invitation->expires_at
            ? $this->invitation->expires_at->format('d M Y')
            : 'N/A';

        return [
            'title'         => 'New Campaign Offer From Boutiqaat',
            'message'       => "The platform has invited you to a campaign: '{$this->invitation->title}'. Commission: {$commission}%. Deadline: {$deadline}.",
            'url'           => route('marketer.admin-offers.show', $this->invitation->id),
            'invitation_id' => $this->invitation->id,
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'push'];
    }

    public function toPush(object $notifiable): array
    {
        $data = $this->notificationData($notifiable);

        return [
            'title' => $data['title'],
            'body'  => $data['message'],
            'data'  => [
                'screen' => 'admin_offer_detail',
                'id'     => $this->invitation->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->invitation->marketer_id)];
    }
}
