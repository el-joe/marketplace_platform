<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Models\MarketerCampaignProduct;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Services\MarketerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $vendors = Vendor::where('status', 'active')->orderBy('store_name')->get(['id', 'store_name']);

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
            'campaign_type' => 'required|in:product_promotion,store_promotion,category_promotion,flash_sale,general',
            'vendor_id' => 'nullable|uuid|exists:vendors,id',
            'starts_at' => 'required|date|after_or_equal:today',
            'ends_at' => 'required|date|after:starts_at',
            'products' => 'nullable|array',
            'products.*' => 'uuid|exists:vendor_listings,id',
        ]);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $campaign = $marketer->campaigns()->create([
            'vendor_id' => $validated['vendor_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'campaign_type' => $validated['campaign_type'],
            'status' => 'draft',
            'commission_type' => 'percentage',
            'commission_rate' => $marketer->commission_rate ?? 5,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'auto_approve_at' => now()->addHours(36),
        ]);

        // Attach products
        if (!empty($validated['products'])) {
            foreach ($validated['products'] as $pos => $listingId) {
                MarketerCampaignProduct::create([
                    'campaign_id' => $campaign->id,
                    'vendor_listing_id' => $listingId,
                    'position' => $pos + 1,
                ]);
            }
        }

        // Auto-approve after 36 hours if admin hasn't acted
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

        $vendor = $campaign->vendor ?? \App\Models\Vendor::findOrFail($request->input('vendor_id'));

        $sampleRequest = $this->service->requestSamples(
            $marketer,
            $vendor,
            $campaign,
            $validated['items'],
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
            'products.vendorListing' => fn($q) => $q->with('product'),
        ]);

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
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $vendorId = $request->input('vendor_id');
        $q = $request->input('q', '');

        $listings = VendorListing::query()
            ->when($vendorId, fn($query) => $query->where('vendor_id', $vendorId))
            ->whereHas('product', fn($query) => $query->where('name_en', 'like', '%' . $q . '%'))
            ->with('product:id,name_en')
            ->limit(20)
            ->get(['id', 'product_id', 'sale_price']);

        return response()->json(
            $listings->map(fn($l) => [
                'id' => $l->id,
                'text' => $l->product?->name_en ?? 'Unknown',
                'price' => number_format($l->sale_price / 100, 2),
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
