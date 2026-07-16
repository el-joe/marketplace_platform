<?php

namespace App\Services\Marketer;

use App\Jobs\NotifyVendorSampleRequestJob;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerSampleRequest;
use Illuminate\Support\Facades\DB;

class SampleRequestService
{
    public function create(Marketer $marketer, MarketerCampaign $campaign, array $data): MarketerSampleRequest
    {
        return DB::transaction(function () use ($marketer, $campaign, $data): MarketerSampleRequest {
            /** @var MarketerSampleRequest $sampleRequest */
            $sampleRequest = MarketerSampleRequest::create([
                'marketer_id' => $marketer->id,
                'vendor_id'   => $campaign->vendor_id,
                'campaign_id' => $campaign->id,
                'status'      => 'requested',
                'notes'       => $data['notes'] ?? null,
            ]);

            foreach ($data['vendor_listing_ids'] as $listingId) {
                $sampleRequest->items()->create([
                    'vendor_listing_id' => $listingId,
                    'quantity'          => 1,
                    'is_mandatory'      => true,
                    'sample_cost' => 0,
                    'created_at'        => now(),
                ]);
            }

            NotifyVendorSampleRequestJob::dispatch($sampleRequest);

            return $sampleRequest->load('items');
        });
    }
}
