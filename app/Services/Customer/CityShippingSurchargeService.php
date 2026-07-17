<?php

namespace App\Services\Customer;

use App\Models\VendorCityShippingSurcharge;

class CityShippingSurchargeService
{
    public function resolveSurcharge(string $vendorId, ?string $cityId): int
    {
        if (!$cityId) {
            return 0;
        }

        return (int) (VendorCityShippingSurcharge::where('vendor_id', $vendorId)
            ->where('city_id', $cityId)
            ->where('is_active', true)
            ->value('extra_amount_cents') ?? 0);
    }
}
