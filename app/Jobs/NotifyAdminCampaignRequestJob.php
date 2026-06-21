<?php

namespace App\Jobs;

use App\Models\MarketerCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAdminCampaignRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly MarketerCampaign $campaign) {}

    public function handle(): void
    {
        Log::info('New marketer campaign request pending admin review', [
            'campaign_id' => $this->campaign->id,
            'marketer_id' => $this->campaign->marketer_id,
            'vendor_id'   => $this->campaign->vendor_id,
            'auto_approve_at' => $this->campaign->auto_approve_at?->toIso8601String(),
        ]);
    }
}
