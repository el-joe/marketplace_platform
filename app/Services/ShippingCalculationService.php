<?php

namespace App\Services;

use App\Models\ShippingWeightSlab;

class ShippingCalculationService
{
    /**
     * Effective billable weight for a single unit: the greater of actual
     * weight and volumetric weight (L x W x H / divisor, in kg, converted to grams).
     */
    public function resolveEffectiveWeightGrams(
        int $lengthCm,
        int $widthCm,
        int $heightCm,
        int $actualWeightGrams,
        int $volumetricDivisor = 5000,
    ): int {
        $volumetricWeightGrams = (int) round((($lengthCm * $widthCm * $heightCm) / $volumetricDivisor) * 1000);

        return max($actualWeightGrams, $volumetricWeightGrams);
    }

    public function getWeightSlabFee(string $shippingMethodId, string $countryId, int $effectiveWeightGrams): int
    {
        $extraFee = ShippingWeightSlab::where('shipping_method_id', $shippingMethodId)
            ->where('country_id', $countryId)
            ->where('is_active', true)
            ->where('min_weight_grams', '<=', $effectiveWeightGrams)
            ->where(function ($query) use ($effectiveWeightGrams) {
                $query->whereNull('max_weight_grams')
                    ->orWhere('max_weight_grams', '>=', $effectiveWeightGrams);
            })
            ->value('extra_fee');

        return (int) ($extraFee ?? 0);
    }
}
