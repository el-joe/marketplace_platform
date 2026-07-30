<?php

namespace App\Console\Commands;

use App\Services\InfluencerMonthlyCheckService;
use Illuminate\Console\Command;

class ExpirePromotionRequestItems extends Command
{
    protected $signature = 'promotion:expire-items';

    protected $description = 'Expire overdue promotion request items and trigger auto-reassignment';

    public function handle(): void
    {
        $count = app(InfluencerMonthlyCheckService::class)->expireOverdueItems();

        $this->info("Expired and reassigned {$count} overdue promotion request items.");
    }
}
