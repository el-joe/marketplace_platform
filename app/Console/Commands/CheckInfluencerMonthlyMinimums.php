<?php

namespace App\Console\Commands;

use App\Services\InfluencerMonthlyCheckService;
use Illuminate\Console\Command;

class CheckInfluencerMonthlyMinimums extends Command
{
    protected $signature = 'promotion:check-minimums';

    protected $description = 'Send alerts to celebrities below monthly promotion minimum';

    public function handle(): void
    {
        app(InfluencerMonthlyCheckService::class)->checkMonthlyMinimums();

        $this->info('Monthly minimum check complete.');
    }
}
