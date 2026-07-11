<?php

namespace App\Http\Controllers\Partner;

use App\Enums\PackagingSupplyRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\PackagingSupply;
use App\Models\PackagingSupplyRequest;
use App\Models\PackagingSupplyRequestItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackagingSupplyController extends Controller
{
    public function index(): View
    {
        $supplies = PackagingSupply::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name_en')
            ->get();

        return view('partner.packaging-supplies.index', compact('supplies'));
    }

    public function request(): View
    {
        $vendor = auth('vendor')->user();

        $supplies = PackagingSupply::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name_en')
            ->get();

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name']);

        return view('partner.packaging-supplies.request', compact('supplies', 'warehouses'));
    }

    public function submitRequest(Request $request): RedirectResponse
    {
        $vendor = auth('vendor')->user();

        $data = $request->validate([
            'warehouse_id'          => ['nullable', 'uuid', 'exists:warehouses,id'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.supply_id'     => ['required', 'uuid', 'exists:packaging_supplies,id'],
            'items.*.quantity'      => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        // Load requested supplies to compute costs
        $supplyIds = collect($data['items'])->pluck('supply_id')->unique();
        $supplies  = PackagingSupply::whereIn('id', $supplyIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // Reject if any supply is unavailable
        foreach ($data['items'] as $item) {
            abort_unless($supplies->has($item['supply_id']), 422, 'One or more selected supplies are unavailable.');
        }

        $totalCents = 0;
        $lineItems  = [];

        foreach ($data['items'] as $item) {
            $supply    = $supplies[$item['supply_id']];
            $lineCents = $supply->unit_cost_cents * $item['quantity'];
            $totalCents += $lineCents;

            $lineItems[] = [
                'packaging_supply_id' => $supply->id,
                'quantity'            => $item['quantity'],
                'unit_cost_cents'     => $supply->unit_cost_cents,
                'line_total_cents'    => $lineCents,
                'created_at'          => now(),
            ];
        }

        $supplyRequest = PackagingSupplyRequest::create([
            'request_number'   => PackagingSupplyRequest::generateRequestNumber(),
            'vendor_id'        => $vendor->id,
            'warehouse_id'     => $data['warehouse_id'] ?? null,
            'status'           => PackagingSupplyRequestStatus::Pending,
            'total_cost_cents' => $totalCents,
            'notes'            => $data['notes'] ?? null,
        ]);

        foreach ($lineItems as $line) {
            $supplyRequest->items()->create($line);
        }

        return redirect()
            ->route('partner.packaging-supplies.my-requests')
            ->with('success', "Request #{$supplyRequest->request_number} submitted successfully.");
    }

    public function myRequests(): View
    {
        $vendor = auth('vendor')->user();

        $supplyRequests = PackagingSupplyRequest::where('vendor_id', $vendor->id)
            ->with('items.supply')
            ->latest()
            ->paginate(20);

        return view('partner.packaging-supplies.my-requests', compact('supplyRequests'));
    }

    public function showRequest(PackagingSupplyRequest $packagingSupplyRequest): View
    {
        $vendor = auth('vendor')->user();

        abort_unless($packagingSupplyRequest->vendor_id === $vendor->id, 403);

        $packagingSupplyRequest->load(['items.supply', 'warehouse']);

        return view('partner.packaging-supplies.show-request', ['req' => $packagingSupplyRequest]);
    }
}
