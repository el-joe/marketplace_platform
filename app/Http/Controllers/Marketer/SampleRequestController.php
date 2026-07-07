<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Sample\SampleListRequest;
use App\Http\Requests\Marketer\Sample\StoreSampleRequestRequest;
use App\Http\Resources\Marketer\SampleRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\Admin;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerSampleRequest;
use App\Notifications\Admin\SampleRequestSubmitted;
use App\Policies\CampaignPolicy;
use App\Policies\SampleRequestPolicy;
use App\Services\Marketer\SampleRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class SampleRequestController extends Controller
{
    public function __construct(private readonly SampleRequestService $service) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/campaigns/{campaign}/samples
    public function campaignSamples(MarketerCampaign $campaign): JsonResponse
    {
        $marketer = $this->marketer();
        abort_if(! (new CampaignPolicy())->manageSamples($marketer, $campaign), 403);

        $existing = MarketerSampleRequest::with(['items' => fn($q) => $q->where('is_mandatory', false)])
            ->where('marketer_id', $marketer->id)
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->first();

        return ApiResponse::success([
            'samples_required' => (int) $campaign->samples_required,
            'request'          => $existing ? new SampleRequestResource($existing) : null,
        ]);
    }

    // POST /api/marketer/v1/campaigns/{campaign}/samples
    public function store(StoreSampleRequestRequest $request, MarketerCampaign $campaign): JsonResponse
    {
        $marketer = $this->marketer();
        abort_if(! (new CampaignPolicy())->manageSamples($marketer, $campaign), 403);

        $listingIds = $request->input('vendor_listing_ids');

        if ($campaign->samples_required > 0 && count($listingIds) !== $campaign->samples_required) {
            return ApiResponse::error(
                "Exactly {$campaign->samples_required} sample item(s) required for this campaign.",
                [],
                422,
            );
        }

        $pending = MarketerSampleRequest::where('marketer_id', $marketer->id)
            ->where('campaign_id', $campaign->id)
            ->whereNotIn('status', ['rejected'])
            ->exists();

        if ($pending) {
            return ApiResponse::error('A sample request for this campaign already exists.', [], 422);
        }

        $sampleRequest = $this->service->create($marketer, $campaign, $request->validated());

        Notification::send(
            Admin::permission('marketers.samples.approve')->get(),
            new SampleRequestSubmitted($sampleRequest),
        );

        return ApiResponse::success(new SampleRequestResource($sampleRequest), 'Sample request submitted.', 201);
    }

    // GET /api/marketer/v1/sample-requests
    public function index(SampleListRequest $request): JsonResponse
    {
        $query = MarketerSampleRequest::with([
                'vendor:id,store_name,business_name',
                'campaign:id,name',
                'items' => fn($q) => $q->where('is_mandatory', false),
            ])
            ->where('marketer_id', $this->marketer()->id);

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('created_at')->paginate(20);

        return ApiResponse::paginated($paginator, SampleRequestResource::class);
    }

    // GET /api/marketer/v1/sample-requests/{sampleRequest}
    public function show(MarketerSampleRequest $sampleRequest): JsonResponse
    {
        abort_if(! (new SampleRequestPolicy())->view($this->marketer(), $sampleRequest), 403);

        $sampleRequest->load([
            'vendor:id,store_name,business_name',
            'campaign:id,name',
            'items' => fn($q) => $q->where('is_mandatory', false),
        ]);

        return ApiResponse::success(new SampleRequestResource($sampleRequest));
    }

    // POST /api/marketer/v1/sample-requests/{sampleRequest}/mark-received
    public function markReceived(MarketerSampleRequest $sampleRequest): JsonResponse
    {
        abort_if(! (new SampleRequestPolicy())->view($this->marketer(), $sampleRequest), 403);

        if ($sampleRequest->status !== 'dispatched') {
            return ApiResponse::error('Sample must be dispatched before it can be marked as received.', [], 422);
        }

        $sampleRequest->update([
            'status'      => 'received',
            'received_at' => now(),
        ]);

        return ApiResponse::success(new SampleRequestResource($sampleRequest), 'Sample marked as received.');
    }
}
