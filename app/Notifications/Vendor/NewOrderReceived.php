<?php

namespace App\Notifications\Vendor;

use App\Models\SubOrder;
use App\Notifications\BaseDatabaseBroadcastNotification;

class NewOrderReceived extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly SubOrder $subOrder) {}

    public function notificationType(): string
    {
        return 'new_order_received';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'         => 'New Order Received',
            'message'       => "Order #{$this->subOrder->sub_order_number} has been placed and is awaiting confirmation.",
            'url'           => route('partner.orders.show', $this->subOrder->sub_order_number),
            'sub_order_id'  => $this->subOrder->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
