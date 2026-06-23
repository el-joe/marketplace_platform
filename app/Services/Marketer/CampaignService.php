<?php

namespace App\Services\Marketer;

use App\Jobs\MarketerAutoApproveJob;
use App\Jobs\NotifyAdminCampaignRequestJob;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function requestCampaign(Marketer $marketer, array $data): MarketerCampaign
    {
        return DB::transaction(function () use ($marketer, $data): MarketerCampaign {
            $autoApproveAt = now()->addHours(36);

            /** @var MarketerCampaign $campaign */
            $campaign = MarketerCampaign::create([
                'marketer_id'     => $marketer->id,
                'vendor_id'       => $data['vendor_id'],
                'name'            => $data['name'],
                'description'     => $data['requested_message'] ?? null,
                'campaign_type'   => 'product_promotion',
                'status'          => 'draft',
                'commission_rate' => $marketer->commission_rate ?? 0,
                'commission_type' => 'percentage',
                'starts_at'       => now(),
                'ends_at'         => now()->addDays(90),
                'auto_approve_at' => $autoApproveAt,
            ]);

            foreach ($data['product_listing_ids'] as $position => $listingId) {
                $campaign->products()->create([
                    'vendor_listing_id' => $listingId,
                    'position'          => $position,
                ]);
            }

            MarketerAutoApproveJob::dispatch($campaign->id)->delay($autoApproveAt);
            NotifyAdminCampaignRequestJob::dispatch($campaign);

            return $campaign;
        });
    }
}
