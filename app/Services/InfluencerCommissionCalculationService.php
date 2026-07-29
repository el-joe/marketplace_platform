<?php

namespace App\Services;

use App\Enums\MarketerType;
use App\Exceptions\CurrencyMismatchException;
use App\Models\AdminProductListing;
use App\Models\MarketerConversion;
use App\Models\OrderItem;
use App\Models\VendorListing;
use RuntimeException;

class InfluencerCommissionCalculationService
{
    public function __construct(
        private readonly InfluencerPromotionFeeService $promotionFeeService,
    ) {
    }

    /**
     * BIGINT base-currency units. Never divide or multiply by 100. Never sum across currencies.
     *
     * @throws CurrencyMismatchException
     */
    public function calculate(MarketerConversion $conversion): array
    {
        $listing = $this->resolveListing($conversion);

        if ($conversion->currency !== $listing->currency) {
            throw new CurrencyMismatchException($conversion->currency, $listing->currency);
        }

        $marketerType = $conversion->marketer->type instanceof MarketerType
            ? $conversion->marketer->type
            : MarketerType::from($conversion->marketer->type);

        $isInfluencerLike = in_array($marketerType, [MarketerType::Influencer, MarketerType::Celebrity], true);

        $rate = $isInfluencerLike
            ? $this->promotionFeeService->getInfluencerCommissionPct($listing)
            : $this->promotionFeeService->getAffiliateCommissionPct($listing);

        $grossInfluencerCommission = (int) floor($conversion->order_value * $rate / 100);

        $category = $listing->productVariant?->product?->category;
        $adminCutPct = $category !== null
            ? (float) ($isInfluencerLike ? $category->admin_cut_from_influencer_pct : $category->admin_cut_from_affiliate_pct)
            : 0.00;

        $adminCutFromInfluencer = $this->promotionFeeService->calculateAdminCutFromInfluencer(
            $grossInfluencerCommission,
            $adminCutPct
        );

        $netInfluencerCommission = $grossInfluencerCommission - $adminCutFromInfluencer;

        return [
            'gross_influencer_commission' => $grossInfluencerCommission,
            'admin_cut_from_influencer' => $adminCutFromInfluencer,
            'net_influencer_commission' => $netInfluencerCommission,
            'currency' => $conversion->currency,
        ];
    }

    private function resolveListing(MarketerConversion $conversion): VendorListing|AdminProductListing
    {
        if ($conversion->admin_product_listing_id !== null) {
            $listing = $conversion->adminProductListing;

            if ($listing === null) {
                throw new RuntimeException("AdminProductListing not found for conversion {$conversion->id}.");
            }

            return $listing;
        }

        $orderItem = OrderItem::find($conversion->order_item_id);
        $listing = $orderItem?->vendorListing;

        if ($listing === null) {
            throw new RuntimeException("VendorListing not found for conversion {$conversion->id}.");
        }

        return $listing;
    }
}
