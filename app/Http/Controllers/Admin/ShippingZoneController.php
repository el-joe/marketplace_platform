<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\ShippingZone;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    use HasDataTable;

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        $countries = Country::where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.shipping-zones.index', compact('countries'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        $query = ShippingZone::query()
            ->withCount('cities')
            ->leftJoin('countries', 'countries.id', '=', 'shipping_zones.country_id')
            ->select('shipping_zones.*', 'countries.name_en as country_name');

        $query = $this->applyFilters($query, $request, [
            'country_id' => fn($q, $v) => $q->where('shipping_zones.country_id', $v),
            'is_active' => fn($q, $v) => $q->where('shipping_zones.is_active', (bool) $v),
        ]);

        $columns = [
            0 => ['searchable_columns' => ['shipping_zones.name'], 'orderable_column' => 'shipping_zones.name'],
            1 => ['searchable_columns' => ['countries.name_en'], 'orderable_column' => 'countries.name_en'],
            2 => ['searchable_columns' => ['shipping_zones.description']],
            3 => ['orderable_column' => 'cities_count'],
            4 => ['orderable_column' => 'shipping_zones.is_active'],
            5 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($zone) {
            $editUrl = '#';
            $deleteUrl = route('admin.shipping-zones.destroy', $zone->id);

            return [
                'DT_RowId' => 'sz-' . $zone->id,
                'DT_RowData' => ['id' => $zone->id, 'name' => $zone->name, 'description' => $zone->description, 'country_id' => $zone->country_id, 'is_active' => (bool) $zone->is_active],
                'name' => '<span class="font-medium text-gray-900">' . e($zone->name) . '</span>',
                'country' => '<span class="text-sm text-gray-700">' . e($zone->country_name ?? '—') . '</span>',
                'description' => '<span class="text-sm text-gray-500">' . e(\Illuminate\Support\Str::limit($zone->description, 60)) . '</span>',
                'cities_count' => '<span class="tabular-nums">' . $zone->cities_count . '</span>',
                'is_active' => $zone->is_active
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>',
                'actions' => '<div class="flex items-center gap-1">'
                    . '<button class="btn btn-xs btn-secondary js-edit-zone"'
                    . ' data-id="' . e($zone->id) . '"'
                    . ' data-name="' . e($zone->name) . '"'
                    . ' data-description="' . e($zone->description) . '"'
                    . ' data-country-id="' . e($zone->country_id) . '"'
                    . ' data-is-active="' . (int) $zone->is_active . '"'
                    . '>Edit</button>'
                    . '<button class="btn btn-xs btn-ghost text-red-500 js-delete-zone" data-url="' . $deleteUrl . '" data-name="' . e($zone->name) . '">Delete</button>'
                    . '</div>',
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'country_id' => 'required|uuid|exists:countries,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $zone = ShippingZone::create($data);

        return response()->json(['message' => 'Shipping zone created.', 'zone' => $zone]);
    }

    public function update(Request $request, ShippingZone $zone): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'country_id' => 'required|uuid|exists:countries,id',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $zone->update($data);

        return response()->json(['message' => 'Shipping zone updated.', 'zone' => $zone]);
    }

    public function destroy(ShippingZone $zone): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        // Unlink cities from this zone before deleting
        City::where('shipping_zone_id', $zone->id)->update(['shipping_zone_id' => null]);

        $zone->delete();

        return response()->json(['message' => "Zone '{$zone->name}' deleted."]);
    }

    public function assignCities(Request $request, ShippingZone $zone): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('countries.view'), 403);

        $request->validate([
            'city_ids' => 'required|array|max:500',
            'city_ids.*' => 'uuid',
        ]);

        $cityIds = $request->input('city_ids');

        // Only assign cities in the same country as the zone
        $updated = City::whereIn('id', $cityIds)
            ->where('country_id', $zone->country_id)
            ->update(['shipping_zone_id' => $zone->id]);

        return response()->json(['message' => "{$updated} cities assigned to zone '{$zone->name}'."]);
    }
}
