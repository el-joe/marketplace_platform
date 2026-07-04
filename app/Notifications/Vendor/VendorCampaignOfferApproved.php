<?php

namespace App\Notifications\Vendor;

use App\Models\VendorCampaignOffer;
use App\Notifications\BaseDatabaseBroadcastNotification;

class VendorCampaignOfferApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignOffer $offer) {}

    public function notificationType(): string
    {
        return 'vendor_campaign_offer_approved';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'    => 'Campaign Offer Approved',
            'message'  => "Your campaign offer '{$this->offer->name}' has been approved! Invited marketers will now receive their invitations.",
            'url'      => route('partner.campaign-offers.show', $this->offer->id),
            'offer_id' => $this->offer->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
