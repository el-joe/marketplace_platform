<?php

namespace App\Services;

use App\Exceptions\InsufficientStockForPromotionException;
use App\Models\AdminProductListing;
use App\Models\VendorListing;

class InfluencerPromotionFeeService
{
    /**
     * BIGINT base-currency units. Never divide or multiply by 100.
     */
    public function getPromotionFeePerSlot(VendorListing|AdminProductListing $listing): int
    {
        if ($listing->promotion_fee_per_influencer_override !== null) {
            return (int) $listing->promotion_fee_per_influencer_override;
        }

        $category = $listing->productVariant?->product?->category;

        return $category !== null ? (int) $category->promotion_fee_per_influencer : 0;
    }

    public function getInfluencerCommissionPct(VendorListing|AdminProductListing $listing): float
    {
        if ($listing->influencer_commission_pct !== null) {
            return (float) $listing->influencer_commission_pct;
        }

        $category = $listing->productVariant?->product?->category;

        return $category !== null ? (float) $category->influencer_commission_pct : 0.00;
    }

    public function getAffiliateCommissionPct(VendorListing|AdminProductListing $listing): float
    {
        if ($listing->affiliate_commission_pct !== null) {
            return (float) $listing->affiliate_commission_pct;
        }

        $category = $listing->productVariant?->product?->category;

        return $category !== null ? (float) $category->affiliate_commission_pct : 0.00;
    }

    /**
     * Never sum across currencies. Always verify currency matches before calling.
     */
    public function calculateAdminCutFromInfluencer(int $grossInfluencerEarning, float $adminCutPct): int
    {
        return (int) floor($grossInfluencerEarning * $adminCutPct / 100);
    }

    /**
     * @throws InsufficientStockForPromotionException
     */
    public function validateStockRequirement(VendorListing|AdminProductListing $listing): bool
    {
        $available = (int) $listing->warehouseInventories->sum('quantity_available');

        $minStock = $listing->min_stock_for_promotion !== null
            ? (int) $listing->min_stock_for_promotion
            : 0;

        if ($minStock === 0) {
            $category = $listing->productVariant?->product?->category;
            $minStock = $category !== null ? (int) $category->min_stock_for_promotion : 0;
        }

        if ($available >= $minStock) {
            return true;
        }

        throw new InsufficientStockForPromotionException($available, $minStock);
    }
}
