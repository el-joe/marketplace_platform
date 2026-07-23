<?php

namespace App\Http\Controllers\Partner;

use App\Enums\PackagingSupplyRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PackagingSupply;
use App\Models\PackagingSupplyRequest;
use App\Models\PackagingSupplyRequestItem;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Notifications\Admin\NewPackagingOrderReceived;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PackagingSupplyController extends Controller
{
    use HasDataTable;
    use HasExport;

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
        $currency   = null;

        foreach ($data['items'] as $item) {
            $supply = $supplies[$item['supply_id']];

            if ($currency && $currency !== $supply->currency) {
                abort(422, 'All items in a single order must use the same currency.');
            }
            $currency = $supply->currency;

            $lineCents = $supply->unit_cost * $item['quantity'];
            $totalCents += $lineCents;

            $lineItems[] = [
                'packaging_supply_id' => $supply->id,
                'quantity'            => $item['quantity'],
                'unit_cost'     => $supply->unit_cost,
                'line_total'    => $lineCents,
                'created_at'          => now(),
            ];
        }

        $supplyRequest = DB::transaction(function () use ($vendor, $data, $lineItems, $totalCents, $currency) {
            $supplyRequest = PackagingSupplyRequest::create([
                'request_number'     => PackagingSupplyRequest::generateRequestNumber(),
                'vendor_id'          => $vendor->vendor_id,
                'warehouse_id'       => $data['warehouse_id'] ?? null,
                'status'             => PackagingSupplyRequestStatus::Pending,
                'total_cost'   => $totalCents,
                'delivery_fee' => $this->resolveDeliveryFeeCents($vendor->vendor),
                'currency'           => $currency,
                'notes'              => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                $supplyRequest->items()->create($line);
            }

            return $supplyRequest;
        });

        Notification::send(Admin::permission('packaging.manage')->get(), new NewPackagingOrderReceived($supplyRequest));

        return redirect()
            ->route('partner.packaging-supplies.my-requests')
            ->with('success', "Request #{$supplyRequest->request_number} submitted successfully.");
    }

    public function myRequests(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if ($request->filled('export')) {
            return $this->exportMyRequests($request);
        }

        $vendor = auth('vendor')->user();

        $supplyRequests = $this->buildMyRequestsQuery($request)
            ->with('items.supply')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('partner.packaging-supplies.my-requests', compact('supplyRequests'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Export
    // ─────────────────────────────────────────────────────────────────────────

    private function buildMyRequestsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $vendor = auth('vendor')->user();

        $query = PackagingSupplyRequest::where('vendor_id', $vendor->vendor_id);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('status', $v),
            'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
        ]);

        return $query;
    }

    private function exportMyRequests(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $requests = $this->buildMyRequestsQuery($request)
            ->withCount('items')
            ->latest()
            ->get();

        $headers = ['Request #', 'Items', 'Total', 'Currency', 'Status', 'Date'];

        $rows = $requests->map(fn($row) => [
            $row->request_number,
            $row->items_count,
            number_format($row->total_cost / 100, 2),
            $row->currency,
            $row->status instanceof PackagingSupplyRequestStatus ? $row->status->value : $row->status,
            $row->created_at->format('Y-m-d H:i'),
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('packaging-supply-requests', $headers, $rows),
            'csv' => $this->exportCsv('packaging-supply-requests', $headers, $rows),
            'word' => $this->exportWord('packaging-supply-requests', 'Packaging Supply Requests', $rows),
            default => abort(400, 'Invalid export format.'),
        };
    }

    public function showRequest(PackagingSupplyRequest $packagingSupplyRequest): View
    {
        $vendor = auth('vendor')->user();

        abort_unless($packagingSupplyRequest->vendor_id === $vendor->vendor_id, 403);

        $packagingSupplyRequest->load(['items.supply', 'warehouse']);

        return view('partner.packaging-supplies.show-request', ['req' => $packagingSupplyRequest]);
    }

    public function deliveryFee(): JsonResponse
    {
        $vendor = auth('vendor')->user();
        $feeCents = $this->resolveDeliveryFeeCents($vendor->vendor);

        return response()->json([
            'fee'      => $feeCents,
            'currency' => 'USD',
        ]);
    }

    private function resolveDeliveryFeeCents(?\App\Models\Vendor $vendor): int
    {
        $feesByCountry = Setting::get('packaging_delivery_fee', []);

        if (!is_array($feesByCountry) || !$vendor || !$vendor->country_id) {
            return 0;
        }

        return (int) ($feesByCountry[$vendor->country_id] ?? 0);
    }
}
