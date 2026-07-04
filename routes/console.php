<?php

use App\Jobs\AutoCompleteOrdersJob;
use App\Jobs\ExpireVendorCampaignInvitationsJob;
use App\Jobs\GenerateCodSettlementsJob;
use App\Jobs\ReleaseExpiredLocksJob;
use App\Jobs\ApproveMarketerCommissionsJob;
use App\Jobs\CheckSlaBreachJob;
use App\Jobs\GenerateVendorPayoutsJob;
use App\Jobs\BannerSchedulerJob;
use App\Jobs\FlashSaleSchedulerJob;
use App\Jobs\TransitionFlashSaleStatusJob;
use App\Jobs\GenerateFbnStorageFeesJob;
use App\Jobs\FbnInboundReminderJob;
use App\Jobs\PublishScheduledBlogPostsJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ReleaseExpiredLocksJob)->everyMinute()->withoutOverlapping()->name('release-expired-locks');
Schedule::job(new CheckSlaBreachJob)->everyFifteenMinutes();
Schedule::job(new AutoCompleteOrdersJob)->dailyAt('02:00');
Schedule::job(new TransitionFlashSaleStatusJob)->everyFiveMinutes();
Schedule::job(new FlashSaleSchedulerJob)->everyFiveMinutes()->withoutOverlapping()->name('flash-sale-scheduler');
Schedule::job(new BannerSchedulerJob)->everyFiveMinutes();
Schedule::job(new \App\Jobs\PageSchedulerJob)->everyFiveMinutes()->name('page-scheduler');
Schedule::job(new PublishScheduledBlogPostsJob)->everyFiveMinutes()->name('publish-scheduled-blog-posts');

// Auto-approve marketer commissions after 14-day return window
Schedule::job(new ApproveMarketerCommissionsJob)->dailyAt('03:00')->name('marketer-approve-commissions');

// Generate vendor payouts every Monday at 06:00 for the previous week (Mon–Sun)
Schedule::call(function () {
    $periodEnd = Carbon::now()->startOfWeek(Carbon::MONDAY)->subDay()->endOfDay();  // last Sunday
    $periodStart = $periodEnd->copy()->startOfWeek(Carbon::MONDAY)->subWeek();        // Monday before last
    GenerateVendorPayoutsJob::dispatch($periodStart->startOfDay(), $periodEnd);
})->weeklyOn(Carbon::MONDAY, '06:00');

Schedule::job(new \App\Jobs\AggregateAnalyticsCacheJob)->hourly()->name('aggregate-analytics-cache');

// FBN: generate storage fees on the 1st of each month at 07:00
Schedule::call(function () {
    GenerateFbnStorageFeesJob::dispatch(now()->format('Y-m'));
})->monthlyOn(1, '07:00')->name('fbn-generate-storage-fees');

// FBN: remind admins of pending inbound requests every Monday at 08:00
Schedule::job(new FbnInboundReminderJob)->weeklyOn(1, '08:00')->name('fbn-inbound-reminder');

// Generate COD settlements for delivery agents nightly at 23:30
Schedule::job(new GenerateCodSettlementsJob)->dailyAt('23:30')->name('generate-cod-settlements');

// Expire pending vendor campaign invitations past their deadline
Schedule::job(new ExpireVendorCampaignInvitationsJob)->dailyAt('00:15')->name('expire-vendor-campaign-invitations');
