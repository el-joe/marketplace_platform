<?php

namespace App\Jobs;

use App\Models\FlashSale;
use App\Models\FlashSaleSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs every 5 minutes. Drives automatic status transitions:
 *
 *  draft         → submission_open   when submission_opens_at  ≤ now
 *  submission_open → submission_closed  when submission_closes_at ≤ now
 *  approved       → live             when sale_starts_at       ≤ now
 *  live           → ended            when sale_ends_at         ≤ now
 */
class FlashSaleSchedulerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        // 1. draft → submission_open
        FlashSale::where('status', 'draft')
            ->where('submission_opens_at', '<=', $now)
            ->each(function (FlashSale $sale) {
                try {
                    $sale->update(['status' => 'submission_open']);
                    Log::info("FlashSale {$sale->id}: draft → submission_open");
                } catch (\Throwable $e) {
                    Log::error("FlashSale {$sale->id} auto-transition failed: " . $e->getMessage());
                }
            });

        // 2. submission_open → submission_closed
        FlashSale::where('status', 'submission_open')
            ->where('submission_closes_at', '<=', $now)
            ->each(function (FlashSale $sale) {
                try {
                    $sale->update(['status' => 'submission_closed']);
                    Log::info("FlashSale {$sale->id}: submission_open → submission_closed");
                } catch (\Throwable $e) {
                    Log::error("FlashSale {$sale->id} auto-transition failed: " . $e->getMessage());
                }
            });

        // 3. approved → live
        FlashSale::where('status', 'approved')
            ->where('sale_starts_at', '<=', $now)
            ->each(function (FlashSale $sale) {
                try {
                    $sale->update(['status' => 'live']);
                    FlashSaleSubmission::where('flash_sale_id', $sale->id)
                        ->where('status', 'approved')
                        ->update(['status' => 'live']);
                    Log::info("FlashSale {$sale->id}: approved → live");
                } catch (\Throwable $e) {
                    Log::error("FlashSale {$sale->id} auto-transition failed: " . $e->getMessage());
                }
            });

        // 4. live → ended
        FlashSale::where('status', 'live')
            ->where('sale_ends_at', '<=', $now)
            ->each(function (FlashSale $sale) {
                try {
                    $sale->update(['status' => 'ended']);
                    FlashSaleSubmission::where('flash_sale_id', $sale->id)
                        ->where('status', 'live')
                        ->update(['status' => 'ended']);
                    FlashSaleAnalyticsJob::dispatch($sale);
                    Log::info("FlashSale {$sale->id}: live → ended");
                } catch (\Throwable $e) {
                    Log::error("FlashSale {$sale->id} auto-transition failed: " . $e->getMessage());
                }
            });
    }
}
