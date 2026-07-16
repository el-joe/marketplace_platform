<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\ShippingWeightSlab;
use App\Models\VendorFbpSubsidySetting;
use App\Models\VendorListing;

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
     * Resolve base fee + weight slab fee for a listing shipped to a zone, then
     * fold in the subsidy/exceptional-zone display logic from calculate().
     *
     * @param  array{vendor_listing_id: string, destination_zone_id: string, actual_weight_grams: int, dimensions: array{length_cm: int, width_cm: int, height_cm: int}, quantity: int}  $params
     */
    public function calculateShipping(array $params): array
    {
        $listing = VendorListing::with('vendor')->findOrFail($params['vendor_listing_id']);
        $quantity = max(1, (int) $params['quantity']);
        $dimensions = $params['dimensions'];

        $unitWeightGrams = $this->resolveEffectiveWeightGrams(
            (int) $dimensions['length_cm'],
            (int) $dimensions['width_cm'],
            (int) $dimensions['height_cm'],
            (int) $params['actual_weight_grams'],
        );
        $effectiveWeightGrams = $unitWeightGrams * $quantity;

        $shippingMethodId = $listing->primary_shipping_method_id;

        $rate = ShippingRate::where('destination_zone_id', $params['destination_zone_id'])
            ->where('shipping_method_id', $shippingMethodId)
            ->where('is_active', true)
            ->first();

        $baseFee = $rate->base_fee ?? 0;
        $weightSlabExtra = $this->getWeightSlabFee($shippingMethodId, $listing->country_id, $effectiveWeightGrams);
        $totalShipping = $baseFee + $weightSlabExtra;

        $display = $this->calculate($totalShipping, $listing, $params['destination_zone_id'], $listing->currency);

        return [
            'base_fee' => $baseFee,
            'weight_slab_extra' => $weightSlabExtra,
            'total_shipping' => $totalShipping,
            'customer_pays' => $display['customer_display'],
            'vendor_deduction' => $display['vendor_deduction'],
            'admin_subsidy' => $display['admin_subsidy'],
            'display_mode' => $display['display_mode'],
            'is_exceptional_zone' => $display['is_exceptional_zone'],
            'vendor_covers_delivery' => $display['vendor_covers_delivery'],
            'effective_weight_grams' => $effectiveWeightGrams,
            'shipping_method_id' => $shippingMethodId,
            'rate_id' => $rate->id ?? null,
            'currency' => $listing->currency,
        ];
    }

    public function isExceptionalZone(?string $shippingZoneId, ?VendorFbpSubsidySetting $settings = null): bool
    {
        if (! $shippingZoneId) {
            return false;
        }

        $settings ??= VendorFbpSubsidySetting::current();

        return in_array($shippingZoneId, $settings->exceptional_zone_shipping_zone_ids ?? [], true);
    }

    /**
     * Split a total delivery cost between the admin subsidy and the vendor's
     * optional coverage of whatever remains after the subsidy is applied.
     *
     * @return array{admin_subsidy: int, vendor_deduction: int, remainder: int}
     */
    public function resolveSubsidySplit(
        int $totalShippingCents,
        bool $vendorCoversDelivery,
        VendorFbpSubsidySetting $settings,
    ): array {
        $adminSubsidyCents = min($settings->admin_subsidy, $totalShippingCents);

        $afterSubsidy = max(0, $totalShippingCents - $adminSubsidyCents);
        $vendorDeductionCents = $vendorCoversDelivery ? $afterSubsidy : 0;

        $remainderCents = max(0, $totalShippingCents - $adminSubsidyCents - $vendorDeductionCents);

        return [
            'admin_subsidy' => $adminSubsidyCents,
            'vendor_deduction' => $vendorDeductionCents,
            'remainder' => $remainderCents,
        ];
    }

    /**
     * Resolve delivery cost + display info for a listing's shipping fee.
     *
     * @return array{
     *   total_shipping: int,
     *   vendor_covers_delivery: bool,
     *   is_exceptional_zone: bool,
     *   admin_subsidy: int,
     *   display_mode: string,
     *   customer_display: int,
     *   subsidy_label_en: string,
     *   subsidy_label_ar: string,
     * }
     */
    public function calculate(
        int $totalShippingCents,
        VendorListing $listing,
        ?string $shippingZoneId,
        string $currencyCode = 'SAR',
    ): array {
        $settings = VendorFbpSubsidySetting::current();
        $vendorCoversDelivery = (bool) $listing->vendor_covers_delivery;
        $isExceptionalZone = $this->isExceptionalZone($shippingZoneId, $settings);

        // Rule 1: vendor covers remaining delivery cost outside exceptional zones.
        if ($vendorCoversDelivery && ! $isExceptionalZone) {
            return $this->buildResult(
                $totalShippingCents,
                $vendorCoversDelivery,
                $isExceptionalZone,
                0,
                $totalShippingCents,
                'free',
                0,
                'Free Delivery — covered by seller',
                'توصيل مجاني — يتحمله البائع',
            );
        }

        // Rule 2: exceptional zones combine admin subsidy + optional vendor deduction.
        if ($isExceptionalZone) {
            $split = $this->resolveSubsidySplit($totalShippingCents, $vendorCoversDelivery, $settings);

            if ($split['admin_subsidy'] + $split['vendor_deduction'] >= $totalShippingCents) {
                return $this->buildResult(
                    $totalShippingCents,
                    $vendorCoversDelivery,
                    $isExceptionalZone,
                    $split['admin_subsidy'],
                    $split['vendor_deduction'],
                    'free',
                    0,
                    'Free Delivery — covered by seller and platform',
                    'توصيل مجاني — يتحمله البائع والمنصة',
                );
            }

            return $this->buildResult(
                $totalShippingCents,
                $vendorCoversDelivery,
                $isExceptionalZone,
                $split['admin_subsidy'],
                $split['vendor_deduction'],
                'subsidized',
                $split['remainder'],
                $this->subsidizedLabelEn($split['admin_subsidy'] + $split['vendor_deduction'], $split['remainder'], $currencyCode),
                $this->subsidizedLabelAr($split['admin_subsidy'] + $split['vendor_deduction'], $split['remainder'], $currencyCode),
            );
        }

        // Rule 3: admin subsidy threshold covers the full cost.
        if ($totalShippingCents <= $settings->full_coverage_threshold) {
            return $this->buildResult(
                $totalShippingCents,
                $vendorCoversDelivery,
                $isExceptionalZone,
                $totalShippingCents,
                0,
                'free_platform',
                0,
                'Free Delivery — the platform covers this',
                'توصيل مجاني — تتحمله المنصة',
            );
        }

        // Rule 4: admin covers a partial amount, vendor doesn't cover the rest.
        $adminSubsidyCents = min($settings->admin_subsidy, $totalShippingCents);
        if ($adminSubsidyCents > 0) {
            $remainderCents = $totalShippingCents - $adminSubsidyCents;

            return $this->buildResult(
                $totalShippingCents,
                $vendorCoversDelivery,
                $isExceptionalZone,
                $adminSubsidyCents,
                0,
                'subsidized',
                $remainderCents,
                $this->subsidizedLabelEn($adminSubsidyCents, $remainderCents, $currencyCode),
                $this->subsidizedLabelAr($adminSubsidyCents, $remainderCents, $currencyCode),
            );
        }

        // Rule 5: no coverage — customer pays full delivery fee.
        return $this->buildResult(
            $totalShippingCents,
            $vendorCoversDelivery,
            $isExceptionalZone,
            0,
            0,
            'full',
            $totalShippingCents,
            'Delivery fee applies',
            'يتم تطبيق رسوم التوصيل',
        );
    }

    private function buildResult(
        int $totalShippingCents,
        bool $vendorCoversDelivery,
        bool $isExceptionalZone,
        int $adminSubsidyCents,
        int $vendorDeductionCents,
        string $displayMode,
        int $customerDisplayCents,
        string $labelEn,
        string $labelAr,
    ): array {
        return [
            'total_shipping' => $totalShippingCents,
            'vendor_covers_delivery' => $vendorCoversDelivery,
            'is_exceptional_zone' => $isExceptionalZone,
            'admin_subsidy' => $adminSubsidyCents,
            'vendor_deduction' => $vendorDeductionCents,
            'display_mode' => $displayMode,
            'customer_display' => $customerDisplayCents,
            'subsidy_label_en' => $labelEn,
            'subsidy_label_ar' => $labelAr,
        ];
    }

    private function subsidizedLabelEn(int $coveredCents, int $remainderCents, string $currencyCode): string
    {
        return sprintf(
            'We cover %s %s of your delivery — you pay %s %s',
            $currencyCode,
            number_format($coveredCents / 100, 2),
            $currencyCode,
            number_format($remainderCents / 100, 2),
        );
    }

    private function subsidizedLabelAr(int $coveredCents, int $remainderCents, string $currencyCode): string
    {
        return sprintf(
            'نتحمل %s %s من تكلفة التوصيل — يتبقى عليك %s %s',
            number_format($coveredCents / 100, 2),
            $currencyCode,
            number_format($remainderCents / 100, 2),
            $currencyCode,
        );
    }
}
