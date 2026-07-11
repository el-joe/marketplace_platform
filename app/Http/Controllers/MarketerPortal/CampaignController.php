<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\AttributionModel;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ClassifiedListing;
use App\Models\MarketerCampaign;
use App\Models\MarketerCampaignProduct;
use App\Models\TravelPackage;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Notifications\Admin\SampleRequestSubmitted;
use App\Services\MarketerService;
use App\Services\SampleQuotaResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(private readonly MarketerService $service)
    {
    }
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $campaigns = $marketer->campaigns()
            ->withCount('products')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('marketer.campaigns.index', [
            'marketer' => $marketer,
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $vendors = Vendor::where('global_status', 'active')->orderBy('store_name')->get(['id', 'name', 'store_name']);

        return view('marketer.campaigns.create', [
            'marketer' => $marketer,
            'vendors' => $vendors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'campaign_type' => 'required|in:referral_link,discount_code,product_specific,brand_deal,classified_promotion,travel_promotion',
            'vendor_id' => 'nullable|uuid|exists:vendors,id',
            'classified_listing_id' => 'required_if:campaign_type,classified_promotion|nullable|uuid|exists:classified_listings,id',
            'travel_package_id' => 'required_if:campaign_type,travel_promotion|nullable|uuid|exists:travel_packages,id',
            'starts_at' => 'required|date|after_or_equal:today',
            'ends_at' => 'required|date|after:starts_at',
            'products' => 'nullable|array',
            'products.*' => 'uuid|exists:vendor_listings,id',
            'budget' => 'nullable|numeric|min:1',
            'attribution_model' => 'nullable|in:last_click,first_click,linear',
            'whatsapp_sharing_enabled' => 'nullable|boolean',
        ]);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $campaignType = $validated['campaign_type'];

        if ($campaignType === 'classified_promotion') {
            $listing = ClassifiedListing::where('status', 'active')->findOrFail($validated['classified_listing_id']);

            if (!$listing->marketer_promotion_enabled) {
                abort(422, 'This listing has not been enabled for marketer promotion by the seller.');
            }

            $campaign = $marketer->campaigns()->create([
                'campaignable_type' => ClassifiedListing::class,
                'campaignable_id' => $listing->id,
                'vendor_id' => null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'campaign_type' => $campaignType,
                'status' => 'draft',
                'commission_type' => 'percentage',
                'commission_rate' => $marketer->commission_rate ?? 5,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'auto_approve_at' => now()->addHours(36),
                'budget_cents' => isset($validated['budget']) ? (int) round($validated['budget'] * 100) : null,
                'attribution_model' => $validated['attribution_model'] ?? AttributionModel::LastClick->value,
                'whatsapp_sharing_enabled' => (bool) ($validated['whatsapp_sharing_enabled'] ?? false),
            ]);
        } elseif ($campaignType === 'travel_promotion') {
            $package = TravelPackage::where('status', 'active')->findOrFail($validated['travel_package_id']);
            // NOTE: TravelPackage has no marketer opt-in flag yet (expected from Prompt 4).
            // Defaulting to allow promotion — travel agencies typically want maximum marketer reach.
            // Revisit once Prompt 4 adds an opt-in flag to travel_packages.

            $campaign = $marketer->campaigns()->create([
                'campaignable_type' => TravelPackage::class,
                'campaignable_id' => $package->id,
                'vendor_id' => null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'campaign_type' => $campaignType,
                'status' => 'draft',
                'commission_type' => 'percentage',
                'commission_rate' => $marketer->commission_rate ?? 5,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'auto_approve_at' => now()->addHours(36),
                'budget_cents' => isset($validated['budget']) ? (int) round($validated['budget'] * 100) : null,
                'attribution_model' => $validated['attribution_model'] ?? AttributionModel::LastClick->value,
                'whatsapp_sharing_enabled' => (bool) ($validated['whatsapp_sharing_enabled'] ?? false),
            ]);
        } else {
            $campaign = $marketer->campaigns()->create([
                'campaignable_type' => Vendor::class,
                'campaignable_id' => $validated['vendor_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'campaign_type' => $campaignType,
                'status' => 'draft',
                'commission_type' => 'percentage',
                'commission_rate' => $marketer->commission_rate ?? 5,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'auto_approve_at' => now()->addHours(36),
                'budget_cents' => isset($validated['budget']) ? (int) round($validated['budget'] * 100) : null,
                'attribution_model' => $validated['attribution_model'] ?? AttributionModel::LastClick->value,
                'whatsapp_sharing_enabled' => (bool) ($validated['whatsapp_sharing_enabled'] ?? false),
            ]);

            if (!empty($validated['products'])) {
                foreach ($validated['products'] as $pos => $listingId) {
                    MarketerCampaignProduct::create([
                        'campaign_id' => $campaign->id,
                        'vendor_listing_id' => $listingId,
                        'position' => $pos + 1,
                    ]);
                }
            }
        }

        \App\Jobs\MarketerAutoApproveJob::dispatch($campaign->id, null)
            ->delay(now()->addHours(36));

        return redirect()->route('marketer.campaigns.show', $campaign->id)
            ->with('success', 'Campaign submitted for approval.');
    }

    public function requestWhatsappLink(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();
        abort_if($campaign->marketer_id !== $marketer->id, 403);
        abort_if(!$campaign->whatsapp_sharing_enabled, 403, 'WhatsApp links not enabled for this campaign.');

        $validated = $request->validate([
            'link_type' => 'required|in:discount,free_shipping,both,custom',
            'discount_pct' => 'nullable|numeric|min:0.01|max:100',
            'free_shipping' => 'nullable|boolean',
        ]);

        $link = $this->service->createWhatsappLink(
            $marketer,
            $campaign,
            $validated['link_type'],
            $validated['discount_pct'] ?? null,
            (bool) ($validated['free_shipping'] ?? false),
        );

        return response()->json([
            'success' => true,
            'tracking_url' => $link->tracking_url,
            'whatsapp_url' => $link->getWhatsappShareUrl(),
            'qr_code_url' => $link->qr_code_path ? \Illuminate\Support\Facades\Storage::url($link->qr_code_path) : null,
            'coupon_code' => $link->coupon_code,
        ]);
    }

    public function generateQrCode(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();
        abort_if($campaign->marketer_id !== $marketer->id, 403);

        $validated = $request->validate([
            'code_type' => 'required|in:marketer_profile,product,vendor_store,campaign,whatsapp_link',
            'target_url' => 'required|url|max:500',
            'custom_label' => 'nullable|string|max:150',
            'vendor_listing_id' => 'nullable|uuid|exists:vendor_listings,id',
        ]);

        $qrCode = $this->service->generateQrCode(
            $marketer,
            $validated['code_type'],
            $validated['target_url'],
            $validated['custom_label'] ?? null,
            $campaign->id,
            $validated['vendor_listing_id'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $qrCode->id,
                'qr_url' => $qrCode->qr_url,
                'download_url' => route('marketer.qr-codes.download', $qrCode),
            ],
        ]);
    }

    public function requestSamples(Request $request, MarketerCampaign $campaign): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();
        abort_if($campaign->marketer_id !== $marketer->id, 403);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.listing_id' => 'required|uuid|exists:vendor_listings,id',
            'items.*.quantity' => 'required|integer|min:1|max:10',
            'items.*.cost_cents' => 'nullable|integer|min:0',
        ]);

        $vendor = $campaign->campaignable;
        abort_unless($vendor instanceof \App\Models\Vendor, 422, 'Sample requests are only available for vendor campaigns.');

        $listingIds = array_column($validated['items'], 'listing_id');
        $category = app(SampleQuotaResolver::class)->resolveFromListingIds($listingIds);
        if ($category && $category->marketer_sample_quota > 0) {
            $totalRequested = array_sum(array_column($validated['items'], 'quantity'));
            if ($totalRequested > $category->marketer_sample_quota) {
                return response()->json([
                    'success' => false,
                    'message' => __('marketer.campaigns.max_samples_quota_exceeded', ['count' => $category->marketer_sample_quota]),
                ], 422);
            }
        }

        $sampleRequest = $this->service->requestSamples(
            $marketer,
            $vendor,
            $campaign,
            $validated['items'],
        );

        Notification::send(
            Admin::permission('marketers.samples.approve')->get(),
            new SampleRequestSubmitted($sampleRequest),
        );

        return response()->json([
            'success' => true,
            'message' => 'Sample request submitted. You will be notified when approved.',
            'id' => $sampleRequest->id,
        ]);
    }

    public function show(MarketerCampaign $campaign): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($campaign->marketer_id !== $marketer->id, 403);

        $campaign->load([
            'products.vendorListing' => fn($q) => $q->with('productVariant.product'),
            'campaignable',
            'sampleRequests' => fn($q) => $q->with([
                'items' => fn($iq) => $iq->where('is_mandatory', false)->with('vendorListing.productVariant.product'),
            ])->latest(),
        ]);

        // Resolve quota for this campaign's category
        $quotaCategory = null;
        $quota = 0;
        if ($campaign->campaignable_type === \App\Models\Vendor::class) {
            $firstProduct = $campaign->products->first();
            $listingId = $firstProduct?->vendorListing?->id ?? $campaign->products->first()?->vendor_listing_id;
            if ($listingId) {
                $quotaCategory = app(SampleQuotaResolver::class)->resolveFromListingIds([$listingId]);
            }
            $quota = $quotaCategory?->marketer_sample_quota ?? 0;
        }

        // Daily chart data: last 30 days
        $clicksData = $this->getDailyStats($campaign, 'marketer_clicks', 'clicked_at');
        $conversionsData = $this->getDailyStats($campaign, 'marketer_conversions', 'created_at');

        return view('marketer.campaigns.show', [
            'marketer' => $marketer,
            'campaign' => $campaign,
            'trackingUrl' => $this->buildTrackingUrl($campaign, $marketer->referral_code),
            'chartLabels' => $clicksData['labels'],
            'clicksData' => $clicksData['data'],
            'conversionsData' => $conversionsData['data'],
            'sampleRequests' => $campaign->sampleRequests,
            'quotaCategory' => $quotaCategory,
            'quota' => $quota,
        ]);
    }

    public function searchClassifiedListings(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        $listings = ClassifiedListing::query()
            ->where('status', 'active')
            ->where('marketer_promotion_enabled', true)
            ->where('title_en', 'like', '%' . $q . '%')
            ->limit(20)
            ->get(['id', 'title_en', 'price_cents', 'thumbnail_path', 'listing_number']);

        return response()->json(
            $listings->map(fn($l) => [
                'id' => $l->id,
                'title' => $l->title_en,
                'price' => $l->price_cents ? number_format($l->price_cents / 100, 2) : null,
                'thumbnail' => $l->thumbnail_path,
            ])
        );
    }

    public function searchTravelPackages(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        // VERIFY: TravelPackage status=active is confirmed by TravelController usage.
        // If Prompt 4 adds a marketer opt-in flag, add it to the where clause here.
        $packages = TravelPackage::query()
            ->where('status', 'active')
            ->where('title_en', 'like', '%' . $q . '%')
            ->limit(20)
            ->get(['id', 'title_en', 'price_cents', 'thumbnail_path']);

        return response()->json(
            $packages->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title_en,
                'price' => $p->price_cents ? number_format($p->price_cents / 100, 2) : null,
                'thumbnail' => $p->thumbnail_path,
            ])
        );
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $vendorId = $request->input('vendor_id');
        $q = $request->input('q', '');

        $listings = VendorListing::query()
            ->when($vendorId, fn($query) => $query->where('vendor_id', $vendorId))
            ->whereHas('productVariant.product', fn($query) => $query->where('name_en', 'like', '%' . $q . '%'))
            ->with('productVariant.product:id,name_en')
            ->limit(20)
            ->get(['id', 'product_variant_id', 'price']);

        return response()->json(
            $listings->map(fn($l) => [
                'id' => $l->id,
                'text' => $l->productVariant?->product?->name_en ?? 'Unknown',
                'price' => number_format($l->price / 100, 2),
            ])
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function getDailyStats(MarketerCampaign $campaign, string $table, string $dateCol): array
    {
        $rows = \DB::table($table)
            ->selectRaw('DATE(' . $dateCol . ') as date, COUNT(*) as cnt')
            ->where('campaign_id', $campaign->id)
            ->where($dateCol, '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date');

        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels[] = $d;
            $data[] = $rows[$d] ?? 0;
        }

        return compact('labels', 'data');
    }

    private function buildTrackingUrl(MarketerCampaign $campaign, string $referralCode): string
    {
        $country = env('DEFAULT_COUNTRY_SLUG', 'sa');
        $domain = env('APP_DOMAIN', 'localhost');

        return 'https://' . $country . '.' . $domain . '/r/' . $campaign->tracking_url_slug . '?ref=' . $referralCode;
    }
}
