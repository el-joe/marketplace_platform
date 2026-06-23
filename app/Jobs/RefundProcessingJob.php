<?php

namespace App\Jobs;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Refund $refund)
    {
    }

    public function handle(): void
    {
        // TODO: Implement refund processing via payment gateway.
        // 1. Call gateway refund API with $this->refund->originalTransaction
        // 2. On success: update refund status → 'completed', store refund_transaction_id
        // 3. On failure: update refund status → 'failed', log error
    }
}
