<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\VendorExceptionalZone;
use App\Models\VendorFbpSubsidySetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ExceptionalZoneController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    public function index(): View
    {
        $vendorId = $this->vendorId();

        $zones = ShippingZone::where('is_active', 1)
            ->with(['country', 'optedInByVendor' => fn ($q) => $q->where('vendor_id', $vendorId)])
            ->orderBy('country_id')
            ->get();

        $subsidySetting = VendorFbpSubsidySetting::where('is_active', true)->first();

        return view('partner.exceptional-zones.index', compact('zones', 'subsidySetting'));
    }

    public function toggleZone(ShippingZone $zone): JsonResponse
    {
        $vendorId = $this->vendorId();

        $existing = VendorExceptionalZone::where('vendor_id', $vendorId)
            ->where('shipping_zone_id', $zone->id)
            ->first();

        if ($existing) {
            $existing->update(['is_active' => !$existing->is_active]);
            $isNowActive = $existing->is_active;
        } else {
            VendorExceptionalZone::create([
                'vendor_id' => $vendorId,
                'shipping_zone_id' => $zone->id,
                'is_active' => true,
            ]);
            $isNowActive = true;
        }

        return response()->json(['success' => true, 'is_active' => $isNowActive]);
    }
}
