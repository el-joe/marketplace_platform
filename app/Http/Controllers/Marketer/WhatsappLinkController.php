<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\WhatsappLink\StoreWhatsappLinkRequest;
use App\Http\Resources\Marketer\QrCodeResource;
use App\Http\Resources\Marketer\WhatsappLinkResource;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerWhatsappLink;
use App\Policies\CampaignPolicy;
use App\Services\Marketer\WhatsappLinkService;
use App\Services\QrCodeGenerationService;
use Illuminate\Http\JsonResponse;

class WhatsappLinkController extends Controller
{
    public function __construct(
        private readonly WhatsappLinkService $service,
        private readonly QrCodeGenerationService $qrService,
    ) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // POST /api/marketer/v1/campaigns/{campaign}/whatsapp-links
    public function store(StoreWhatsappLinkRequest $request, MarketerCampaign $campaign): JsonResponse
    {
        $marketer = $this->marketer();

        abort_if(! (new CampaignPolicy())->manageWhatsappLinks($marketer, $campaign), 403);

        if (! $campaign->whatsapp_sharing_enabled) {
            return ApiResponse::error('WhatsApp sharing is not enabled for this campaign.', [], 403);
        }

        $link = $this->service->create($campaign, $request->validated());

        return ApiResponse::success(new WhatsappLinkResource($link), 'WhatsApp link created.', 201);
    }

    // GET /api/marketer/v1/campaigns/{campaign}/whatsapp-links
    public function index(MarketerCampaign $campaign): JsonResponse
    {
        abort_if(! (new CampaignPolicy())->manageWhatsappLinks($this->marketer(), $campaign), 403);

        $links = $campaign->whatsappLinks()->orderByDesc('created_at')->get();

        return ApiResponse::success(WhatsappLinkResource::collection($links));
    }

    // GET /api/marketer/v1/whatsapp-links/{whatsappLink}/qr-code
    public function qrCode(MarketerWhatsappLink $whatsappLink): JsonResponse
    {
        $marketer = $this->marketer();

        abort_if($whatsappLink->marketer_id !== $marketer->id, 403);

        $qrCode = $this->qrService->generate(
            marketer: $marketer,
            codeType: 'whatsapp_link',
            targetUrl: $whatsappLink->tracking_url,
            label: 'WhatsApp Link',
            campaignId: $whatsappLink->campaign_id,
        );

        return ApiResponse::success(new QrCodeResource($qrCode));
    }
}
