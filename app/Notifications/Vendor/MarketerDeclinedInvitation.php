<?php

namespace App\Notifications\Vendor;

use App\Models\VendorCampaignInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;

class MarketerDeclinedInvitation extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'marketer_declined_invitation';
    }

    public function notificationData(object $notifiable): array
    {
        $marketerName = $this->invitation->marketer->name;
        $offerName    = $this->invitation->offer->name;

        return [
            'title'         => 'Marketer Declined Invitation',
            'message'       => "{$marketerName} has declined your invitation for '{$offerName}'.",
            'invitation_id' => $this->invitation->id,
            'offer_id'      => $this->invitation->vendor_campaign_offer_id,
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
                'screen' => 'dashboard',
                'id'     => null,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
