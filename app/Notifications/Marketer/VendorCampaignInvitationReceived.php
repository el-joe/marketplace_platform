<?php

namespace App\Notifications\Marketer;

use App\Models\VendorCampaignInvitation;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class VendorCampaignInvitationReceived extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignInvitation $invitation) {}

    public function notificationType(): string
    {
        return 'vendor_campaign_invitation_received';
    }

    public function notificationData(object $notifiable): array
    {
        $offer      = $this->invitation->offer;
        $vendorName = $offer->vendor->store_name;
        $commission = number_format($offer->offered_commission_rate, 0);
        $deadline   = $offer->invitation_deadline
            ? $offer->invitation_deadline->format('d M Y')
            : 'N/A';

        return [
            'title'         => 'New Campaign Invitation',
            'message'       => "{$vendorName} has invited you to promote their products! Commission: {$commission}%. Deadline: {$deadline}.",
            'url'           => route('marketer.invitations.show', $this->invitation->id),
            'invitation_id' => $this->invitation->id,
            'offer_id'      => $this->invitation->vendor_campaign_offer_id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->invitation->marketer_id)];
    }
}
