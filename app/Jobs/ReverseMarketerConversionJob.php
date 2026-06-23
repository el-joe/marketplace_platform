<?php

namespace App\Jobs;

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
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($conversions as $conversion) {
            $conversion->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversal_reason' => $this->reason,
            ]);

            // Deduct from marketer's accumulated earnings
            $conversion->marketer?->decrement('total_earnings_cents', $conversion->commission_amount_cents);
        }
    }
}
