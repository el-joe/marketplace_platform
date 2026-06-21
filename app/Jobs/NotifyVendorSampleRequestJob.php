<?php

namespace App\Jobs;

use App\Models\MarketerSampleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyVendorSampleRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly MarketerSampleRequest $sampleRequest) {}

    public function handle(): void
    {
        Log::info('New marketer sample request submitted to vendor', [
            'sample_request_id' => $this->sampleRequest->id,
            'marketer_id'       => $this->sampleRequest->marketer_id,
            'vendor_id'         => $this->sampleRequest->vendor_id,
            'campaign_id'       => $this->sampleRequest->campaign_id,
            'item_count'        => $this->sampleRequest->items()->count(),
        ]);
    }
}
