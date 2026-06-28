<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSecretPromotionRequest;
use App\Models\Country;
use App\Models\Marketer;
use App\Models\MarketerSecretPromotion;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Services\SecretPromotionService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SecretPromotionController extends Controller
{
    use HasDataTable;

    public function __construct(private readonly SecretPromotionService $service)
    {
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $stats = $this->service->getStatsForAdmin();
        $vendors = Vendor::where('global_status', 'active')->orderBy('store_name')->get(['id', 'store_name']);
        $marketers = Marketer::where('status', 'active')->orderBy('name')->get(['id', 'name', 'type']);
        $countries = Country::where('is_active', 1)->orderBy('name_en')->get();

        return view('admin.secret-promotions.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Marketers', 'url' => route('admin.marketers.all.index')],
                ['label' => 'Secret Promotions'],
            ],
            'stats' => $stats,
            'vendors' => $vendors,
            'marketers' => $marketers,
            'countries' => $countries,
        ]);
    }

    // ── DataTable ─────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['products.name_en']],
            ['searchable_columns' => ['vendors.store_name']],
            ['searchable_columns' => ['marketers.name', 'marketers.email']],
            [],
            [],
            [],
            ['orderable_column' => 'marketer_secret_promotions.total_commission_pct'],
            ['orderable_column' => 'marketer_secret_promotions.marketer_share_pct'],
            ['orderable_column' => 'marketer_secret_promotions.admin_share_pct'],
            [],
            ['orderable_column' => 'marketer_secret_promotions.valid_until'],
            ['orderable_column' => 'marketer_secret_promotions.status'],
            [],
        ];

        $query = MarketerSecretPromotion::query()
            ->with([
                'vendor',
                'vendorListing.productVariant.product.images',
                'marketer',
                'approvedBy',
            ])
            ->join('vendors', 'vendors.id', '=', 'marketer_secret_promotions.vendor_id')
            ->join('vendor_listings', 'vendor_listings.id', '=', 'marketer_secret_promotions.vendor_listing_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
            ->leftJoin('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('marketers', 'marketers.id', '=', 'marketer_secret_promotions.marketer_id')
            ->select('marketer_secret_promotions.*')
            ->when($request->status, fn($q, $v) => $q->where('marketer_secret_promotions.status', $v))
            ->when($request->vendor_id, fn($q, $v) => $q->where('marketer_secret_promotions.vendor_id', $v))
            ->when($request->marketer_id === 'open_to_all', fn($q) => $q->whereNull('marketer_secret_promotions.marketer_id'))
            ->when(
                $request->marketer_id && $request->marketer_id !== 'open_to_all',
                fn($q) => $q->where('marketer_secret_promotions.marketer_id', $request->marketer_id)
            )
            ->when(
                $request->expiry_filter === 'expiring_soon',
                fn($q) =>
                $q->whereNotNull('marketer_secret_promotions.valid_until')
                    ->where('marketer_secret_promotions.valid_until', '<=', today()->addDays(7))
                    ->where('marketer_secret_promotions.valid_until', '>=', today())
            )
            ->when(
                $request->expiry_filter === 'no_expiry',
                fn($q) =>
                $q->whereNull('marketer_secret_promotions.valid_until')
            )
            ->orderByDesc('marketer_secret_promotions.created_at');

        return $this->dataTableResponse(
            $request,
            $query,
            $columns,
            function (MarketerSecretPromotion $p) {
                $isExpiringSoon = $p->valid_until
                    && $p->valid_until->between(today(), today()->addDays(7));

                return [
                    'id' => $p->id,
                    'vendor_store_name' => $p->vendor?->store_name ?? '—',
                    'listing_product_name' => $p->vendorListing?->productVariant?->product?->name_en ?? '—',
                    'listing_variant' => $p->vendorListing?->productVariant?->variant_name ?? '',
                    'listing_price_formatted' => $p->listing_price_formatted,
                    'product_value_formatted' => $p->product_value_formatted,
                    'margin_pct' => $p->margin_pct,
                    'total_commission_pct' => $p->total_commission_pct,
                    'marketer_share_pct' => $p->marketer_share_pct,
                    'admin_share_pct' => $p->admin_share_pct,
                    'marketer_earnings_per_sale' => $p->marketer_earnings_format,
                    'admin_earnings_per_sale' => $p->admin_earnings_format,
                    'marketer_name' => $p->marketer?->name ?? 'Open to all',
                    'marketer_type' => $p->marketer?->type ?? '',
                    'status' => $p->status,
                    'status_color' => $p->status_color,
                    'valid_until_formatted' => $p->valid_until?->format('M d, Y'),
                    'is_expiring_soon' => $isExpiringSoon,
                    'conversion_count' => $p->conversions()->count(),
                    'total_admin_earned_formatted' => number_format($p->total_admin_earned / 100, 2),
                    'created_by_vendor' => $p->created_by_vendor,
                ];
            }
        );
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(MarketerSecretPromotion $secretPromotion): View
    {
        $promotion = $secretPromotion;
        $promotion->load([
            'vendor',
            'marketer',
            'approvedBy',
            'vendorListing.productVariant.product.images',
            'conversions.order.customer',
            'conversions.marketer',
        ]);

        $conversions = $promotion->conversions()
            ->with(['order.customer', 'marketer'])
            ->latest()
            ->get();

        // Chart data (last 30 days by default)
        $chartRows = $promotion->conversions()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(COALESCE(commission_amount_cents,0) * (total_commission_pct - marketer_share_pct) / NULLIF(total_commission_pct,0)) as admin_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = [
            'labels' => $chartRows->pluck('date')->toArray(),
            'counts' => $chartRows->pluck('count')->toArray(),
            'admin_revenue' => $chartRows->pluck('admin_revenue')->map(fn($v) => (int) round($v))->toArray(),
        ];

        $marketers = Marketer::where('status', 'active')->orderBy('name')->get(['id', 'name', 'type']);

        return view('admin.secret-promotions.show', compact('promotion', 'conversions', 'chartData', 'marketers'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreSecretPromotionRequest $request): JsonResponse
    {
        try {
            $promo = $this->service->create(
                $request->validated(),
                auth()->guard('admin')->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Secret promotion created.',
                'data' => ['id' => $promo->id],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(StoreSecretPromotionRequest $request, MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        try {
            $this->service->update(
                $secretPromotion,
                $request->validated(),
                auth()->guard('admin')->user()
            );

            return response()->json(['success' => true, 'message' => 'Promotion updated.']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // ── Approve vendor-proposed promotion ────────────────────────────────────

    public function approve(MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        try {
            $this->service->approve($secretPromotion, auth()->guard('admin')->user());

            return response()->json(['success' => true, 'message' => 'Promotion approved and activated.']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Reject vendor-proposed promotion ──────────────────────────────────────

    public function reject(MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        try {
            $this->service->reject($secretPromotion, auth()->guard('admin')->user());

            return response()->json(['success' => true, 'message' => 'Promotion rejected.']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Toggle status ─────────────────────────────────────────────────────────

    public function toggleStatus(Request $request, MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        $action = $request->input('action');

        try {
            if ($action === 'pause') {
                $this->service->pause($secretPromotion, auth()->guard('admin')->user());
            } else {
                $this->service->resume($secretPromotion, auth()->guard('admin')->user());
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated.',
                'data' => ['status' => $secretPromotion->fresh()->status],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status.'], 500);
        }
    }

    // ── Expire ────────────────────────────────────────────────────────────────

    public function expire(MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        $this->service->expire($secretPromotion, auth()->guard('admin')->user());

        return response()->json(['success' => true, 'message' => 'Promotion expired.']);
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicate(MarketerSecretPromotion $secretPromotion): JsonResponse
    {
        $new = $this->service->duplicate($secretPromotion, auth()->guard('admin')->user());

        return response()->json([
            'success' => true,
            'message' => 'Promotion duplicated.',
            'data' => ['id' => $new->id],
        ]);
    }

    // ── Get listings for vendor (AJAX Select2) ────────────────────────────────

    public function getListingsForVendor(Request $request): JsonResponse
    {
        $listings = VendorListing::where('vendor_id', $request->vendor_id)
            ->where('status', 'active')
            ->with([
                'productVariant.product',
                'productVariant.product.images' => fn($q) => $q->where('is_primary', 1),
            ])
            ->get()
            ->map(function (VendorListing $l) {
                $product = $l->productVariant?->product;
                $image = $product?->images?->first();
                $imageUrl = $image
                    ? Storage::disk($image->disk)->url($image->path)
                    : null;

                return [
                    'id' => $l->id,
                    'text' => $product?->name_en ?? 'Unknown',
                    'variant' => $l->productVariant?->variant_name,
                    'price' => $l->price,
                    'currency' => $l->currency,
                    'image' => $imageUrl,
                ];
            });

        return response()->json(['results' => $listings]);
    }

    // ── Get listing details ───────────────────────────────────────────────────

    public function getListingDetails(VendorListing $listing): JsonResponse
    {
        $product = $listing->productVariant?->product;
        $image = $product?->images?->first();
        $imageUrl = $image
            ? Storage::disk($image->disk)->url($image->path)
            : null;

        return response()->json([
            'data' => [
                'price' => $listing->price,
                'price_formatted' => number_format($listing->price / 100, 2),
                'currency' => $listing->currency,
                'product_name' => $product?->name_en,
                'image_url' => $imageUrl,
                'vendor_store' => $listing->vendor?->store_name,
            ],
        ]);
    }

    // ── Stats (cached) ────────────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $adminId = auth()->guard('admin')->id();
        $data = Cache::remember("secret_promo_stats_{$adminId}", 300, fn() => $this->service->getStatsForAdmin());

        return response()->json(['data' => $data]);
    }
}
