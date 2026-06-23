<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReferralRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Customer $referrer,
        public readonly Customer $referred,
    ) {}

    public function handle(): void
    {
        // TODO: implement reward logic (loyalty points, discount voucher, etc.)
        // Reward amounts and rules are defined in the admin reward configuration.
    }
}
