<?php

namespace App\Notifications;

use App\Models\VendorInfluencerPromotionRequest;
use Illuminate\Broadcasting\PrivateChannel;

class VendorFulfillmentAlertNotification extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly VendorInfluencerPromotionRequest $request) {}

    public function notificationType(): string
    {
        return 'influencer_promotion_fbp_fbm_fulfillment_required';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => 'Ship Products to Warehouse',
            'message' => $this->message(),
            'promotion_request_id' => $this->request->id,
        ];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->notificationData($notifiable);
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'to' => $notifiable->whatsapp_number,
            'message' => $this->message(),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('vendor.' . $this->request->vendor_id)];
    }

    private function referenceCode(): string
    {
        return strtoupper(substr(str_replace('-', '', $this->request->id), 0, 8));
    }

    private function warehouseAddress(): string
    {
        $address = $this->request->vendor->defaultWarehouse?->address;

        if (! $address) {
            return 'our fulfillment warehouse (contact support for the address)';
        }

        return collect([
            $address->street_address,
            $address->building,
            $address->area,
            $address->city?->name_en,
            $address->country?->name_en,
        ])->filter()->implode(', ');
    }

    private function message(): string
    {
        return sprintf(
            "Your promotion request has been submitted. Since your products are under FBP/FBM fulfillment, you must ship the promotional products to our warehouse before the promotion can begin.\n\nWarehouse address: %s\nReference: %s",
            $this->warehouseAddress(),
            $this->referenceCode(),
        );
    }
}
