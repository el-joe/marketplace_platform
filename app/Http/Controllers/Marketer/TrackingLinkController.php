<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketer\TrackingLinkResource;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Policies\CampaignPolicy;
use App\Services\Marketer\TrackingLinkService;
use Illuminate\Http\JsonResponse;

class TrackingLinkController extends Controller
{
    public function __construct(private readonly TrackingLinkService $service) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/tracking-link
    public function base(): JsonResponse
    {
        $marketer = $this->marketer();

        return ApiResponse::success(new TrackingLinkResource([
            'referral_code' => $marketer->referral_code,
            'url'           => $this->service->baseLink($marketer),
        ]));
    }

    // GET /api/marketer/v1/campaigns/{campaign}/tracking-link
    public function forCampaign(MarketerCampaign $campaign): JsonResponse
    {
        $marketer = $this->marketer();

        $this->authorizeWithGuard($marketer, 'generateTrackingLink', $campaign);

        return ApiResponse::success(new TrackingLinkResource([
            'referral_code' => $marketer->referral_code,
            'campaign_id'   => $campaign->id,
            'url'           => $this->service->campaignLink($marketer, $campaign),
        ]));
    }

    private function authorizeWithGuard(Marketer $marketer, string $ability, MarketerCampaign $campaign): void
    {
        $policy = new CampaignPolicy();

        if (! $policy->{$ability}($marketer, $campaign)) {
            abort(403);
        }
    }
}
