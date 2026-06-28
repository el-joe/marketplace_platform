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
        $commission = number_format($this->conversion->commission_amount_cents / 100, 2);
        $currency   = $this->conversion->currency ?? 'SAR';

        return [
            'title'         => 'Conversion Approved',
            'message'       => "A pending conversion has been approved. Commission earned: {$commission} {$currency}.",
            'url'           => route('marketer.earnings.index'),
            'conversion_id' => $this->conversion->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->conversion->marketer_id)];
    }
}
