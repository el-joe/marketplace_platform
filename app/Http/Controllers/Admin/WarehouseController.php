<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use HasDataTable;

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $stats = [
            'platform'       => Warehouse::where('type', 'platform_fbn')->count(),
            'seller_owned'   => Warehouse::where('type', 'seller_owned')->count(),
            'third_party'    => Warehouse::where('type', 'third_party')->count(),
            'total_capacity' => Warehouse::sum('total_capacity_m3'),
            'used_capacity'  => Warehouse::sum('used_capacity_m3'),
        ];

        return view('admin.warehouses.index', compact('stats'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $query = Warehouse::query()
            ->leftJoin('countries', 'countries.id', '=', 'warehouses.country_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'warehouses.owner_vendor_id')
            ->leftJoin('admins', 'admins.id', '=', 'warehouses.manager_admin_id')
            ->select(
                'warehouses.*',
                'countries.name_en as country_name',
                'vendors.store_name as vendor_name',
                'admins.name as manager_name'
            );

        $query = $this->applyFilters($query, $request, [
            'type'       => fn ($q, $v) => $q->where('warehouses.type', $v),
            'country_id' => fn ($q, $v) => $q->where('warehouses.country_id', $v),
            'is_active'  => fn ($q, $v) => $q->where('warehouses.is_active', (bool) $v),
        ]);

        $columns = [
            0 => ['searchable_columns' => ['warehouses.name', 'warehouses.code'], 'orderable_column' => 'warehouses.name'],
            1 => ['orderable_column' => 'warehouses.code'],
            2 => ['orderable_column' => 'warehouses.type'],
            3 => ['searchable_columns' => ['countries.name_en'], 'orderable_column' => 'countries.name_en'],
            4 => ['searchable_columns' => ['vendors.store_name']],
            5 => ['searchable_columns' => ['admins.name']],
            6 => [],
            7 => ['orderable_column' => 'warehouses.is_active'],
            8 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($wh) {
            $typeBadge = match ($wh->type) {
                'platform_fbn' => 'bg-indigo-100 text-indigo-700',
                'seller_owned' => 'bg-orange-100 text-orange-700',
                'third_party'  => 'bg-gray-100 text-gray-600',
                default        => 'bg-gray-100 text-gray-700',
            };

            $usedPct  = $wh->total_capacity_m3 > 0 ? round($wh->used_capacity_m3 / $wh->total_capacity_m3 * 100) : 0;
            $barColor = $usedPct >= 90 ? 'bg-red-500' : ($usedPct >= 70 ? 'bg-warning-500' : 'bg-green-500');

            $showUrl = route('admin.warehouses.show', $wh->id);
            $editUrl = route('admin.warehouses.edit', $wh->id);
            $toggleUrl = route('admin.warehouses.toggle-active', $wh->id);

            return [
                'DT_RowId'    => 'wh-' . $wh->id,
                'name'        => '<a href="' . $showUrl . '" class="font-medium text-primary-600 hover:underline">' . e($wh->name) . '</a>',
                'code'        => '<span class="font-mono text-xs text-gray-600">' . e($wh->code) . '</span>',
                'type'        => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $typeBadge . '">' . e(str_replace('_', ' ', $wh->type)) . '</span>',
                'country'     => '<span class="text-sm">' . e($wh->country_name ?? '—') . '</span>',
                'vendor'      => $wh->owner_vendor_id ? '<span class="text-sm text-gray-700">' . e($wh->vendor_name) . '</span>' : '<span class="text-gray-300">—</span>',
                'manager'     => $wh->manager_admin_id ? '<span class="text-sm text-gray-700">' . e($wh->manager_name) . '</span>' : '<span class="text-gray-300">—</span>',
                'capacity'    => '<div class="flex items-center gap-2"><div class="flex-1 bg-gray-100 rounded-full h-2"><div class="h-2 rounded-full ' . $barColor . '" style="width:' . $usedPct . '%"></div></div><span class="text-xs tabular-nums text-gray-500 whitespace-nowrap">' . $usedPct . '%</span></div>',
                'is_active'   => $wh->is_active
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>',
                'actions'     => '<div class="flex items-center gap-1">'
                    . '<a href="' . $editUrl . '" class="btn btn-xs btn-secondary">Edit</a>'
                    . '<button class="btn btn-xs btn-ghost js-toggle-active" data-url="' . $toggleUrl . '" data-active="' . (int) $wh->is_active . '">' . ($wh->is_active ? 'Disable' : 'Enable') . '</button>'
                    . '</div>',
            ];
        });
    }

    public function show(Warehouse $warehouse): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $warehouse->load(['country', 'ownerVendor', 'managerAdmin']);

        $inventoryStats = [
            'total_skus'    => WarehouseInventory::where('warehouse_id', $warehouse->id)->count(),
            'total_items'   => WarehouseInventory::where('warehouse_id', $warehouse->id)->sum('quantity_on_hand'),
            'low_stock'     => WarehouseInventory::where('warehouse_id', $warehouse->id)
                ->whereColumn('quantity_available', '<=', 'reorder_point')
                ->where('quantity_available', '>', 0)
                ->count(),
            'out_of_stock'  => WarehouseInventory::where('warehouse_id', $warehouse->id)
                ->where('quantity_available', '<=', 0)
                ->count(),
        ];

        $inventory = WarehouseInventory::where('warehouse_id', $warehouse->id)
            ->with('vendorListing.vendor')
            ->orderBy('quantity_available')
            ->paginate(50);

        $usedPct = $warehouse->total_capacity_m3 > 0
            ? round($warehouse->used_capacity_m3 / $warehouse->total_capacity_m3 * 100, 1)
            : 0;

        return view('admin.warehouses.show', compact('warehouse', 'inventoryStats', 'inventory', 'usedPct'));
    }

    public function create(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $countries = Country::where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id');
        $vendors   = Vendor::where('status', 'approved')->orderBy('store_name')->pluck('store_name', 'id');

        return view('admin.warehouses.create', compact('countries', 'vendors'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $data = $this->validateWarehouse($request);
        $warehouse = Warehouse::create($data);

        return redirect()->route('admin.warehouses.show', $warehouse->id)
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $countries = Country::where('is_active', true)->orderBy('name_en')->pluck('name_en', 'id');
        $vendors   = Vendor::where('status', 'approved')->orderBy('store_name')->pluck('store_name', 'id');

        return view('admin.warehouses.edit', compact('warehouse', 'countries', 'vendors'));
    }

    public function update(Request $request, Warehouse $warehouse): \Illuminate\Http\RedirectResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $data = $this->validateWarehouse($request);
        $warehouse->update($data);

        return back()->with('success', 'Warehouse updated successfully.');
    }

    public function toggleActive(Warehouse $warehouse): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('warehouses.view'), 403);

        $warehouse->update(['is_active' => !$warehouse->is_active]);

        $status = $warehouse->is_active ? 'activated' : 'deactivated';
        return response()->json(['message' => "Warehouse {$status}.", 'is_active' => $warehouse->is_active]);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function validateWarehouse(Request $request): array
    {
        return $request->validate([
            'name'                      => 'required|string|max:150',
            'code'                      => 'required|string|max:20',
            'type'                      => 'required|in:platform_fbn,seller_owned,third_party',
            'country_id'                => 'required|uuid|exists:countries,id',
            'owner_vendor_id'           => 'nullable|uuid|exists:vendors,id',
            'manager_admin_id'          => 'nullable|uuid|exists:admins,id',
            'latitude'                  => 'nullable|numeric',
            'longitude'                 => 'nullable|numeric',
            'total_capacity_m3'         => 'nullable|numeric|min:0',
            'used_capacity_m3'          => 'nullable|numeric|min:0',
            'storage_rate_per_m3_price' => 'nullable|integer|min:0',
            'storage_currency'          => 'nullable|string|size:3',
            'is_active'                 => 'boolean',
        ]);
    }
}
