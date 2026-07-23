<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerProduct;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class ProductRejected extends BaseDatabaseBroadcastNotification
{
    public function __construct(
        private readonly MarketerProduct $product,
        private readonly string $reason = '',
    ) {}

    public function notificationType(): string
    {
        return 'marketer_product_rejected';
    }

    public function notificationData(object $notifiable): array
    {
        return [
            'title'      => 'Product Not Approved',
            'message'    => "Your product \"{$this->product->name_en}\" was not approved." . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'url'        => route('marketer.store-products.index'),
            'product_id' => $this->product->id,
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
                'screen' => 'store_product_detail',
                'id'     => $this->product->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->product->marketer_id)];
    }
}
