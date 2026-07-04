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

    public function broadcastOn(): array
    {
        return [];
    }
}
