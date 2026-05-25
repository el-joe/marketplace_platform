<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\SubOrder;
use App\Models\Vendor;
use Carbon\Carbon;

class PayoutCalculationService
{
    /**
     * Sub-order statuses that are eligible for payout calculation.
     */
    private const PAYABLE_STATUSES = ['delivered', 'completed'];

    /**
     * Calculate a payout summary for a vendor over a given period.
     *
     * Uses sub_orders.subtotal and sub_orders.platform_commission (bigint, no _cents).
     * All returned amounts are in the lowest currency unit (cents/halala/etc.).
     *
     * @return array{
     *   vendor_id: string,
     *   period_start: string,
     *   period_end: string,
     *   gross_sales: int,
     *   commission: int,
     *   refunds_deducted: int,
     *   chargebacks_deducted: int,
     *   storage_fees: int,
     *   ad_fees: int,
     *   other_adjustments: int,
     *   net_amount: int,
     *   currency: string
     * }
     */
    public function calculateForVendor(Vendor $vendor, Carbon $from, Carbon $to): array
    {
        $stats = SubOrder::where('vendor_id', $vendor->id)
            ->whereIn('status', self::PAYABLE_STATUSES)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('delivered_at', [$from->startOfDay(), $to->endOfDay()])
                  ->orWhereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
            })
            ->selectRaw('
                COALESCE(SUM(subtotal), 0)            AS gross_sales,
                COALESCE(SUM(platform_commission), 0) AS commission,
                COALESCE(SUM(vendor_payout), 0)       AS vendor_payout_total
            ')
            ->first();

        $grossSales = (int) ($stats->gross_sales ?? 0);
        $commission = (int) ($stats->commission ?? 0);

        // Refunds where the vendor is held responsible (vendor_charged_back=true)
        $refundsDeducted = (int) Refund::whereHas('subOrder', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })
            ->where('vendor_charged_back', true)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount');

        // Chargebacks (payment transactions of type 'chargeback')
        $chargebacksDeducted = (int) \App\Models\PaymentTransaction::where('type', 'chargeback')
            ->whereHas('order', function ($q) use ($vendor) {
                $q->whereHas('subOrders', fn($s) => $s->where('vendor_id', $vendor->id));
            })
            ->where('status', 'succeeded')
            ->whereBetween('processed_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount');

        $netAmount = $grossSales - $commission - $refundsDeducted - $chargebacksDeducted;

        return [
            'vendor_id'            => $vendor->id,
            'period_start'         => $from->toDateString(),
            'period_end'           => $to->toDateString(),
            'gross_sales'          => $grossSales,
            'commission'           => $commission,
            'refunds_deducted'     => $refundsDeducted,
            'chargebacks_deducted' => $chargebacksDeducted,
            'storage_fees'         => 0,
            'ad_fees'              => 0,
            'other_adjustments'    => 0,
            'net_amount'           => max(0, $netAmount),
            'currency'             => 'USD', // caller may override from vendor settings
        ];
    }
}
