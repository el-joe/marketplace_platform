<?php

namespace App\Notifications\Vendor;

use App\Models\VendorCampaignInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;

class MarketerAcceptedInvitation extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'marketer_accepted_invitation';
    }

    public function notificationData(object $notifiable): array
    {
        $marketerName = $this->invitation->marketer->name;
        $offerName    = $this->invitation->offer->name;

        return [
            'title'         => 'Marketer Accepted Invitation',
            'message'       => "{$marketerName} has accepted your campaign invitation for '{$offerName}' and is now promoting your products.",
            'url'           => route('partner.campaign-offers.show', $this->invitation->vendor_campaign_offer_id),
            'invitation_id' => $this->invitation->id,
            'offer_id'      => $this->invitation->vendor_campaign_offer_id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
