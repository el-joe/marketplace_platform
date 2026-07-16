<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerConversion;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class ConversionApproved extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerConversion $conversion) {}

    public function notificationType(): string
    {
        return 'marketer_conversion_approved';
    }

    public function notificationData(object $notifiable): array
    {
        $commission = number_format($this->conversion->commission_amount / 100, 2);
        $currency   = $this->conversion->currency ?? '';

        return [
            'title'         => 'Conversion Approved',
            'message'       => "A pending conversion has been approved. Commission earned: {$commission} {$currency}.",
            'url'           => route('marketer.earnings.index'),
            'conversion_id' => $this->conversion->id,
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
                'screen' => 'earnings_detail',
                'id'     => $this->conversion->id,
                'type'   => class_basename(static::class),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->conversion->marketer_id)];
    }
}
