<?php

namespace App\Services;

use App\Enums\GlobalSystemType;
use App\Enums\PaymentTransactionStatus;
use App\Enums\RefundStatus;
use App\Models\Category;
use App\Models\MarketerSampleItem;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Models\SubOrder;
use App\Models\Vendor;
use App\Models\VendorInfluencerPromotionRequest;
use Carbon\Carbon;

class PayoutCalculationService
{
    /**
     * Sub-order statuses that are eligible for payout calculation.
     */
    private const PAYABLE_STATUSES = ['delivered', 'completed'];

    /**
     * Calculate payout summaries for a vendor over a given period, grouped by currency.
     *
     * sub_orders carry no currency column — currency is on the parent order.
     * Returns one entry per currency found in payable sub-orders for this vendor/period.
     *
     * BUSINESS DECISION (flag for finance team): when a vendor's sub-orders are in SAR
     * but their bank account is denominated in USD, we pay out in the SALES currency
     * (SAR) and let the payment rail handle any cross-currency conversion on their end,
     * where the exchange rate is stated and auditable. If a convert-at-payout approach
     * is preferred instead, a separate FX step with an explicit logged rate must be added
     * here — do NOT silently absorb or profit from FX spread.
     *
     * @return array<string, array{
     *   vendor_id: string,
     *   period_start: string,
     *   period_end: string,
     *   gross_sales: int,
     *   commission: int,
     *   gateway_fee_deducted: int,
     *   refunds_deducted: int,
     *   chargebacks_deducted: int,
     *   storage_fees: int,
     *   ad_fees: int,
     *   other_adjustments: int,
     *   net_amount: int,
     *   currency: string
     * }> Keyed by ISO currency code.
     */
    public function calculateForVendor(Vendor $vendor, Carbon $from, Carbon $to): array
    {
        // sub_orders has no currency column; join orders to get the order currency
        // so we can group and produce per-currency totals.
        $rows = SubOrder::where('sub_orders.vendor_id', $vendor->id)
            ->whereIn('sub_orders.status', self::PAYABLE_STATUSES)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('sub_orders.delivered_at', [$from->startOfDay(), $to->endOfDay()])
                  ->orWhereBetween('sub_orders.created_at', [$from->startOfDay(), $to->endOfDay()]);
            })
            ->where(function ($q) {
                // COD sub_orders are blocked until cash has been remitted by the delivery agent.
                // Non-COD sub_orders are always eligible regardless of the flag, since nothing
                // sets cod_remittance_confirmed=true for non-COD orders after creation.
                $q->whereHas('order', fn($q) => $q->where('payment_method', '!=', 'cod'))
                  ->orWhere('sub_orders.cod_remittance_confirmed', true);
            })
            ->join('orders', 'orders.id', '=', 'sub_orders.order_id')
            ->selectRaw('
                orders.currency                                        AS currency,
                COALESCE(SUM(sub_orders.subtotal), 0)            AS gross_sales,
                COALESCE(SUM(sub_orders.platform_commission), 0) AS commission,
                COALESCE(SUM(sub_orders.gateway_fee), 0)         AS gateway_fee_total,
                COALESCE(SUM(sub_orders.vendor_payout), 0)       AS vendor_payout_total
            ')
            ->groupBy('orders.currency')
            ->get();

        $results = [];

        foreach ($rows as $row) {
            $currency    = $row->currency;
            $grossSales  = (int) $row->gross_sales;
            $commission  = (int) $row->commission;
            $gatewayFee  = (int) $row->gateway_fee_total;

            // Refunds where the vendor bears the cost — filter by matching currency.
            $refundsDeducted = (int) Refund::whereHas('subOrder', fn($q) => $q->where('vendor_id', $vendor->id))
                ->where('vendor_charged_back', true)
                ->where('status', RefundStatus::Completed->value)
                ->where('currency', $currency)
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->sum('amount');

            // Chargebacks — payment_transactions.currency exists; filter by it.
            $chargebacksDeducted = (int) PaymentTransaction::where('type', 'chargeback')
                ->whereHas('order', fn($q) => $q->whereHas('subOrders', fn($s) => $s->where('vendor_id', $vendor->id)))
                ->where('status', PaymentTransactionStatus::Succeeded->value)
                ->where('currency', $currency)
                ->whereBetween('processed_at', [$from->startOfDay(), $to->endOfDay()])
                ->sum('amount');

            $promotionRequests = $this->pendingPromotionFeeRequests($vendor, $currency);
            $promotionFeeTotal = $promotionRequests->sum('total_promotion_fee');

            $sampleItems = $this->pendingSampleItems($vendor, $currency);
            $sampleCommissionTotal = $sampleItems->sum(fn($entry) => $entry['commission_amount']);

            $netAmount = $grossSales - $commission - $gatewayFee - $refundsDeducted - $chargebacksDeducted - $promotionFeeTotal - $sampleCommissionTotal;

            $results[$currency] = [
                'vendor_id'                  => $vendor->id,
                'period_start'               => $from->toDateString(),
                'period_end'                 => $to->toDateString(),
                'gross_sales'                => $grossSales,
                'commission'                 => $commission,
                'gateway_fee_deducted'       => $gatewayFee,
                'refunds_deducted'           => $refundsDeducted,
                'chargebacks_deducted'       => $chargebacksDeducted,
                'storage_fees'               => 0,
                'ad_fees'                    => 0,
                'other_adjustments'          => -$promotionFeeTotal - $sampleCommissionTotal,
                'net_amount'                 => max(0, $netAmount),
                'currency'                   => $currency,
                'promotion_fee_requests'     => $promotionRequests,
                'sample_items'               => $sampleItems,
            ];
        }

        // Currencies that have promotion fees due but no sub_orders in the period
        // (e.g. a vendor whose only activity this period is influencer promotions)
        // still need a payout row so the fee gets deducted and fee_deducted flips.
        $pendingCurrencies = VendorInfluencerPromotionRequest::where('vendor_id', $vendor->id)
            ->where('fee_deducted', false)
            ->where('fee_deduction_timing', 'weekly_settlement')
            ->whereIn('status', ['partially_accepted', 'fully_accepted', 'completed'])
            ->distinct()
            ->pluck('currency');

        $pendingSampleCurrencies = MarketerSampleItem::where('fee_deducted', false)
            ->whereHas('sampleRequest', fn($q) => $q->where('vendor_id', $vendor->id)->where('status', 'received'))
            ->join('vendor_listings', 'vendor_listings.id', '=', 'marketer_sample_items.vendor_listing_id')
            ->distinct()
            ->pluck('vendor_listings.currency');

        foreach ($pendingCurrencies->merge($pendingSampleCurrencies)->unique() as $currency) {
            if (isset($results[$currency])) {
                continue;
            }

            $promotionRequests = $this->pendingPromotionFeeRequests($vendor, $currency);
            $promotionFeeTotal = $promotionRequests->sum('total_promotion_fee');

            $sampleItems = $this->pendingSampleItems($vendor, $currency);
            $sampleCommissionTotal = $sampleItems->sum(fn($entry) => $entry['commission_amount']);

            $totalDue = $promotionFeeTotal + $sampleCommissionTotal;

            if ($totalDue <= 0) {
                continue;
            }

            $results[$currency] = [
                'vendor_id'                  => $vendor->id,
                'period_start'               => $from->toDateString(),
                'period_end'                 => $to->toDateString(),
                'gross_sales'                => 0,
                'commission'                 => 0,
                'gateway_fee_deducted'       => 0,
                'refunds_deducted'           => 0,
                'chargebacks_deducted'       => 0,
                'storage_fees'               => 0,
                'ad_fees'                    => 0,
                'other_adjustments'          => -$totalDue,
                'net_amount'                 => max(0, -$totalDue),
                'currency'                   => $currency,
                'promotion_fee_requests'     => $promotionRequests,
                'sample_items'               => $sampleItems,
            ];
        }

        return $results;
    }

    /**
     * Influencer promotion requests due for weekly-settlement fee deduction,
     * for a single vendor/currency. Never mixed across currencies.
     */
    private function pendingPromotionFeeRequests(Vendor $vendor, string $currency): \Illuminate\Support\Collection
    {
        return VendorInfluencerPromotionRequest::where('vendor_id', $vendor->id)
            ->where('currency', $currency)
            ->where('fee_deducted', false)
            ->where('fee_deduction_timing', 'weekly_settlement')
            ->whereIn('status', ['partially_accepted', 'fully_accepted', 'completed'])
            ->with(['vendorListing.productVariant.product', 'adminListing.productVariant.product', 'items'])
            ->get();
    }

    /**
     * Marketer sample items (influencer promotion samples) whose admin commission has
     * not yet been deducted, for a single vendor/currency. Samples are treated as a
     * normal purchase for commission purposes: admin fixed + variable commission is
     * deducted from sample_cost (the cost already borne by the vendor), using FLOOR()
     * for the percentage component. The vendor statement shows the product price as
     * ZERO ("Promotion Sample") — only the commission is actually deducted here.
     *
     * admin_quantity is platform-internal and never factors into the vendor-facing
     * commission/statement line — only sample_cost (the vendor-borne cost) is used.
     *
     * @return \Illuminate\Support\Collection<int, array{item: MarketerSampleItem, commission_amount: int, commission_pct: float, commission_fixed: int}>
     */
    private function pendingSampleItems(Vendor $vendor, string $currency): \Illuminate\Support\Collection
    {
        return MarketerSampleItem::where('fee_deducted', false)
            ->whereHas('sampleRequest', fn($q) => $q->where('vendor_id', $vendor->id)->where('status', 'received'))
            ->whereHas('vendorListing', fn($q) => $q->where('currency', $currency))
            ->with(['sampleRequest', 'vendorListing.productVariant.product.category'])
            ->get()
            ->filter(fn(MarketerSampleItem $item) => $item->sample_cost > 0)
            ->map(function (MarketerSampleItem $item) {
                $listing = $item->vendorListing;
                $isFBN = $listing?->global_system_type === GlobalSystemType::ExpressFbn;
                $category = $listing?->productVariant?->product?->category;
                $resolvedCategory = $this->resolveSampleCommissionCategory($category, $isFBN);

                $pct = (float) ($isFBN ? $resolvedCategory?->commission_fbn_pct : $resolvedCategory?->commission_fbp_pct) ?: 0.0;
                $fixed = (int) ($isFBN ? $resolvedCategory?->commission_fbn_fixed : $resolvedCategory?->commission_fbp_fixed) ?: 0;

                $pctComponent = (int) floor($item->sample_cost * ($pct / 100));
                $commissionAmount = $pctComponent + $fixed;

                return [
                    'item'               => $item,
                    'commission_amount'  => $commissionAmount,
                    'commission_pct'     => $pct,
                    'commission_fixed'   => $fixed,
                ];
            });
    }

    /**
     * Walk up the category parent chain (max 5 levels) looking for the first
     * category with a non-zero commission rate or fixed fee for the given
     * fulfillment type. Mirrors CheckoutCalculationService::resolveCommissionCategory.
     */
    private function resolveSampleCommissionCategory(?Category $category, bool $isFBN): ?Category
    {
        $current = $category;
        $levels = 0;

        while ($current !== null && $levels < 5) {
            $pct = (float) ($isFBN ? $current->commission_fbn_pct : $current->commission_fbp_pct);
            $fixed = (int) ($isFBN ? $current->commission_fbn_fixed : $current->commission_fbp_fixed);

            if ($pct > 0 || $fixed > 0) {
                return $current;
            }

            $current = $current->parent;
            $levels++;
        }

        return $category;
    }
}
