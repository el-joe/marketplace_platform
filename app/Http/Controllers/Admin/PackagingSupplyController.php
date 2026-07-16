<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\PackagingSupplyRequestStatus;
use App\Enums\PackagingSupplyType;
use App\Models\PackagingSupply;
use App\Models\PackagingSupplyRequest;
use App\Models\Vendor;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackagingSupplyController extends Controller
{
    use HasDataTable;

    // ── Catalog ────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.packaging-supplies.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = PackagingSupply::query();

        $query = $this->applyFilters($query, $request, [
            'type' => fn($q, $v) => $q->where('type', $v),
            'is_active' => fn($q, $v) => $q->where('is_active', (bool) $v),
        ]);

        $columns = [
            ['searchable_columns' => [], 'orderable_column' => null], // image
            ['searchable_columns' => ['name_en', 'name_ar'], 'orderable_column' => 'name_en'],
            ['searchable_columns' => [], 'orderable_column' => 'type'],
            ['searchable_columns' => [], 'orderable_column' => 'size'],
            ['searchable_columns' => [], 'orderable_column' => 'unit_cost_cents'],
            ['searchable_columns' => [], 'orderable_column' => 'stock_available'],
            ['searchable_columns' => [], 'orderable_column' => 'is_active'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        if (!$query->getQuery()->orders) {
            $query->orderByDesc('created_at');
        }

        return $this->dataTableResponse($request, $query, $columns, function (PackagingSupply $row) {
            $imgHtml = $row->image_path
                ? '<img src="' . e(Storage::disk('public')->url($row->image_path)) . '" class="w-10 h-10 object-cover rounded-lg">'
                : '<div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300 text-xs">—</div>';

            $nameHtml = '<div class="min-w-0">'
                . '<div class="font-medium text-gray-900 truncate max-w-xs">' . e($row->name_en) . '</div>'
                . '<div class="text-xs text-gray-400 truncate max-w-xs" dir="rtl">' . e($row->name_ar) . '</div>'
                . '</div>';

            $typeBadge = '<span class="badge ' . $row->typeBadgeClass() . '">' . e($row->type->label()) . '</span>';

            $stockHtml = $row->stock_available !== null ? number_format($row->stock_available) : '&infin;';

            $statusBadge = $row->is_active
                ? '<span class="badge bg-green-100 text-green-800">Active</span>'
                : '<span class="badge bg-gray-100 text-gray-600">Inactive</span>';

            $editUrl = route('admin.packaging-supplies.edit', $row->id);
            $deleteUrl = route('admin.packaging-supplies.destroy', $row->id);

            $actionsHtml = '<div class="flex justify-end gap-3">'
                . '<a href="' . $editUrl . '" class="text-primary-600 hover:underline text-xs font-medium">Edit</a>'
                . '<form method="POST" action="' . $deleteUrl . '" class="btn-delete-form inline">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="text-red-500 hover:underline text-xs font-medium">Delete</button>'
                . '</form>'
                . '</div>';

            return [
                'image' => $imgHtml,
                'name' => $nameHtml,
                'type' => $typeBadge,
                'size' => $row->size ?? '—',
                'unit_cost' => $row->unit_cost_formatted,
                'stock' => $stockHtml,
                'status' => $statusBadge,
                'actions' => $actionsHtml,
            ];
        });
    }

    public function create(): View
    {
        return view('admin.packaging-supplies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en'         => ['required', 'string', 'max:150'],
            'name_ar'         => ['required', 'string', 'max:150'],
            'type'            => ['required', Rule::enum(PackagingSupplyType::class)],
            'size'            => ['nullable', 'string', 'max:50'],
            'unit_cost_cents' => ['required', 'integer', 'min:0'],
            'stock_available' => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['boolean'],
            'image_path'      => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $supply = PackagingSupply::create($data);

        return redirect()
            ->route('admin.packaging-supplies.index')
            ->with('success', "Supply \"{$supply->name_en}\" created.");
    }

    public function edit(PackagingSupply $packagingSupply): View
    {
        return view('admin.packaging-supplies.edit', ['supply' => $packagingSupply]);
    }

    public function update(Request $request, PackagingSupply $packagingSupply): RedirectResponse
    {
        $data = $request->validate([
            'name_en'         => ['required', 'string', 'max:150'],
            'name_ar'         => ['required', 'string', 'max:150'],
            'type'            => ['required', Rule::enum(PackagingSupplyType::class)],
            'size'            => ['nullable', 'string', 'max:50'],
            'unit_cost_cents' => ['required', 'integer', 'min:0'],
            'stock_available' => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['boolean'],
            'image_path'      => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $packagingSupply->update($data);

        return redirect()
            ->route('admin.packaging-supplies.index')
            ->with('success', "Supply \"{$packagingSupply->name_en}\" updated.");
    }

    public function destroy(PackagingSupply $packagingSupply): RedirectResponse
    {
        if ($packagingSupply->image_path) {
            Storage::disk('public')->delete($packagingSupply->image_path);
        }

        $packagingSupply->delete();

        return redirect()
            ->route('admin.packaging-supplies.index')
            ->with('success', 'Supply deleted.');
    }

    // ── Catalog image upload (FilePond) ─────────────────────────────────────

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store('packaging-supplies', 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function deleteImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        Storage::disk('public')->delete($data['path']);

        return response()->json(['success' => true]);
    }

    // ── Requests queue ─────────────────────────────────────────────────────

    public function requests(Request $request): View
    {
        $stats = [
            'pending'   => PackagingSupplyRequest::where('status', PackagingSupplyRequestStatus::Pending)->count(),
            'approved'  => PackagingSupplyRequest::where('status', PackagingSupplyRequestStatus::Approved)->count(),
            'shipped'   => PackagingSupplyRequest::where('status', PackagingSupplyRequestStatus::Shipped)->count(),
            'delivered' => PackagingSupplyRequest::where('status', PackagingSupplyRequestStatus::Delivered)->count(),
            'rejected'  => PackagingSupplyRequest::where('status', PackagingSupplyRequestStatus::Rejected)->count(),
        ];

        $vendors = Vendor::orderBy('store_name')->get(['id', 'store_name']);

        return view('admin.packaging-supplies.requests', compact('stats', 'vendors'));
    }

    public function requestsDatatable(Request $request): JsonResponse
    {
        $query = PackagingSupplyRequest::with(['vendor', 'warehouse']);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('status', $v),
            'vendor_id' => fn($q, $v) => $q->where('vendor_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('created_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('created_at', '<=', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['request_number'], 'orderable_column' => 'request_number'],
            ['searchable_columns' => [], 'orderable_column' => null], // vendor
            ['searchable_columns' => [], 'orderable_column' => 'status'],
            ['searchable_columns' => [], 'orderable_column' => 'total_cost_cents'],
            ['searchable_columns' => [], 'orderable_column' => null], // delivery fee
            ['searchable_columns' => [], 'orderable_column' => null], // currency
            ['searchable_columns' => [], 'orderable_column' => 'created_at'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        if (!$query->getQuery()->orders) {
            $query->orderByDesc('created_at');
        }

        return $this->dataTableResponse($request, $query, $columns, function (PackagingSupplyRequest $row) {
            $statusBadge = '<span class="badge ' . $row->statusBadgeClass() . '">' . e($row->status->label()) . '</span>';

            $viewUrl = route('admin.packaging-supplies.show-request', $row->id);

            $actionsHtml = '<a href="' . $viewUrl . '" class="text-primary-600 hover:underline text-xs font-medium">View</a>';

            return [
                'request_number' => e($row->request_number),
                'vendor' => e($row->vendor->store_name ?? '—'),
                'status' => $statusBadge,
                'total_cost' => $row->total_cost_formatted,
                'delivery_fee' => isset($row->delivery_fee_cents) ? number_format($row->delivery_fee_cents / 100, 2) : '—',
                'currency' => $row->currency ?? config('app.currency', 'SAR'),
                'date' => $row->created_at->format('d M Y'),
                'actions' => $actionsHtml,
            ];
        });
    }

    public function showRequest(PackagingSupplyRequest $packagingSupplyRequest): View
    {
        $packagingSupplyRequest->load(['vendor', 'warehouse', 'items.supply', 'approvedBy']);

        return view('admin.packaging-supplies.show-request', ['req' => $packagingSupplyRequest]);
    }

    public function approveRequest(Request $request, PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        abort_if(! $packagingSupplyRequest->isPending(), 422, 'Request is not pending.');

        $admin = auth('admin')->user();

        $packagingSupplyRequest->update([
            'status'              => PackagingSupplyRequestStatus::Approved,
            'approved_by_admin_id'=> $admin->id,
            'approved_at'         => now(),
        ]);

        return redirect()
            ->route('admin.packaging-supplies.show-request', $packagingSupplyRequest)
            ->with('success', "Request #{$packagingSupplyRequest->request_number} approved.");
    }

    public function rejectRequest(Request $request, PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        abort_if(! $packagingSupplyRequest->isPending(), 422, 'Request is not pending.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $packagingSupplyRequest->update([
            'status' => PackagingSupplyRequestStatus::Rejected,
            'notes' => trim(($packagingSupplyRequest->notes ? $packagingSupplyRequest->notes . "\n\n" : '')
                . 'Rejection reason: ' . ($data['reason'] ?? '—')),
        ]);

        return redirect()
            ->route('admin.packaging-supplies.show-request', $packagingSupplyRequest)
            ->with('success', "Request #{$packagingSupplyRequest->request_number} rejected.");
    }

    public function markShipped(PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        abort_if($packagingSupplyRequest->status !== PackagingSupplyRequestStatus::Approved, 422, 'Request must be approved first.');

        $packagingSupplyRequest->update(['status' => PackagingSupplyRequestStatus::Shipped]);

        return back()->with('success', "Request #{$packagingSupplyRequest->request_number} marked as shipped.");
    }

    public function markDelivered(PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        abort_if($packagingSupplyRequest->status !== PackagingSupplyRequestStatus::Shipped, 422, 'Request must be shipped first.');

        $packagingSupplyRequest->update(['status' => PackagingSupplyRequestStatus::Delivered]);

        return back()->with('success', "Request #{$packagingSupplyRequest->request_number} marked as delivered.");
    }

    public function updateRequestStatus(Request $request, PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(PackagingSupplyRequestStatus::class)->only([
                PackagingSupplyRequestStatus::Shipped,
                PackagingSupplyRequestStatus::Delivered,
            ])],
        ]);

        return $data['status'] === PackagingSupplyRequestStatus::Shipped->value
            ? $this->markShipped($packagingSupplyRequest)
            : $this->markDelivered($packagingSupplyRequest);
    }
}
