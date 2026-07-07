<?php

namespace App\Notifications\Vendor;

use App\Models\VendorCampaignOffer;
use App\Notifications\BaseDatabaseBroadcastNotification;

class VendorCampaignOfferRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignOffer $offer) {}

    public function notificationType(): string
    {
        return 'vendor_campaign_offer_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'            => 'Campaign Offer Not Approved',
            'message'          => "Your campaign offer '{$this->offer->name}' was not approved. Reason: {$this->offer->rejection_reason}. You may edit and resubmit.",
            'url'              => route('partner.campaign-offers.show', $this->offer->id),
            'offer_id'         => $this->offer->id,
            'rejection_reason' => $this->offer->rejection_reason,
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
