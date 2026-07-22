<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PlatformShippingSubsidy;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Warehouse;
use App\Traits\HasDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ShippingSubsidyController extends Controller
{
    use HasDataTable;

    public function index(Request $request): View|JsonResponse
    {
        if (!$request->has('draw')) {
            $countries = Country::where('is_active', 1)->orderBy('name_en')->get();
            $zones = ShippingZone::with('country')->orderBy('name')->get();
            $exceptionalZones = $zones->filter(fn(ShippingZone $zone) => $zone->hasGapRates())->values();
            $methods = ShippingMethod::where('is_active', 1)->orderBy('name')->get();
            $currencies = Currency::where('is_active', 1)->orderBy('code')->get();
            $warehouses = Warehouse::orderBy('name')->get();

            return view('admin.shipping_subsidies.index', compact('countries', 'zones', 'exceptionalZones', 'methods', 'currencies', 'warehouses'));
        }

        $query = PlatformShippingSubsidy::query()
            ->with(['warehouse', 'shippingZone.country', 'shippingMethod']);

        if ($request->filled('country_id')) {
            $query->whereHas('shippingZone', fn($q) => $q->where('country_id', $request->country_id));
        }

        $columns = [
            [],
            [],
            [],
            [],
            ['orderable_column' => 'subsidy_cap'],
            ['orderable_column' => 'max_subsidy_weight_grams'],
            [],
            [],
            [],
            [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (PlatformShippingSubsidy $subsidy) {
            return $this->formatSubsidy($subsidy);
        });
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateSubsidy($request);

        $this->assertNoDuplicate($data);

        $subsidy = PlatformShippingSubsidy::updateOrCreate(
            [
                'warehouse_id' => $data['warehouse_id'],
                'shipping_zone_id' => $data['shipping_zone_id'],
                'shipping_method_id' => $data['shipping_method_id'],
            ],
            $data + [
                'created_by_admin_id' => auth()->guard('admin')->id(),
            ]
        );

        $this->forgetCache($subsidy->warehouse_id, $subsidy->shipping_zone_id, $subsidy->shipping_method_id);

        return response()->json([
            'success' => true,
            'message' => 'Subsidy created.',
            'data' => $this->formatSubsidy($subsidy->fresh(['warehouse', 'shippingZone.country', 'shippingMethod'])),
        ], 201);
    }

    public function update(Request $request, PlatformShippingSubsidy $subsidy): JsonResponse
    {
        $data = $this->validateSubsidy($request);

        $this->assertNoDuplicate($data, $subsidy->id);

        $subsidy->update($data);

        $this->forgetCache($subsidy->warehouse_id, $subsidy->shipping_zone_id, $subsidy->shipping_method_id);

        return response()->json([
            'success' => true,
            'message' => 'Subsidy updated.',
            'data' => $this->formatSubsidy($subsidy->fresh(['warehouse', 'shippingZone.country', 'shippingMethod'])),
        ]);
    }

    public function destroy(PlatformShippingSubsidy $subsidy): JsonResponse
    {
        $subsidy->update(['is_active' => false]);

        $this->forgetCache($subsidy->warehouse_id, $subsidy->shipping_zone_id, $subsidy->shipping_method_id);

        return response()->json([
            'success' => true,
            'message' => 'Subsidy deactivated.',
        ]);
    }

    private function validateSubsidy(Request $request): array
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'shipping_zone_id' => ['required', 'exists:shipping_zones,id'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'subsidy_cap' => ['required', 'integer', 'min:0'],
            'max_subsidy_weight_grams' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'size:3', Rule::exists('currencies', 'code')],
            'split_type' => ['required', 'in:percentage,fixed'],
            'vendor_share_pct' => ['required_if:split_type,percentage', 'nullable', 'integer', 'min:0', 'max:100'],
            'vendor_fixed_amount' => ['required_if:split_type,fixed', 'nullable', 'integer', 'min:0'],
            'admin_fixed_amount' => ['required_if:split_type,fixed', 'nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $zone = ShippingZone::find($data['shipping_zone_id']);

        if (! $zone || ! $zone->hasGapRates()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'shipping_zone_id' => 'Subsidies can only be configured for zones that have a recorded carrier/customer rate gap.',
            ]);
        }

        return $data;
    }

    private function assertNoDuplicate(array $data, ?string $excludeId = null): void
    {
        $query = PlatformShippingSubsidy::query()
            ->where('warehouse_id', $data['warehouse_id'])
            ->where('shipping_zone_id', $data['shipping_zone_id'])
            ->where('shipping_method_id', $data['shipping_method_id'])
            ->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'shipping_method_id' => 'An active subsidy already exists for this warehouse, zone, and method.',
            ]);
        }
    }

    private function forgetCache(?string $warehouseId, string $zoneId, string $methodId): void
    {
        Cache::forget("shipping_subsidy_{$warehouseId}_{$zoneId}_{$methodId}");
    }

    private function formatSubsidy(PlatformShippingSubsidy $subsidy): array
    {
        return [
            'id' => $subsidy->id,
            'warehouse_id' => $subsidy->warehouse_id,
            'warehouse_name' => $subsidy->warehouse?->name ?? 'All warehouses',
            'shipping_zone_id' => $subsidy->shipping_zone_id,
            'zone_name' => $subsidy->shippingZone?->name ?? '—',
            'country_name' => $subsidy->shippingZone?->country?->name_en ?? '—',
            'shipping_method_id' => $subsidy->shipping_method_id,
            'method_name' => $subsidy->shippingMethod?->name ?? '—',
            'subsidy_cap' => $subsidy->subsidy_cap,
            'max_subsidy_weight_grams' => $subsidy->max_subsidy_weight_grams,
            'currency' => $subsidy->currency,
            'split_type' => $subsidy->split_type,
            'vendor_share_pct' => $subsidy->vendor_share_pct,
            'vendor_fixed_amount' => $subsidy->vendor_fixed_amount,
            'admin_fixed_amount' => $subsidy->admin_fixed_amount,
            'is_active' => $subsidy->is_active,
            'active_badge' => $subsidy->is_active
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-gray">Inactive</span>',
        ];
    }
}
