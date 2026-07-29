<?php

namespace App\Jobs;

use App\Models\VendorInfluencerPromotionRequestItem;
use App\Services\InfluencerPromotionRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoReassignInfluencerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $itemId) {}

    public function handle(InfluencerPromotionRequestService $service): void
    {
        try {
            $item = VendorInfluencerPromotionRequestItem::query()->findOrFail($this->itemId);

            $service->autoReassign($item);
        } catch (Throwable $e) {
            Log::error('AutoReassignInfluencerJob failed', [
                'promotion_request_item_id' => $this->itemId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
