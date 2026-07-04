<?php

namespace App\Jobs;

use App\Models\MarketerCampaign;
use App\Models\MarketerSampleRequest;
use App\Notifications\Marketer\SampleRequestApproved as MarketerSampleRequestApproved;
use App\Services\SampleQuotaResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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

            if ($campaign->status !== 'draft') {
                return;
            }

            if (now() < $campaign->auto_approve_at) {
                return;
            }

            $campaign->update([
                'status'        => 'active',
                'auto_approved' => 1,
                'approved_at'   => now(),
            ]);
        }

        // ── Sample request auto-approval ──────────────────────────────────────
        if ($this->sampleRequestId) {
            $sampleRequest = MarketerSampleRequest::with('items')->find($this->sampleRequestId);

            if (!$sampleRequest || $sampleRequest->status !== 'requested') {
                return;
            }

            DB::transaction(function () use ($sampleRequest) {
                $category = app(SampleQuotaResolver::class)->resolveFromRequest($sampleRequest);

                if ($category && $category->admin_sample_quota > 0) {
                    // Distribute admin quota across existing item rows (one row per listing).
                    // admin_quantity is kept hidden from marketer and vendor; only quantity
                    // (the combined total) is exposed to the vendor.
                    $items = $sampleRequest->items;
                    $remaining = (int) $category->admin_sample_quota;
                    $count = $items->count();

                    foreach ($items as $i => $item) {
                        $share = $count > 0 ? intdiv($remaining, $count - $i) : 0;
                        $remaining -= $share;
                        $item->update([
                            'admin_quantity' => $share,
                            'quantity'       => $item->marketer_quantity + $share,
                        ]);
                    }
                }

                $sampleRequest->update([
                    'status'            => 'approved',
                    // admin_approved_by left null — no human actor for system approvals.
                    // TODO: set to a dedicated automation-admin account ID for cleaner audit trails.
                    'admin_approved_by' => null,
                    'approved_at'       => now(),
                ]);
            });

            $sampleRequest->marketer->notify(new MarketerSampleRequestApproved($sampleRequest));
        }
    }
}
