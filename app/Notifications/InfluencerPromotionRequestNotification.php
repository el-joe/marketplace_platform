<?php

namespace App\Notifications;

use App\Models\VendorInfluencerPromotionRequestItem;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Route as RouteFacade;

class InfluencerPromotionRequestNotification extends BaseDatabaseBroadcastNotification
{
    public function __construct(
        private readonly VendorInfluencerPromotionRequestItem $item,
        private readonly string $notificationType,
    ) {}

    public function notificationType(): string
    {
        return match ($this->notificationType) {
            'new_request' => 'influencer_promotion_request_received',
            'accepted' => 'influencer_promotion_request_accepted',
            'declined' => 'influencer_promotion_request_declined',
            'reassigned_away' => 'influencer_promotion_request_reassigned_away',
            'reassigned_to' => 'influencer_promotion_request_reassigned_to',
            'timeout_reassigned' => 'influencer_promotion_request_timeout_reassigned',
        };
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'promotion_request_item_id' => $this->item->id,
            'promotion_request_id' => $this->item->promotion_request_id,
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

    /**
     * Consumed by the job that dispatches the outbound WhatsApp message
     * (no gateway integration exists yet, see SendInfluencerPromotionWhatsAppNotificationJob).
     */
    public function toWhatsApp(object $notifiable): array
    {
        return [
            'to' => $notifiable->whatsapp_number,
            'message' => $this->message(),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->item->marketer_id)];
    }

    private function productName(): string
    {
        $listing = $this->item->promotionRequest->vendorListing
            ?? $this->item->promotionRequest->adminProductListing;

        return $listing?->productVariant?->product?->name_en ?? 'the product';
    }

    private function vendorName(): string
    {
        return $this->item->promotionRequest->vendor->store_name;
    }

    private function marketerName(): string
    {
        return $this->item->marketer->display_name ?: $this->item->marketer->name;
    }

    private function feeAmount(): string
    {
        return number_format((float) $this->item->slot_promotion_fee, 2) . ' ' . $this->item->promotionRequest->currency;
    }

    private function acceptUrl(): string
    {
        return $this->marketerUrl('marketer.promotion-requests.accept');
    }

    private function declineUrl(): string
    {
        return $this->marketerUrl('marketer.promotion-requests.decline');
    }

    private function marketerUrl(string $routeName): string
    {
        if (RouteFacade::has($routeName)) {
            return route($routeName, $this->item->id);
        }

        $domain = 'marketer.' . env('APP_DOMAIN', 'localhost');

        return "https://{$domain}/promotion-requests/{$this->item->id}";
    }

    private function title(): string
    {
        return match ($this->notificationType) {
            'new_request' => 'New Product Promotion Request',
            'accepted' => 'Promotion Request Accepted',
            'declined' => 'Promotion Request Declined',
            'reassigned_away' => 'Promotion Request Reassigned',
            'reassigned_to' => 'New Promotion Request Assigned to You',
            'timeout_reassigned' => 'Promotion Request Reassigned (No Response)',
        };
    }

    private function message(): string
    {
        $product = $this->productName();
        $vendor = $this->vendorName();

        return match ($this->notificationType) {
            'new_request' => sprintf(
                "%s invited you to promote \"%s\" for a slot fee of %s.\n\nAccept: %s\nDecline: %s\n\nIf you do not respond within %d hours, the system will automatically find another influencer.",
                $vendor,
                $product,
                $this->feeAmount(),
                $this->acceptUrl(),
                $this->declineUrl(),
                $this->item->acceptance_window_hours,
            ),
            'reassigned_to' => sprintf(
                "%s invited you to promote \"%s\" for a slot fee of %s (reassigned from another influencer).\n\nAccept: %s\nDecline: %s\n\nIf you do not respond within %d hours, the system will automatically find another influencer.",
                $vendor,
                $product,
                $this->feeAmount(),
                $this->acceptUrl(),
                $this->declineUrl(),
                $this->item->acceptance_window_hours,
            ),
            'accepted' => sprintf('%s accepted your promotion request for "%s".', $this->marketerName(), $product),
            'declined' => sprintf('%s declined your promotion request for "%s".', $this->marketerName(), $product),
            'reassigned_away' => sprintf('The promotion request for "%s" has been reassigned to another influencer.', $product),
            'timeout_reassigned' => sprintf('The promotion request for "%s" was not accepted in time and has been reassigned to another influencer.', $product),
        };
    }
}
