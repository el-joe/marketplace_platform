<?php

namespace App\Services;

use App\Models\PlatformShippingSubsidy;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingWeightSlab;
use App\Models\ShippingZone;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Models\WarehouseExceptionalZone;
use App\ValueObjects\ShippingBreakdown;

/**
 * All weight values are GRAMS. All money values are BIGINT base currency
 * units — never divide or multiply by 100 here.
 */
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

    /**
     * Calculate the shipping breakdown for one sub_order: what the customer
     * pays, what the carrier actually costs the platform, and — for
     * exceptional zones the vendor has opted into — how the resulting gap
     * is split between the vendor and the platform.
     */
    public function calculate(
        Vendor $vendor,
        VendorListing $listing,
        int $quantity,
        ShippingZone $destinationZone,
        ShippingMethod $method,
        int $billableWeightGrams,
    ): ShippingBreakdown {
        // ── STEP 1: Resolve the warehouse this listing ships from, and its
        //            origin zone (used to prefer a lane-specific rate/rule). ──
        $warehouse = $listing->warehouse_id ? $listing->warehouse()->first() : null;
        $originZoneId = $warehouse?->shipping_zone_id;

        // ── STEP 2: Find the shipping rate for this zone × method, preferring
        //            one scoped to this warehouse's origin zone over a
        //            zone-agnostic (NULL origin_zone_id) fallback rate. ──────
        $rateQuery = ShippingRate::where('destination_zone_id', $destinationZone->id)
            ->where('shipping_method_id', $method->id)
            ->where('is_active', 1);

        if ($originZoneId) {
            $rateQuery->orderByRaw('CASE WHEN origin_zone_id = ? THEN 0 ELSE 1 END', [$originZoneId]);
        } else {
            $rateQuery->orderByRaw('CASE WHEN origin_zone_id IS NULL THEN 0 ELSE 1 END');
        }

        $rate = $rateQuery->first();

        if (! $rate) {
            return ShippingBreakdown::notServiced();
        }

        // ── STEP 3: Calculate CUSTOMER rate (what they see at checkout) ─────────
        $customerShipping = $rate->base_fee;
        if ($billableWeightGrams > $rate->min_weight_grams) {
            $overKg = ($billableWeightGrams - $rate->min_weight_grams) / 1000;
            $customerShipping += (int) round($overKg * $rate->rate_per_kg);
        }

        $slabFee = $this->getWeightSlabFee($method->id, $destinationZone->country_id, $billableWeightGrams);
        $customerShipping += $slabFee;

        // ── STEP 4: Calculate CARRIER cost (what platform pays) ─────────────────
        $carrierCost = $rate->carrier_rate;
        if ($billableWeightGrams > $rate->min_weight_grams && $rate->carrier_rate_per_kg > 0) {
            $overKg = ($billableWeightGrams - $rate->min_weight_grams) / 1000;
            $carrierCost += (int) round($overKg * $rate->carrier_rate_per_kg);
        }

        // ── STEP 5: Calculate the GAP ────────────────────────────────────────────
        $gap = max(0, $carrierCost - $customerShipping);

        if ($gap === 0) {
            // No gap: customer pays full shipping, no subsidy split.
            return new ShippingBreakdown(
                customerPays: $customerShipping,
                carrierCost: $carrierCost,
                gap: 0,
                vendorContribution: 0,
                adminSubsidy: 0,
                subsidyRuleId: null,
                isExceptional: false,
                billableWeightGrams: $billableWeightGrams,
            );
        }

        // ── STEP 6: Has the WAREHOUSE opted in to serve this destination as an
        //            exceptional (gap) zone? Opt-in is warehouse-scoped, not
        //            vendor-scoped: the risk depends on where the parcel ships
        //            from, not who sells it. ─────────────────────────────────
        $warehouseOptedIn = $warehouse
            && WarehouseExceptionalZone::where('warehouse_id', $warehouse->id)
                ->where('destination_zone_id', $destinationZone->id)
                ->where('is_active', 1)
                ->exists();

        if (! $warehouseOptedIn) {
            // Warehouse does not serve this destination as an exceptional
            // zone: full carrier cost is passed through to the customer, and
            // no vendor/admin split occurs.
            return new ShippingBreakdown(
                customerPays: $carrierCost,
                carrierCost: $carrierCost,
                gap: 0,
                vendorContribution: 0,
                adminSubsidy: 0,
                subsidyRuleId: null,
                isExceptional: false,
                billableWeightGrams: $billableWeightGrams,
            );
        }

        // ── STEP 7: Warehouse opted in — find the subsidy rule and split the
        //            gap. Prefer a rule scoped to this warehouse over a
        //            global rule (warehouse_id IS NULL, e.g. platform FBN). ──
        $subsidyQuery = PlatformShippingSubsidy::where('shipping_zone_id', $destinationZone->id)
            ->where('shipping_method_id', $method->id)
            ->where('is_active', 1);

        if ($warehouse) {
            $subsidyQuery->where(function ($query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->orWhereNull('warehouse_id');
            })->orderByRaw('CASE WHEN warehouse_id = ? THEN 0 ELSE 1 END', [$warehouse->id]);
        } else {
            $subsidyQuery->whereNull('warehouse_id');
        }

        $subsidyRule = $subsidyQuery->first();

        if (! $subsidyRule) {
            // Exceptional zone but admin hasn't configured the split yet.
            // Default: vendor absorbs 100% of the gap.
            return new ShippingBreakdown(
                customerPays: $customerShipping,
                carrierCost: $carrierCost,
                gap: $gap,
                vendorContribution: $gap,
                adminSubsidy: 0,
                subsidyRuleId: null,
                isExceptional: true,
                billableWeightGrams: $billableWeightGrams,
            );
        }

        // ── STEP 7: Compute split based on subsidy rule ───────────────────────────
        if ($subsidyRule->split_type === 'percentage') {
            $vendorContribution = (int) floor($gap * $subsidyRule->vendor_share_pct / 100);
            $adminSubsidy = $gap - $vendorContribution;
        } else {
            $vendorContribution = min($subsidyRule->vendor_fixed_amount, $gap);
            $adminSubsidy = min($subsidyRule->admin_fixed_amount, $gap - $vendorContribution);
            $residual = $gap - $vendorContribution - $adminSubsidy;
            $adminSubsidy += $residual;
        }

        // Apply admin ceiling (subsidy_cap): whatever admin won't cover falls
        // back to the vendor.
        if ($subsidyRule->subsidy_cap > 0 && $adminSubsidy > $subsidyRule->subsidy_cap) {
            $excess = $adminSubsidy - $subsidyRule->subsidy_cap;
            $adminSubsidy = $subsidyRule->subsidy_cap;
            $vendorContribution += $excess;
        }

        // INVARIANT: vendor_contribution + admin_subsidy must always equal the
        // gap exactly (zero-sum, no rounding leakage). Any drift lands on the
        // admin side rather than silently under/over-charging the vendor.
        $drift = $gap - ($vendorContribution + $adminSubsidy);
        if ($drift !== 0) {
            $adminSubsidy += $drift;
        }

        return new ShippingBreakdown(
            customerPays: $customerShipping,
            carrierCost: $carrierCost,
            gap: $gap,
            vendorContribution: $vendorContribution,
            adminSubsidy: $adminSubsidy,
            subsidyRuleId: $subsidyRule->id,
            isExceptional: true,
            billableWeightGrams: $billableWeightGrams,
        );
    }
}
