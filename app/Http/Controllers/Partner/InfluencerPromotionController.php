<?php

namespace App\Http\Controllers\Partner;

use App\Enums\VendorListingStatus;
use App\Exceptions\InsufficientStockForPromotionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\RequestInfluencerPromotionRequest;
use App\Models\Marketer;
use App\Models\VendorListing;
use App\Services\InfluencerPromotionFeeService;
use App\Services\InfluencerPromotionRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class InfluencerPromotionController extends Controller
{
    public function __construct(
        private readonly InfluencerPromotionFeeService $feeService,
        private readonly InfluencerPromotionRequestService $requestService,
    ) {
    }

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    private function vendor(): \App\Models\Vendor
    {
        return Auth::guard('vendor')->user()->vendor;
    }

    private function authoriseListing(VendorListing $listing): void
    {
        if ($listing->vendor_id !== $this->vendorId()) {
            abort(403);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Marketer search (Select2 AJAX)
    // ─────────────────────────────────────────────────────────────────────────

    public function marketersSearch(Request $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        $q = trim((string) $request->input('q', ''));

        $marketers = Marketer::query()
            ->active()
            ->where('accept_new_campaigns', true)
            ->whereIn('type', ['influencer', 'celebrity', 'brand_ambassador'])
            ->when($listing->country_id, fn ($query) => $query->where('country_id', $listing->country_id))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('display_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'display_name', 'followers_count', 'niche']);

        return response()->json([
            'data' => $marketers->map(fn ($m) => [
                'id' => $m->id,
                'text' => $m->public_name . ($m->followers_count ? ' — ' . number_format($m->followers_count) . ' followers' : ''),
                'name' => $m->public_name,
                'niche' => $m->niche,
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fee preview (per-slot fee + eligibility)
    // ─────────────────────────────────────────────────────────────────────────

    public function feePreview(VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        $listing->load('warehouseInventories', 'productVariant.product.category');

        $feePerSlot = $this->feeService->getPromotionFeePerSlot($listing);

        $available = (int) $listing->warehouseInventories->sum('quantity_available');
        $minStock = $listing->min_stock_for_promotion !== null
            ? (int) $listing->min_stock_for_promotion
            : (int) ($listing->productVariant?->product?->category?->min_stock_for_promotion ?? 0);

        return response()->json([
            'fee_per_slot' => $feePerSlot,
            'currency' => $listing->currency,
            'available_stock' => $available,
            'min_stock_for_promotion' => $minStock,
            'stock_sufficient' => $available >= $minStock,
            'available_for_marketers' => (bool) $listing->available_for_marketers,
            'fulfillment_model' => $listing->fulfillment_model,
            'requires_warehouse_receipt' => in_array($listing->fulfillment_model, ['fbm', 'cross_dock'], true)
                || $listing->global_system_type === \App\Enums\GlobalSystemType::MerchantFbp,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Store (create promotion request)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(RequestInfluencerPromotionRequest $request, VendorListing $listing): JsonResponse
    {
        $this->authoriseListing($listing);

        if ($listing->status !== VendorListingStatus::Active) {
            return response()->json([
                'success' => false,
                'message' => 'يجب أن تكون القائمة نشطة لطلب ترويج المؤثرين.',
            ], 422);
        }

        if (! $listing->available_for_marketers) {
            return response()->json([
                'success' => false,
                'message' => 'هذه القائمة غير مفعّلة لترويج المسوّقين.',
            ], 422);
        }

        try {
            $promotionRequest = $this->requestService->createRequest([
                'vendor_listing_id' => $listing->id,
                'marketer_ids' => $request->input('marketer_ids'),
                'vendor_note' => $request->input('vendor_note'),
            ], $this->vendor());
        } catch (InsufficientStockForPromotionException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب ترويج المؤثرين بنجاح.',
            'redirect' => Route::has('partner.listings.show') ? route('partner.listings.show', $listing->id) : route('partner.listings.index'),
            'promotion_request_id' => $promotionRequest->id,
        ]);
    }
}
