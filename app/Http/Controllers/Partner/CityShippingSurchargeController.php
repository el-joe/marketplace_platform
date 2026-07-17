<?php

namespace App\Http\Controllers\Partner;

use App\Enums\GlobalSystemType;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\VendorCityShippingSurcharge;
use App\Models\VendorListing;
use App\Traits\HasDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CityShippingSurchargeController extends Controller
{
    use HasDataTable;

    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    public function index(Request $request): View|JsonResponse
    {
        if (!$request->has('draw')) {
            $vendorId = $this->vendorId();

            $hasFbpListings = VendorListing::where('vendor_id', $vendorId)
                ->where('global_system_type', GlobalSystemType::MerchantFbp->value)
                ->whereNotIn('status', ['archived'])
                ->exists();

            $cities = City::where('is_active', true)
                ->with('country')
                ->orderBy('name_en')
                ->get(['id', 'name_en', 'country_id']);

            return view('partner.city-surcharges', compact('hasFbpListings', 'cities'));
        }

        $query = VendorCityShippingSurcharge::query()
            ->with('city.country')
            ->where('vendor_id', $this->vendorId())
            ->select('vendor_city_shipping_surcharges.*');

        $columns = [
            ['orderable_column' => 'city_id'],
            ['orderable_column' => 'extra_amount_cents'],
            [],
            [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (VendorCityShippingSurcharge $surcharge) {
            return [
                'id' => $surcharge->id,
                'city' => $surcharge->city?->name_en,
                'country' => $surcharge->city?->country?->name_en,
                'extra_amount' => number_format($surcharge->extra_amount_cents / 100, 2),
                'is_active' => $surcharge->is_active,
                'actions' => [
                    'id' => $surcharge->id,
                    'city_id' => $surcharge->city_id,
                    'extra_amount_cents' => $surcharge->extra_amount_cents,
                ],
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();

        $validated = $request->validate([
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'extra_amount_cents' => ['required', 'integer', 'min:0'],
        ]);

        $surcharge = VendorCityShippingSurcharge::updateOrCreate(
            ['vendor_id' => $vendorId, 'city_id' => $validated['city_id']],
            ['extra_amount_cents' => $validated['extra_amount_cents'], 'is_active' => true],
        );

        return response()->json(['message' => 'City surcharge saved.', 'data' => ['id' => $surcharge->id]]);
    }

    public function update(Request $request, VendorCityShippingSurcharge $surcharge): JsonResponse
    {
        abort_unless($surcharge->vendor_id === $this->vendorId(), 404);

        $validated = $request->validate([
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'extra_amount_cents' => ['required', 'integer', 'min:0'],
        ]);

        $surcharge->update($validated);

        return response()->json(['message' => 'City surcharge updated.']);
    }

    public function toggleActive(VendorCityShippingSurcharge $surcharge): JsonResponse
    {
        abort_unless($surcharge->vendor_id === $this->vendorId(), 404);

        $surcharge->update(['is_active' => !$surcharge->is_active]);

        return response()->json(['message' => $surcharge->is_active ? 'Surcharge activated.' : 'Surcharge deactivated.']);
    }
}
