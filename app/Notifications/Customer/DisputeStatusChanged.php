<?php

namespace App\Notifications\Customer;

use App\Models\Dispute;
use Illuminate\Broadcasting\PrivateChannel;

class DisputeStatusChanged extends BaseCustomerNotification
{
    private static array $statusLabels = [
        'open'             => 'Open',
        'seller_responded' => 'Seller Responded',
        'under_review'     => 'Under Review',
        'escalated'        => 'Escalated',
        'resolved'         => 'Resolved',
        'closed'           => 'Closed',
    ];

    public function __construct(
        private readonly Dispute $dispute,
        private readonly string $previousStatus,
    ) {}

    public function notificationType(): string
    {
        return 'dispute_status_changed';
    }

    public function notificationData(object $notifiable): array
    {
        $newLabel = self::$statusLabels[$this->dispute->status] ?? ucfirst($this->dispute->status);

        return [
            'title'           => 'Dispute Update',
            'message'         => "Your dispute #{$this->dispute->dispute_number} status changed to: {$newLabel}.",
            'url'             => route('customer.disputes.show', $this->dispute->dispute_number),
            'dispute_id'      => $this->dispute->id,
            'dispute_number'  => $this->dispute->dispute_number,
            'status'          => $this->dispute->status,
            'previous_status' => $this->previousStatus,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->dispute->customer_id)];
    }
}
