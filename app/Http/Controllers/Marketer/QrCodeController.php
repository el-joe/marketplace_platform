<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\QrCode\StoreQrCodeRequest;
use App\Http\Resources\Marketer\QrCodeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerQrCode;
use App\Models\VendorListing;
use App\Policies\QrCodePolicy;
use App\Services\Marketer\TrackingLinkService;
use App\Services\QrCodeGenerationService;
use Illuminate\Http\JsonResponse;

class QrCodeController extends Controller
{
    public function __construct(
        private readonly QrCodeGenerationService $generator,
        private readonly TrackingLinkService $trackingLinks,
    ) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /api/marketer/v1/qr-codes
    public function index(): JsonResponse
    {
        $qrCodes = $this->marketer()
            ->qrCodes()
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success(QrCodeResource::collection($qrCodes));
    }

    // POST /api/marketer/v1/qr-codes
    public function store(StoreQrCodeRequest $request): JsonResponse
    {
        $marketer  = $this->marketer();
        $type      = $request->input('type');
        $campaignId   = $request->input('target_campaign_id');
        $listingId    = $request->input('target_listing_id');
        $label        = $request->input('label');

        // Resolve & validate campaign ownership
        $campaign = null;
        if ($campaignId) {
            $campaign = MarketerCampaign::findOrFail($campaignId);
            abort_if($campaign->marketer_id !== $marketer->id, 403, 'Campaign does not belong to you.');
        }

        // Resolve & validate listing (owner is marketer's campaign vendor)
        $listing = null;
        if ($listingId) {
            $listing = VendorListing::findOrFail($listingId);
            // Listings are vendor-owned; require a campaign that scopes this listing
            if ($campaign) {
                $ok = $campaign->products()->where('vendor_listing_id', $listingId)->exists();
                abort_if(! $ok, 403, 'Listing is not part of your campaign.');
            }
        }

        $targetUrl  = $this->resolveTargetUrl($marketer, $type, $campaign, $listing);
        $codeType   = $this->mapTypeToCodeType($type);

        $qrCode = $this->generator->generate(
            marketer: $marketer,
            codeType: $codeType,
            targetUrl: $targetUrl,
            label: $label,
            campaignId: $campaign?->id,
            vendorListingId: $listing?->id,
            productId: $listing?->product_id ?? null,
        );

        return ApiResponse::success(new QrCodeResource($qrCode), 'QR code created.', 201);
    }

    // GET /api/marketer/v1/qr-codes/{qrCode}
    public function show(MarketerQrCode $qrCode): JsonResponse
    {
        $this->checkOwnership($qrCode);

        return ApiResponse::success(new QrCodeResource($qrCode));
    }

    // DELETE /api/marketer/v1/qr-codes/{qrCode}
    public function destroy(MarketerQrCode $qrCode): JsonResponse
    {
        $this->checkOwnership($qrCode);

        $qrCode->delete();

        return ApiResponse::success(null, 'QR code deleted.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function checkOwnership(MarketerQrCode $qrCode): void
    {
        $policy = new QrCodePolicy();
        abort_if(! $policy->view($this->marketer(), $qrCode), 403);
    }

    private function resolveTargetUrl(
        Marketer $marketer,
        string $type,
        ?MarketerCampaign $campaign,
        mixed $listing,
    ): string {
        $base = $this->trackingLinks->baseLink($marketer);

        return match ($type) {
            'profile'  => rtrim(config('app.url'), '/') . '/marketers/' . $marketer->boutiqaat_style_slug,
            'product'  => ($campaign
                ? $this->trackingLinks->campaignLink($marketer, $campaign)
                : $base) . ($listing ? '&product=' . $listing->id : ''),
            'store'    => ($campaign
                ? $this->trackingLinks->campaignLink($marketer, $campaign)
                : $base) . ($listing?->vendor_id ? '&store=' . $listing->vendor_id : ''),
            'campaign' => $campaign
                ? $this->trackingLinks->campaignLink($marketer, $campaign)
                : $base,
        };
    }

    private function mapTypeToCodeType(string $type): string
    {
        return match ($type) {
            'profile'  => 'marketer_profile',
            'product'  => 'product',
            'store'    => 'vendor_store',
            'campaign' => 'campaign',
        };
    }
}
