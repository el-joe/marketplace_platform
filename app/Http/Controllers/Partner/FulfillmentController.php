<?php

namespace App\Http\Controllers\Partner;

use App\Enums\WarehouseType;
use App\Http\Controllers\Controller;
use App\Models\FbnInboundRequest;
use App\Models\FbnStorageFee;
use App\Models\MarketplaceShippingRule;
use App\Models\VendorListing;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FulfillmentController extends Controller
{
    private function vendor()
    {
        return Auth::guard('vendor')->user()->vendor;
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    public function index(): View
    {
        $vendor = $this->vendor();

        $listings = VendorListing::where('vendor_id', $vendor->id)
            ->with(['productVariant.product', 'warehouseInventories.warehouse'])
            ->get();

        $stats = [
            'fbn_count' => $listings->where('global_system_type', 'express_fbn')->count(),
            'fbp_count' => $listings->where('global_system_type', 'merchant_fbp')->count(),
            'marketplace_count' => $listings->where('global_system_type', 'marketplace')->count(),
            'pending_requests' => FbnInboundRequest::where('vendor_id', $vendor->id)
                ->whereIn('status', ['draft', 'submitted', 'approved'])
                ->count(),
        ];

        $fbnListings = $listings->where('global_system_type', 'express_fbn')->values();
        $fbpListings = $listings->where('global_system_type', 'merchant_fbp')->values();
        $marketplaceListings = $listings->where('global_system_type', 'marketplace')->values();

        $warehouses = Warehouse::where('is_active', true)
            ->where(function ($q) use ($vendor) {
                $q->where('type', WarehouseType::PlatformFbn->value)
                    ->orWhere('owner_vendor_id', $vendor->id);
            })
            ->get(['id', 'name', 'code']);

        return view('partner.fulfillment.index', compact(
            'vendor',
            'stats',
            'fbnListings',
            'fbpListings',
            'marketplaceListings',
            'warehouses'
        ));
    }

    // ── FBN: list vendor's inbound requests ───────────────────────────────────

    public function fbnRequests(): JsonResponse
    {
        $vendor = $this->vendor();
        $requests = FbnInboundRequest::where('vendor_id', $vendor->id)
            ->with(['warehouse', 'vendorListing.productVariant.product'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'request_number' => $r->request_number,
                    'product' => $r->vendorListing?->productVariant?->product?->name_en ?? '—',
                    'warehouse' => $r->warehouse?->name ?? '—',
                    'qty_requested' => $r->quantity_requested,
                    'qty_received' => $r->quantity_received,
                    'status' => $r->status,
                    'status_color' => $r->statusColor(),
                    'status_label' => $r->statusLabel(),
                    'expected_arrival' => $r->expected_arrival?->format('d M Y'),
                    'tracking_number' => $r->tracking_number,
                    'can_cancel' => $r->canBeCancelled(),
                    'created_at' => $r->created_at->format('d M Y'),
                ];
            });

        return response()->json(['success' => true, 'data' => $requests]);
    }

    // ── FBN: submit inbound request ───────────────────────────────────────────

    public function submitInboundRequest(Request $request): JsonResponse
    {
        $vendor = $this->vendor();

        $data = $request->validate([
            'vendor_listing_id' => 'required|exists:vendor_listings,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity_requested' => 'required|integer|min:1|max:10000',
            'vendor_notes' => 'nullable|string|max:1000',
        ]);

        // Verify listing belongs to this vendor
        $listing = VendorListing::where('id', $data['vendor_listing_id'])
            ->where('vendor_id', $vendor->id)
            ->where('global_system_type', 'express_fbn')
            ->firstOrFail();

        $req = FbnInboundRequest::create([
            'vendor_id' => $vendor->id,
            'vendor_listing_id' => $listing->id,
            'warehouse_id' => $data['warehouse_id'],
            'quantity_requested' => $data['quantity_requested'],
            'status' => 'submitted',
            'vendor_notes' => $data['vendor_notes'] ?? null,
        ]);

        // Mark quantity_inbound in warehouse inventory
        WarehouseInventory::updateOrCreate(
            ['vendor_listing_id' => $listing->id, 'warehouse_id' => $data['warehouse_id']],
            []
        )->increment('quantity_inbound', $data['quantity_requested']);

        return response()->json([
            'success' => true,
            'message' => 'Inbound request ' . $req->request_number . ' submitted successfully.',
        ]);
    }

    // ── FBN: cancel inbound request ───────────────────────────────────────────

    public function cancelInboundRequest(Request $request, FbnInboundRequest $inboundRequest): JsonResponse
    {
        $vendor = $this->vendor();

        if ($inboundRequest->vendor_id !== $vendor->id) {
            abort(403);
        }

        if (!$inboundRequest->canBeCancelled()) {
            return response()->json(['success' => false, 'message' => 'This request cannot be cancelled in its current state.'], 422);
        }

        // Reverse quantity_inbound
        WarehouseInventory::where('vendor_listing_id', $inboundRequest->vendor_listing_id)
            ->where('warehouse_id', $inboundRequest->warehouse_id)
            ->where('quantity_inbound', '>', 0)
            ->each(fn($inv) => $inv->decrement('quantity_inbound', min($inboundRequest->quantity_requested, $inv->quantity_inbound)));

        $inboundRequest->update(['status' => 'rejected', 'rejection_reason' => 'Cancelled by vendor']);

        return response()->json(['success' => true, 'message' => 'Inbound request cancelled.']);
    }

    // ── FBP: inventory overview ───────────────────────────────────────────────

    public function fbpInventory(): JsonResponse
    {
        $vendor = $this->vendor();

        $inventory = WarehouseInventory::query()
            ->select('warehouse_inventories.*', 'warehouses.name as warehouse_name', 'warehouses.code as warehouse_code')
            ->join('vendor_listings', 'vendor_listings.id', '=', 'warehouse_inventories.vendor_listing_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_inventories.warehouse_id')
            ->where('vendor_listings.vendor_id', $vendor->id)
            ->where('vendor_listings.global_system_type', 'merchant_fbp')
            ->with('vendorListing.productVariant.product')
            ->orderByDesc('warehouse_inventories.updated_at')
            ->get()
            ->map(function ($inv) {
                return [
                    'product' => $inv->vendorListing?->productVariant?->product?->name_en ?? '—',
                    'warehouse' => $inv->warehouse_name . ' (' . $inv->warehouse_code . ')',
                    'on_hand' => $inv->quantity_on_hand,
                    'available' => $inv->quantity_available,
                    'reserved' => $inv->quantity_reserved,
                    'inbound' => $inv->quantity_inbound,
                    'bin_location' => $inv->bin_location,
                    'reorder_point' => $inv->reorder_point,
                    'needs_reorder' => $inv->reorder_point && $inv->quantity_available <= $inv->reorder_point,
                ];
            });

        return response()->json(['success' => true, 'data' => $inventory]);
    }

    // ── Storage fees (vendor's own) ───────────────────────────────────────────

    public function storageFees(): JsonResponse
    {
        $vendor = $this->vendor();

        $fees = FbnStorageFee::where('vendor_id', $vendor->id)
            ->orderByDesc('month')
            ->limit(24)
            ->get()
            ->map(fn($f) => [
                'month' => $f->monthLabel(),
                'units' => $f->units_stored,
                'total' => $f->totalFormatted(),
                'status' => $f->status,
                'status_color' => $f->statusColor(),
            ]);

        return response()->json(['success' => true, 'data' => $fees]);
    }
}
