<?php

namespace App\Notifications\Customer;

use App\Models\SubOrder;
use Illuminate\Broadcasting\PrivateChannel;

class OrderOutForDelivery extends BaseCustomerNotification
{
    public function __construct(private readonly SubOrder $subOrder) {}

    public function notificationType(): string
    {
        return 'order_out_for_delivery';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'            => 'Out for Delivery',
            'title_ar'         => 'الطلب في الطريق إليك',
            'message'          => "Your order #{$this->subOrder->sub_order_number} is out for delivery today.",
            'message_ar'       => "طلبك رقم #{$this->subOrder->sub_order_number} في الطريق إليك اليوم.",
            'url'              => route('customer.orders.show', $this->subOrder->order->order_number),
            'sub_order_id'     => $this->subOrder->id,
            'sub_order_number' => $this->subOrder->sub_order_number,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('customer.' . $this->subOrder->order->customer_id)];
    }
}
