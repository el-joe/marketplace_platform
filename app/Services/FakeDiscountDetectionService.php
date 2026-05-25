<?php

namespace App\Services;

use App\Models\FlashSalePriceHistory;
use App\Models\FlashSaleSubmission;

class FakeDiscountDetectionService
{
    /**
     * Days before the submission to look back for reference prices.
     */
    private const LOOKBACK_DAYS = 30;

    /**
     * Minimum ratio: if the flash_price is below this fraction of the
     * 30-day reference, the discount is considered suspiciously deep
     * (possible original-price inflation).
     */
    private const MIN_FLASH_TO_REFERENCE_RATIO = 0.30;

    /**
     * If the price was raised within this many days before submission
     * by at least this percentage, flag as potential fake discount.
     */
    private const INFLATION_WINDOW_DAYS = 7;
    private const INFLATION_THRESHOLD_PCT = 20.0;

    /**
     * Check a flash sale submission for potential fake-discount manipulation.
     *
     * Returns an array:
     *   [
     *     'flagged'       => bool,
     *     'risk_level'    => 'none'|'low'|'medium'|'high',
     *     'reasons'       => string[],
     *     'reference_avg' => int|null,   // 30-day average price in cents
     *     'price_spike'   => float|null, // % spike before submission window
     *   ]
     */
    public function check(FlashSaleSubmission $submission): array
    {
        $reasons   = [];
        $riskLevel = 'none';

        $listingId   = $submission->vendor_listing_id;
        $flashPrice  = $submission->flash_price;
        $cutoff      = $submission->submitted_at
            ? $submission->submitted_at->subDays(self::LOOKBACK_DAYS)
            : now()->subDays(self::LOOKBACK_DAYS);

        // ── 1. 30-day average price ───────────────────────────────────────────
        $recentPrices = FlashSalePriceHistory::where('vendor_listing_id', $listingId)
            ->where('recorded_at', '>=', $cutoff)
            ->orderBy('recorded_at')
            ->pluck('price');

        $referenceAvg = $recentPrices->isNotEmpty()
            ? (int) round($recentPrices->average())
            : null;

        if ($referenceAvg !== null && $referenceAvg > 0) {
            $ratio = $flashPrice / $referenceAvg;

            if ($ratio < self::MIN_FLASH_TO_REFERENCE_RATIO) {
                $reasons[] = sprintf(
                    'Flash price (%s) is less than %d%% of the 30-day average (%s). Potential extreme inflation.',
                    number_format($flashPrice / 100, 2),
                    (int) (self::MIN_FLASH_TO_REFERENCE_RATIO * 100),
                    number_format($referenceAvg / 100, 2)
                );
                $riskLevel = 'high';
            }
        }

        // ── 2. Price spike in the 7 days before submission ────────────────────
        $inflationCutoff = $submission->submitted_at
            ? $submission->submitted_at->subDays(self::INFLATION_WINDOW_DAYS)
            : now()->subDays(self::INFLATION_WINDOW_DAYS);

        $beforeSpike = FlashSalePriceHistory::where('vendor_listing_id', $listingId)
            ->where('recorded_at', '<', $inflationCutoff)
            ->orderByDesc('recorded_at')
            ->value('price');

        $afterSpike = FlashSalePriceHistory::where('vendor_listing_id', $listingId)
            ->where('recorded_at', '>=', $inflationCutoff)
            ->orderBy('recorded_at')
            ->value('price');

        $priceSpikePct = null;
        if ($beforeSpike && $afterSpike && $beforeSpike > 0) {
            $priceSpikePct = (($afterSpike - $beforeSpike) / $beforeSpike) * 100;

            if ($priceSpikePct >= self::INFLATION_THRESHOLD_PCT) {
                $reasons[] = sprintf(
                    'Price spiked %.1f%% within %d days before the submission window (from %s to %s). Possible artificial inflation.',
                    $priceSpikePct,
                    self::INFLATION_WINDOW_DAYS,
                    number_format($beforeSpike / 100, 2),
                    number_format($afterSpike / 100, 2)
                );
                $riskLevel = $riskLevel === 'high' ? 'high' : 'medium';
            }
        }

        // ── 3. Discount below flash sale minimum ──────────────────────────────
        $minPct = $submission->flashSale?->min_discount_pct;
        if ($minPct !== null && $submission->calculated_discount_pct < $minPct) {
            $reasons[] = sprintf(
                'Calculated discount (%.1f%%) is below the flash sale minimum (%.1f%%).',
                $submission->calculated_discount_pct,
                $minPct
            );
            $riskLevel = $riskLevel === 'none' ? 'low' : $riskLevel;
        }

        // ── 4. No price history at all ────────────────────────────────────────
        if ($recentPrices->isEmpty()) {
            $reasons[] = 'No price history found for this listing in the last 30 days. Cannot verify discount authenticity.';
            $riskLevel = $riskLevel === 'none' ? 'low' : $riskLevel;
        }

        return [
            'flagged'       => !empty($reasons),
            'risk_level'    => $riskLevel,
            'reasons'       => $reasons,
            'reference_avg' => $referenceAvg,
            'price_spike'   => $priceSpikePct !== null ? round($priceSpikePct, 2) : null,
        ];
    }
}
