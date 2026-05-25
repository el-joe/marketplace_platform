<?php

use App\Jobs\AutoCompleteOrdersJob;
use App\Jobs\CheckSlaBreachJob;
use App\Jobs\GenerateVendorPayoutsJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckSlaBreachJob)->everyFifteenMinutes();
Schedule::job(new AutoCompleteOrdersJob)->dailyAt('02:00');

// Generate vendor payouts every Monday at 06:00 for the previous week (Mon–Sun)
Schedule::call(function () {
    $periodEnd   = Carbon::now()->startOfWeek(Carbon::MONDAY)->subDay()->endOfDay();  // last Sunday
    $periodStart = $periodEnd->copy()->startOfWeek(Carbon::MONDAY)->subWeek();        // Monday before last
    GenerateVendorPayoutsJob::dispatch($periodStart->startOfDay(), $periodEnd);
})->weeklyOn(Carbon::MONDAY, '06:00');
