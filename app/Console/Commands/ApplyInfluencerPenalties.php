<?php

namespace App\Console\Commands;

use App\Services\InfluencerMonthlyCheckService;
use Illuminate\Console\Command;

class ApplyInfluencerPenalties extends Command
{
    protected $signature = 'promotion:apply-penalties';

    protected $description = 'Apply month-end penalties to celebrities who missed minimums';

    public function handle(): void
    {
        app(InfluencerMonthlyCheckService::class)->applyMonthEndPenalties();

        $this->info('Month-end penalties applied.');
    }
}
