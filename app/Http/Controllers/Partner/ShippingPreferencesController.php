<?php

namespace App\Http\Controllers\Partner;

use App\Enums\GlobalSystemType;
use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\VendorExceptionalZone;
use App\Models\VendorListing;
use App\Models\VendorSubsidySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShippingPreferencesController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    private function activeSubsidySetting(string $vendorId, string $countryId): ?VendorSubsidySetting
    {
        $today = today()->toDateString();

        return VendorSubsidySetting::where('vendor_id', $vendorId)
            ->where('country_id', $countryId)
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', $today);
            })
            ->latest('effective_from')
            ->first();
    }

    public function index(): View
    {
        $vendorId = $this->vendorId();

        $hasFbpListings = VendorListing::where('vendor_id', $vendorId)
            ->where('global_system_type', GlobalSystemType::MerchantFbp->value)
            ->whereNotIn('status', ['archived'])
            ->exists();

        if (!$hasFbpListings) {
            return view('partner.shipping.preferences', [
                'hasFbpListings' => false,
                'countries' => collect(),
            ]);
        }

        $countryIds = VendorListing::where('vendor_id', $vendorId)
            ->where('global_system_type', GlobalSystemType::MerchantFbp->value)
            ->whereNotIn('status', ['archived'])
            ->pluck('country_id')
            ->unique();

        $zones = ShippingZone::active()
            ->whereIn('country_id', $countryIds)
            ->with('country')
            ->orderBy('name')
            ->get();

        $exceptionalZoneIds = VendorExceptionalZone::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->pluck('shipping_zone_id')
            ->all();

        $countries = $zones->groupBy(fn($zone) => $zone->country->name_ar ?: $zone->country->name_en)
            ->map(function ($zonesForCountry) use ($vendorId, $exceptionalZoneIds) {
                return $zonesForCountry->map(function (ShippingZone $zone) use ($vendorId, $exceptionalZoneIds) {
                    $setting = $this->activeSubsidySetting($vendorId, $zone->country_id);

                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'description' => $zone->description,
                        'country_name' => $zone->country->name_ar ?: $zone->country->name_en,
                        'currency' => $zone->country->currency_code,
                        'is_exceptional' => in_array($zone->id, $exceptionalZoneIds, true),
                        'has_subsidy_config' => $setting !== null,
                        'vendor_share' => $setting?->vendor_share ?? 0,
                    ];
                });
            });

        return view('partner.shipping.preferences', [
            'hasFbpListings' => true,
            'countries' => $countries,
        ]);
    }

    public function toggleZone(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => ['required', 'exists:shipping_zones,id'],
        ]);

        $vendorId = $this->vendorId();
        $zoneId = $request->input('zone_id');

        $existing = VendorExceptionalZone::where('vendor_id', $vendorId)
            ->where('shipping_zone_id', $zoneId)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            $existing->update(['is_active' => false]);
            $isNowExceptional = false;
        } else {
            VendorExceptionalZone::updateOrCreate(
                ['vendor_id' => $vendorId, 'shipping_zone_id' => $zoneId],
                ['id' => (string) Str::uuid(), 'is_active' => true],
            );
            $isNowExceptional = true;
        }

        $zone = ShippingZone::with('country')->findOrFail($zoneId);
        $setting = $this->activeSubsidySetting($vendorId, $zone->country_id);

        return response()->json([
            'success' => true,
            'is_now_exceptional' => $isNowExceptional,
            'vendor_share' => $setting?->vendor_share ?? 0,
            'currency' => $zone->country->currency_code,
        ]);
    }
}
