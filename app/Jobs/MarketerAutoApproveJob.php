<?php

namespace App\Jobs;

use App\Models\MarketerCampaign;
use App\Models\MarketerSampleRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarketerAutoApproveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?string $campaignId,
        public readonly ?string $sampleRequestId = null,
    ) {
    }

    public function handle(): void
    {
        // ── Campaign auto-approval ────────────────────────────────────────────
        if ($this->campaignId) {
            $campaign = MarketerCampaign::find($this->campaignId);

            if (!$campaign) {
                return;
            }

            // Admin already acted — skip
            if ($campaign->status !== 'draft') {
                return;
            }

            if (now() < $campaign->auto_approve_at) {
                return;
            }

            $campaign->update([
                'status' => 'active',
                'auto_approved' => 1,
                'approved_at' => now(),
            ]);
        }

        // ── Sample request auto-approval ──────────────────────────────────────
        if ($this->sampleRequestId) {
            $sampleRequest = MarketerSampleRequest::find($this->sampleRequestId);

            if ($sampleRequest && $sampleRequest->status === 'requested') {
                $sampleRequest->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                ]);
            }
        }
    }
}
