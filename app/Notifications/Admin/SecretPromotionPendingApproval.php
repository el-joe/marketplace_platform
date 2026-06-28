<?php

namespace App\Notifications\Admin;

use App\Models\MarketerSecretPromotion;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

// VERIFY trigger point: fires once vendor-side secret promotion proposal flow exists.
// Currently, SecretPromotionService::create() is admin-only (status='active', no approval step).
// When vendor proposal support is added, set status='pending' and dispatch this notification.
class SecretPromotionPendingApproval extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerSecretPromotion $promotion) {}

    public function notificationType(): string
    {
        return 'secret_promotion_pending_approval';
    }

    public function notificationData(object $notifiable): array
    {
        $vendor  = $this->promotion->vendor?->store_name ?? 'Unknown vendor';
        $listing = $this->promotion->vendorListing?->productVariant?->product?->name_en
            ?? 'Unknown product';

        return [
            'title'        => 'Secret Promotion Pending Approval',
            'message'      => "Vendor \"{$vendor}\" has proposed a secret promotion for \"{$listing}\" and it requires approval.",
            'promotion_id' => $this->promotion->id,
            'link'         => route('admin.secret-promotions.show', $this->promotion->id),
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
