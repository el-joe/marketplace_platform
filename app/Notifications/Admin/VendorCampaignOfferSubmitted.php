<?php

namespace App\Notifications\Admin;

use App\Models\VendorCampaignOffer;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class VendorCampaignOfferSubmitted extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorCampaignOffer $offer) {}

    public function notificationType(): string
    {
        return 'vendor_campaign_offer_submitted';
    }

    public function notificationData(object $notifiable): array
    {
        $vendorName    = $this->offer->vendor->store_name;
        $productsCount = $this->offer->products()->count();
        $commission    = number_format($this->offer->offered_commission_rate, 0);

        return [
            'title'          => 'Campaign Offer Pending Review',
            'message'        => "{$vendorName} submitted a campaign offer '{$this->offer->name}' for review — {$productsCount} products, {$commission}% commission offered to marketers.",
            'url'            => route('admin.vendor-campaign-offers.show', $this->offer->id),
            'offer_id'       => $this->offer->id,
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
