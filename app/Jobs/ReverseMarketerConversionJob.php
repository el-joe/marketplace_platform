<?php

namespace App\Jobs;

use App\Enums\MarketerTrackingStatus;
use App\Models\MarketerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReverseMarketerConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $orderId,
        public readonly string $reason = 'Order refunded',
    ) {
    }

    public function handle(): void
    {
        $conversions = MarketerConversion::where('order_id', $this->orderId)
            ->where('status', '!=', MarketerTrackingStatus::Paid)
            ->get();

        foreach ($conversions as $conversion) {
            $conversion->update([
                'status' => MarketerTrackingStatus::Reversed,
                'reversed_at' => now(),
                'reversal_reason' => $this->reason,
            ]);

            // Deduct from marketer's accumulated earnings
            $conversion->marketer?->decrement('total_earnings', $conversion->commission_amount);
        }
    }
}
