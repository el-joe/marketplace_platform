<?php

namespace App\Notifications\Marketer;

use App\Models\MarketerPayout;
use App\Notifications\BaseDatabaseBroadcastNotification;
use Illuminate\Broadcasting\PrivateChannel;

class PayoutProcessed extends BaseDatabaseBroadcastNotification
{
    public function __construct(private readonly MarketerPayout $payout) {}

    public function notificationType(): string
    {
        return 'marketer_payout_processed';
    }

    public function notificationData(object $notifiable): array
    {
        $amount   = number_format($this->payout->net_amount_cents / 100, 2);
        $currency = $this->payout->currency ?? 'SAR';

        return [
            'title'     => 'Payout Processed',
            'message'   => "Your payout of {$amount} {$currency} ({$this->payout->payout_number}) has been processed and will be transferred to your bank account.",
            'url'       => route('marketer.earnings.index'),
            'payout_id' => $this->payout->id,
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('marketer.' . $this->payout->marketer_id)];
    }
}
