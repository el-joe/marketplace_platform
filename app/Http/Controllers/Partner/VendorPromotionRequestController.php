<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorPromotionRequestRequest;
use App\Models\Marketer;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorInfluencerPromotionRequest;
use App\Models\VendorListing;
use App\Services\InfluencerPromotionRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VendorPromotionRequestController extends Controller
{
    public function __construct(
        private readonly InfluencerPromotionRequestService $requestService,
    ) {
    }

    private function vendor(): Vendor
    {
        return Auth::guard('vendor')->user()->vendor;
    }

    public function index(): View
    {
        $requests = VendorInfluencerPromotionRequest::query()
            ->where('vendor_id', $this->vendor()->id)
            ->with(['vendorListing', 'items.marketer'])
            ->latest()
            ->paginate(20);

        return view('vendor.promotion_requests.index', ['requests' => $requests]);
    }

    public function create(): View
    {
        $vendor = $this->vendor();

        $listings = VendorListing::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->where('available_for_marketers', true)
            ->get();

        $celebrities = Marketer::query()
            ->whereIn('type', ['celebrity', 'influencer'])
            ->where('status', 'active')
            ->where('accept_new_campaigns', true)
            ->orderBy('followers_count', 'desc')
            ->get(['id', 'name', 'display_name', 'profile_photo_path', 'followers_count', 'niche']);

        $costSettings = [
            'fee_per_celebrity' => (int) Setting::get('promotion_fee_per_celebrity', 9),
            'fixed_commission' => (int) Setting::get('promotion_fixed_admin_commission', 2),
        ];

        return view('vendor.promotion_requests.create', [
            'listings' => $listings,
            'celebrities' => $celebrities,
            'costSettings' => $costSettings,
        ]);
    }

    public function store(StoreVendorPromotionRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $promotionRequest = $this->requestService->createRequest([
            'vendor_listing_id' => $validated['vendor_listing_id'],
            'marketer_ids' => $validated['celebrity_marketer_ids'],
            'vendor_note' => $validated['vendor_note'] ?? null,
        ], $this->vendor());

        return redirect()
            ->route('promotion-requests.show', $promotionRequest->id)
            ->with('success', 'تم إرسال طلب ترويج المؤثرين بنجاح.');
    }

    public function show(VendorInfluencerPromotionRequest $request): View
    {
        abort_if($request->vendor_id !== $this->vendor()->id, 403);

        $request->load(['vendorListing', 'items.marketer']);

        return view('vendor.promotion_requests.show', ['promotionRequest' => $request]);
    }
}
