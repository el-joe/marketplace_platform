<?php

use App\Jobs\AutoCompleteOrdersJob;
use App\Jobs\CheckSlaBreachJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckSlaBreachJob)->everyFifteenMinutes();
Schedule::job(new AutoCompleteOrdersJob)->dailyAt('02:00');
