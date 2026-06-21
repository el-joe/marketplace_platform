<?php

namespace App\Services\Vendor;

use App\Models\SubOrder;
use App\Models\VendorAdmin;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getSnapshot(VendorAdmin $admin): array
    {
        $vendorId = $admin->vendor_id;

        return Cache::remember("vendor_dashboard_{$vendorId}", 60, function () use ($admin, $vendorId) {
            $thirtyDaysAgo = now()->subDays(30);

            $todayRevenue = SubOrder::where('vendor_id', $vendorId)
                ->whereDate('created_at', today())
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('vendor_payout');

            $pendingCount = SubOrder::where('vendor_id', $vendorId)
                ->whereIn('status', ['placed', 'confirmed', 'processing', 'packed'])
                ->count();

            $lowStockCount = WarehouseInventory::whereHas(
                'vendorListing',
                fn ($q) => $q->where('vendor_id', $vendorId)
            )
                ->whereRaw('quantity_available <= COALESCE(reorder_point, 5)')
                ->where('quantity_available', '>=', 0)
                ->count();

            $unreadCount = $admin->notifications()
                ->whereNull('read_at')
                ->count();

            $recentOrders = SubOrder::where('vendor_id', $vendorId)
                ->with(['items', 'order:id,shipping_address_snapshot'])
                ->latest()
                ->limit(5)
                ->get();

            // On-time ship rate (last 30 days)
            $shippedLast30 = SubOrder::where('vendor_id', $vendorId)
                ->whereNotNull('shipped_at')
                ->where('shipped_at', '>=', $thirtyDaysAgo)
                ->count();

            $onTimeLast30 = SubOrder::where('vendor_id', $vendorId)
                ->whereNotNull('shipped_at')
                ->where('shipped_at', '>=', $thirtyDaysAgo)
                ->whereNotNull('sla_ship_deadline')
                ->whereColumn('shipped_at', '<=', 'sla_ship_deadline')
                ->count();

            $totalLast30 = SubOrder::where('vendor_id', $vendorId)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count();

            $cancelledLast30 = SubOrder::where('vendor_id', $vendorId)
                ->where('status', 'cancelled')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count();

            $slaBreachCount = SubOrder::where('vendor_id', $vendorId)
                ->whereNotNull('sla_ship_deadline')
                ->where('sla_ship_deadline', '<', now())
                ->whereNotIn('status', ['shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'returned', 'refunded'])
                ->count();

            $vendor = $admin->vendor;

            return [
                'today_revenue_cents'       => (int) $todayRevenue,
                'pending_orders_count'      => $pendingCount,
                'low_stock_count'           => $lowStockCount,
                'unread_notifications_count' => $unreadCount,
                'recent_orders'             => $recentOrders,
                'performance'               => [
                    'on_time_ship_rate'   => $shippedLast30 > 0
                        ? round(($onTimeLast30 / $shippedLast30) * 100, 2)
                        : null,
                    'cancellation_rate'   => $totalLast30 > 0
                        ? round(($cancelledLast30 / $totalLast30) * 100, 2)
                        : 0.0,
                    'avg_rating'          => (float) ($vendor->store_rating_avg ?? 0),
                ],
                'sla_breaches' => [
                    'count' => $slaBreachCount,
                ],
            ];
        });
    }
}
