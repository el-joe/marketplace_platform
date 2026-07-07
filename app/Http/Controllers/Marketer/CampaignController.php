<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Campaign\StoreCampaignRequest;
use App\Http\Resources\Marketer\CampaignResource;
use App\Http\Resources\Marketer\ConversionResource;
use App\Http\Responses\ApiResponse;
use App\Models\ClassifiedListing;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerQrCode;
use App\Models\TravelPackage;
use App\Policies\CampaignPolicy;
use App\Services\Marketer\CampaignService;
use App\Services\Marketer\TrackingLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $service,
        private readonly TrackingLinkService $trackingLinks,
    ) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    private function policy(): CampaignPolicy
    {
        return new CampaignPolicy();
    }

    // GET /api/marketer/v1/campaigns
    public function index(Request $request): JsonResponse
    {
        $query = $this->marketer()
            ->campaigns()
            ->with('campaignable')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->paginate(15);

        return ApiResponse::paginated($paginator, CampaignResource::class);
    }

    // GET /api/marketer/v1/campaigns/{campaign}
    public function show(MarketerCampaign $campaign): JsonResponse
    {
        abort_if(! $this->policy()->view($this->marketer(), $campaign), 403);

        $campaign->load(['products', 'secretPromotion', 'campaignable']);

        $qrCode = MarketerQrCode::where('marketer_id', $this->marketer()->id)
            ->where('campaign_id', $campaign->id)
            ->first();

        $data = array_merge((new CampaignResource($campaign))->resolve(), [
            'recent_conversions' => ConversionResource::collection(
                $campaign->conversions()->orderByDesc('created_at')->limit(5)->get()
            )->resolve(),
            'classified_listing' => $campaign->campaignable instanceof ClassifiedListing
                ? [
                    'id'    => $campaign->campaignable->id,
                    'title' => $campaign->campaignable->title_en,
                    'slug'  => $campaign->campaignable->slug,
                ]
                : null,
            'travel_package' => $campaign->campaignable instanceof TravelPackage
                ? [
                    'id'    => $campaign->campaignable->id,
                    'title' => $campaign->campaignable->title_en,
                    'slug'  => $campaign->campaignable->slug,
                ]
                : null,
            'tracking_link' => $this->trackingLinks->campaignLink($this->marketer(), $campaign),
            'qr_code_url'   => $qrCode?->qr_url,
        ]);

        return ApiResponse::success($data);
    }

    // POST /api/marketer/v1/campaigns
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->service->requestCampaign(
            $this->marketer(),
            $request->validated(),
        );

        $campaign->load(['products', 'secretPromotion', 'campaignable']);

        return ApiResponse::success(new CampaignResource($campaign), 'Campaign request submitted.', 201);
    }

    // GET /api/marketer/v1/campaigns/{campaign}/analytics
    public function analytics(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        abort_if(! $this->policy()->viewAnalytics($this->marketer(), $campaign), 403);

        $from = $request->date('from', 'Y-m-d') ?? now()->subDays(29)->startOfDay();
        $to   = $request->date('to', 'Y-m-d') ?? now()->endOfDay();

        $clicks = $campaign->clicks()
            ->selectRaw('DATE(clicked_at) as date, COUNT(*) as clicks')
            ->whereBetween('clicked_at', [$from, $to])
            ->groupByRaw('DATE(clicked_at)')
            ->pluck('clicks', 'date');

        $conversions = $campaign->conversions()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as conversions, SUM(commission_amount_cents) as commission_cents')
            ->whereBetween('created_at', [$from, $to])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor <= $to) {
            $dateKey = $cursor->toDateString();
            $conv    = $conversions->get($dateKey);
            $days[]  = [
                'date'             => $dateKey,
                'clicks'           => (int) ($clicks->get($dateKey) ?? 0),
                'conversions'      => (int) ($conv->conversions ?? 0),
                'commission_cents' => (int) ($conv->commission_cents ?? 0),
            ];
            $cursor->addDay();
        }

        return ApiResponse::success([
            'campaign_id' => $campaign->id,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'daily'       => $days,
        ]);
    }

    // GET /api/marketer/v1/campaigns/{campaign}/conversions
    public function conversions(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        abort_if(! $this->policy()->viewConversions($this->marketer(), $campaign), 403);

        $paginator = $campaign->conversions()
            ->select(['id', 'order_id', 'commission_amount_cents', 'status', 'is_whatsapp_conversion', 'approved_at', 'paid_at', 'created_at'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::paginated($paginator, ConversionResource::class);
    }
}
