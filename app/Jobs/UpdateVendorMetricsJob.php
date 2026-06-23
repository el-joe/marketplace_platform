<?php

namespace App\Jobs;

use App\Models\Vendor;
use App\Models\VendorStrike;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateVendorMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string|null  $vendorId  Pass a specific vendor UUID to update only that vendor; null = all vendors.
     */
    public function __construct(public readonly ?string $vendorId = null)
    {
    }

    public function handle(): void
    {
        $query = Vendor::query()->whereIn('global_status', ['active', 'suspended', 'under_review']);

        if ($this->vendorId) {
            $query->where('id', $this->vendorId);
        }

        // Auto-expire stale strikes before aggregating
        VendorStrike::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $query->chunk(50, function ($vendors) {
            foreach ($vendors as $vendor) {
                $this->updateVendor($vendor);
            }
        });
    }

    private function updateVendor(Vendor $vendor): void
    {
        // ── GMV & total orders (from sub_orders) ─────────────────────────────
        $orderMetrics = DB::table('sub_orders')
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->selectRaw('COALESCE(SUM(vendor_payout), 0) as gmv, COUNT(*) as total_orders')
            ->first();

        // ── Return rate ───────────────────────────────────────────────────────
        $totalOrders = (int) $orderMetrics->total_orders;
        $returnedCount = DB::table('return_requests')
            ->join('sub_orders', 'return_requests.sub_order_id', '=', 'sub_orders.id')
            ->where('sub_orders.vendor_id', $vendor->id)
            ->whereIn('return_requests.status', ['approved', 'completed'])
            ->count();

        $returnRatePct = $totalOrders > 0
            ? round(($returnedCount / $totalOrders) * 100, 2)
            : 0.00;

        // ── Cancellation rate ─────────────────────────────────────────────────
        $cancelledCount = DB::table('sub_orders')
            ->where('vendor_id', $vendor->id)
            ->where('status', 'cancelled')
            ->count();

        $allOrdersCount = DB::table('sub_orders')
            ->where('vendor_id', $vendor->id)
            ->count();

        $cancellationRatePct = $allOrdersCount > 0
            ? round(($cancelledCount / $allOrdersCount) * 100, 2)
            : 0.00;

        // ── Store rating ──────────────────────────────────────────────────────
        $ratingMetrics = DB::table('reviews')
            ->where('vendor_id', $vendor->id)
            ->whereNull('deleted_at')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')
            ->first();

        // ── Active strikes ────────────────────────────────────────────────────
        $activeStrikes = $vendor->activeStrikes()->count();

        $vendor->updateQuietly([
            'total_sales' => round($orderMetrics->gmv / 100, 2), // stored in cents
            'total_orders' => $totalOrders,
            'return_rate_pct' => $returnRatePct,
            'cancellation_rate_pct' => $cancellationRatePct,
            'store_rating_avg' => round((float) $ratingMetrics->avg_rating, 2),
            'store_rating_count' => (int) $ratingMetrics->rating_count,
            'strikes_count' => $activeStrikes,
        ]);
    }
}
