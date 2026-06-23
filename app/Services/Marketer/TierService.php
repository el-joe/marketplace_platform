<?php

namespace App\Services\Marketer;

use App\Models\Marketer;
use App\Models\MarketerCommissionTier;

class TierService
{
    public function getProgress(Marketer $marketer): array
    {
        $salesCount = $marketer->conversions()
            ->where('status', '!=', 'reversed')
            ->count();

        $tiers = MarketerCommissionTier::where('marketer_id', $marketer->id)
            ->whereNull('campaign_id')
            ->active()
            ->orderBy('tier_order')
            ->get();

        return $tiers->map(function (MarketerCommissionTier $tier) use ($salesCount) {
            $isCurrent = $salesCount >= $tier->min_sales_count
                && ($tier->max_sales_count === null || $salesCount <= $tier->max_sales_count);

            return [
                'id'              => $tier->id,
                'tier_order'      => $tier->tier_order,
                'min_sales_count' => $tier->min_sales_count,
                'max_sales_count' => $tier->max_sales_count,
                'commission_rate' => (float) $tier->commission_rate,
                'is_current'      => $isCurrent,
                'sales_remaining' => $isCurrent
                    ? null
                    : ($salesCount < $tier->min_sales_count
                        ? $tier->min_sales_count - $salesCount
                        : 0),
            ];
        })->all();
    }

    public function getAllTiers(Marketer $marketer): array
    {
        return $this->getProgress($marketer);
    }
}
