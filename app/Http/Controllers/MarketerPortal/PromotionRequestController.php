<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Models\VendorInfluencerPromotionRequestItem;
use App\Services\InfluencerPromotionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PromotionRequestController extends Controller
{
    public function __construct(
        private readonly InfluencerPromotionRequestService $promotionRequestService,
    ) {}

    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $items = VendorInfluencerPromotionRequestItem::query()
            ->where('marketer_id', $marketer->id)
            ->where('status', 'pending')
            ->with(['promotionRequest.vendor', 'promotionRequest.vendorListing.productVariant.product', 'promotionRequest.adminProductListing.productVariant.product'])
            ->orderBy('expires_at')
            ->get();

        $progress = MarketerMonthlyQuotaProgress::query()
            ->where('marketer_id', $marketer->id)
            ->forPeriod(now()->year, now()->month)
            ->get()
            ->keyBy('promotion_category');

        return view('marketer.promotion_requests.index', [
            'marketer' => $marketer,
            'items' => $items,
            'progress' => $progress,
        ]);
    }

    public function show(string $item): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $item = VendorInfluencerPromotionRequestItem::query()
            ->where('marketer_id', $marketer->id)
            ->with(['promotionRequest.vendor', 'promotionRequest.vendorListing.productVariant.product', 'promotionRequest.adminProductListing.productVariant.product'])
            ->findOrFail($item);

        return view('marketer.promotion_requests.show', [
            'marketer' => $marketer,
            'item' => $item,
        ]);
    }

    public function accept(string $item): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $item = VendorInfluencerPromotionRequestItem::query()
            ->where('marketer_id', $marketer->id)
            ->findOrFail($item);

        abort_if($item->status !== 'pending', 422, 'This promotion request is no longer pending.');

        $this->promotionRequestService->handleInfluencerResponse($item, 'accept', null);

        return back()->with('success', __('marketer.promotion_requests.accepted'));
    }

    public function decline(string $item, Request $request): RedirectResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $item = VendorInfluencerPromotionRequestItem::query()
            ->where('marketer_id', $marketer->id)
            ->findOrFail($item);

        abort_if($item->status !== 'pending', 422, 'This promotion request is no longer pending.');

        $this->promotionRequestService->handleInfluencerResponse($item, 'decline', $request->input('note'));

        return back()->with('success', __('marketer.promotion_requests.declined'));
    }
}
