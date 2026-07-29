<?php

namespace App\Jobs;

use App\Models\VendorInfluencerPromotionRequestItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CheckInfluencerPromotionTimeoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        VendorInfluencerPromotionRequestItem::query()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($items) {
                DB::transaction(function () use ($items) {
                    foreach ($items as $item) {
                        $item->update(['status' => 'timed_out']);

                        AutoReassignInfluencerJob::dispatch($item->id);
                    }
                });
            });
    }
}
