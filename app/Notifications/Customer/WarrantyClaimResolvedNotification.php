<?php

namespace App\Notifications\Customer;

use App\Models\WarrantyClaim;
use Illuminate\Broadcasting\PrivateChannel;

class WarrantyClaimResolvedNotification extends BaseCustomerNotification
{
    private static array $resolutionLabels = [
        'repair' => 'Repair',
        'replace' => 'Replacement',
        'refund' => 'Refund',
        'no_action' => 'No Action',
    ];

    public function __construct(
        private readonly WarrantyClaim $warrantyClaim,
    ) {}

    public function notificationType(): string
    {
        return 'warranty_claim_resolved';
    }

    public function notificationData(object $notifiable): array
    {
        $resolutionLabel = self::$resolutionLabels[$this->warrantyClaim->resolution] ?? ucfirst((string) $this->warrantyClaim->resolution);

        return [
            'title' => 'Warranty Claim Resolved',
            'message' => "Your warranty claim #{$this->warrantyClaim->claim_number} has been resolved: {$resolutionLabel}.",
            'url' => route('customer.warranty-claims.show', $this->warrantyClaim->id),
            'warranty_claim_id' => $this->warrantyClaim->id,
            'claim_number' => $this->warrantyClaim->claim_number,
            'resolution' => $this->warrantyClaim->resolution,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->warrantyClaim->customer_id)];
    }
}
