<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\PackagingSupplyRequestStatus;
use App\Enums\PackagingSupplyType;
use App\Models\PackagingSupply;
use App\Models\PackagingSupplyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackagingSupplyController extends Controller
{
    // ── Catalog ────────────────────────────────────────────────────────────

    public function index(): View
    {
        $supplies = PackagingSupply::latest()->paginate(30);

        return view('admin.packaging-supplies.index', compact('supplies'));
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
        $packagingSupply->delete();

        return redirect()
            ->route('admin.packaging-supplies.index')
            ->with('success', 'Supply deleted.');
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

        $query = PackagingSupplyRequest::with(['vendor', 'warehouse'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $supplyRequests = $query->paginate(20)->withQueryString();

        return view('admin.packaging-supplies.requests', compact('supplyRequests', 'stats'));
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

        $packagingSupplyRequest->update(['status' => PackagingSupplyRequestStatus::Rejected]);

        return redirect()
            ->route('admin.packaging-supplies.show-request', $packagingSupplyRequest)
            ->with('success', "Request #{$packagingSupplyRequest->request_number} rejected.");
    }

    public function updateRequestStatus(Request $request, PackagingSupplyRequest $packagingSupplyRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(PackagingSupplyRequestStatus::class)->only([
                PackagingSupplyRequestStatus::Shipped,
                PackagingSupplyRequestStatus::Delivered,
            ])],
        ]);

        abort_if(
            ! in_array($packagingSupplyRequest->status, [
                PackagingSupplyRequestStatus::Approved,
                PackagingSupplyRequestStatus::Shipped,
            ], true),
            422,
            'Cannot update status from current state.'
        );

        $packagingSupplyRequest->update(['status' => $data['status']]);

        return back()->with('success', 'Status updated to ' . $data['status'] . '.');
    }
}
