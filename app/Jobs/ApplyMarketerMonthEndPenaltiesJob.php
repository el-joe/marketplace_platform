<?php

namespace App\Jobs;

use App\Services\MarketerMonthlyQuotaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyMarketerMonthEndPenaltiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MarketerMonthlyQuotaService $service): void
    {
        $service->applyMonthEndPenalties();
        $service->initializeNewMonthProgress();
    }
}
