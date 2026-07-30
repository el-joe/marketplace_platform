<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignType;
use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Models\Vendor;
use App\Models\VendorInfluencerPromotionRequest;
use App\Models\VendorInfluencerPromotionRequestItem;
use App\Services\InfluencerPromotionRequestService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfluencerPromotionController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        $stats = [
            'pending' => VendorInfluencerPromotionRequest::query()->where('status', 'pending')->count(),
            'active_campaigns' => MarketerCampaign::query()
                ->where('campaign_type', CampaignType::ProductPromotion)
                ->where('status', 'active')
                ->count(),
            'needs_warehouse_receipt' => VendorInfluencerPromotionRequest::query()
                ->where('requires_warehouse_receipt', true)
                ->where('warehouse_receipt_confirmed', false)
                ->count(),
            'outstanding_sample_debts' => VendorInfluencerPromotionRequest::query()
                ->where('admin_sample_debt', '>', 0)
                ->where('admin_sample_debt_settled', false)
                ->count(),
        ];

        return view('admin.influencer-promotions.index', [
            'stats' => $stats,
            'vendors' => Vendor::orderBy('store_name')->get(['id', 'store_name']),
            'statuses' => ['pending', 'partially_accepted', 'fully_accepted', 'completed', 'cancelled'],
            'fulfillmentModels' => ['fbm', 'fbn', 'cross_dock', 'admin_express', 'admin_global'],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = VendorInfluencerPromotionRequest::query()
            ->leftJoin('vendors', 'vendors.id', '=', 'vendor_influencer_promotion_requests.vendor_id')
            ->select('vendor_influencer_promotion_requests.*', 'vendors.store_name as vendor_store_name')
            ->with(['vendorListing.productVariant.product', 'adminProductListing.productVariant.product']);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('vendor_influencer_promotion_requests.status', $v),
            'vendor_id' => fn($q, $v) => $q->where('vendor_influencer_promotion_requests.vendor_id', $v),
            'listing_fulfillment_model' => fn($q, $v) => $q->where('vendor_influencer_promotion_requests.listing_fulfillment_model', $v),
            'warehouse_receipt_confirmed' => fn($q, $v) => $q->where('vendor_influencer_promotion_requests.warehouse_receipt_confirmed', (bool) (int) $v),
        ]);

        $columns = [
            0 => ['orderable_column' => 'vendor_influencer_promotion_requests.id'],
            1 => ['searchable_columns' => ['vendors.store_name']],
            2 => [],
            3 => [],
            4 => ['orderable_column' => 'vendor_influencer_promotion_requests.status'],
            5 => ['orderable_column' => 'vendor_influencer_promotion_requests.total_promotion_fee'],
            6 => ['orderable_column' => 'vendor_influencer_promotion_requests.listing_fulfillment_model'],
            7 => [],
            8 => ['orderable_column' => 'vendor_influencer_promotion_requests.created_at'],
            9 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($r) {
            $showUrl = route('admin.influencer-promotions.show', $r->id);
            $listing = $r->vendorListing ?? $r->adminProductListing;
            $productName = $listing?->productVariant?->product?->name_en ?? '—';

            $actions = '<a href="' . $showUrl . '" class="btn btn-xs btn-secondary">' . e(__('admin.influencer_promotions.view')) . '</a>';
            if (!in_array($r->status, ['completed', 'cancelled'], true)) {
                $actions .= ' <button type="button" class="btn btn-xs btn-danger btn-cancel-promotion" data-id="' . $r->id . '">' . e(__('admin.influencer_promotions.cancel')) . '</button>';
            }
            if ($r->requires_warehouse_receipt && !$r->warehouse_receipt_confirmed) {
                $actions .= ' <button type="button" class="btn btn-xs btn-success btn-confirm-warehouse" data-id="' . $r->id . '">' . e(__('admin.influencer_promotions.confirm_warehouse')) . '</button>';
            }

            return [
                'DT_RowId' => 'vipr-' . $r->id,
                'request_no' => '<span class="font-mono text-xs text-gray-500">#' . strtoupper(substr($r->id, 0, 8)) . '</span>',
                'vendor' => '<a href="' . route('admin.vendors.show', $r->vendor_id) . '" class="text-sm font-medium text-primary-600 hover:underline">' . e($r->vendor_store_name ?? '—') . '</a>',
                'listing' => '<span class="text-sm text-gray-700">' . e($productName) . '</span>',
                'total_slots' => '<span class="text-sm text-gray-700">' . (int) $r->items()->count() . '</span>',
                'status' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $this->statusBadgeClass($r->status) . '">' . e(__('admin.influencer_promotions.status_' . $r->status)) . '</span>',
                'fee' => '<span class="text-sm text-gray-700 whitespace-nowrap">' . number_format($r->total_promotion_fee) . ' ' . e($r->currency) . '</span>',
                'fulfillment_model' => '<span class="text-xs text-gray-500">' . e($r->listing_fulfillment_model ? strtoupper($r->listing_fulfillment_model) : '—') . '</span>',
                'warehouse_required' => $r->requires_warehouse_receipt
                    ? ($r->warehouse_receipt_confirmed
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">' . e(__('admin.influencer_promotions.confirmed')) . '</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">' . e(__('admin.influencer_promotions.pending_confirmation')) . '</span>')
                    : '<span class="text-xs text-gray-400">' . e(__('admin.influencer_promotions.not_required')) . '</span>',
                'created_at' => '<span class="text-xs text-gray-500 whitespace-nowrap">' . \Carbon\Carbon::parse($r->created_at)->format('M d, Y H:i') . '</span>',
                'actions' => $actions,
            ];
        });
    }

    public function show(string $id): View
    {
        $promotionRequest = VendorInfluencerPromotionRequest::query()
            ->with([
                'vendor',
                'vendorListing.productVariant.product',
                'adminProductListing.productVariant.product',
                'confirmedByAdmin',
                'createdByAdmin',
                'items.marketer',
                'items.resultingCampaign',
            ])
            ->findOrFail($id);

        $reassignmentLogs = \App\Models\VendorInfluencerReassignmentLog::query()
            ->where('promotion_request_id', $id)
            ->with(['fromMarketer', 'toMarketer', 'triggeredByAdmin'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.influencer-promotions.show', [
            'promotionRequest' => $promotionRequest,
            'reassignmentLogs' => $reassignmentLogs,
        ]);
    }

    public function cancel(Request $request, VendorInfluencerPromotionRequest $promotionRequest): JsonResponse
    {
        if (in_array($promotionRequest->status, ['completed', 'cancelled'], true)) {
            return response()->json(['success' => false, 'message' => __('admin.influencer_promotions.cannot_cancel')], 422);
        }

        $promotionRequest->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => __('admin.influencer_promotions.cancelled_success')]);
    }

    public function confirmWarehouseReceipt(Request $request, VendorInfluencerPromotionRequest $promotionRequest): JsonResponse
    {
        if (!$promotionRequest->requires_warehouse_receipt || $promotionRequest->warehouse_receipt_confirmed) {
            return response()->json(['success' => false, 'message' => __('admin.influencer_promotions.cannot_confirm_warehouse')], 422);
        }

        $promotionRequest->update([
            'warehouse_receipt_confirmed' => true,
            'confirmed_by_admin_id' => auth('admin')->id(),
        ]);

        return response()->json(['success' => true, 'message' => __('admin.influencer_promotions.warehouse_confirmed_success')]);
    }

    public function settleSampleDebt(Request $request, VendorInfluencerPromotionRequest $promotionRequest): JsonResponse
    {
        if ($promotionRequest->admin_sample_debt <= 0 || $promotionRequest->admin_sample_debt_settled) {
            return response()->json(['success' => false, 'message' => __('admin.influencer_promotions.cannot_settle_debt')], 422);
        }

        $promotionRequest->update(['admin_sample_debt_settled' => true]);

        return response()->json(['success' => true, 'message' => __('admin.influencer_promotions.debt_settled_success')]);
    }

    public function forceReassign(
        Request $request,
        VendorInfluencerPromotionRequest $promotionRequest,
        VendorInfluencerPromotionRequestItem $item,
        InfluencerPromotionRequestService $service
    ): JsonResponse {
        abort_if($item->promotion_request_id !== $promotionRequest->id, 404);

        if (!in_array($item->status, ['pending', 'timed_out'], true)) {
            return response()->json(['success' => false, 'message' => __('admin.influencer_promotions.cannot_force_reassign')], 422);
        }

        $item->update(['status' => 'reassigned']);
        $service->autoReassign($item, auth('admin')->id(), 'admin_forced');

        return response()->json(['success' => true, 'message' => __('admin.influencer_promotions.force_reassign_success')]);
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-700',
            'partially_accepted' => 'bg-blue-100 text-blue-700',
            'fully_accepted' => 'bg-indigo-100 text-indigo-700',
            'completed' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-gray-100 text-gray-500',
            default => 'bg-gray-100 text-gray-500',
        };
    }
}
