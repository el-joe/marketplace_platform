<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payout;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Vendor;
use App\Models\WarehouseInventory;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
        ]);
    }

    /**
     * AJAX — stat cards.
     * Accepts: period (today|week|month), country_id
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');
        $countryId = $request->input('country_id');

        [$currentStart, $currentEnd, $prevStart, $prevEnd] = $this->periodDates($period);

        $current = $this->fetchStats($currentStart, $currentEnd, $countryId);
        $previous = $this->fetchStats($prevStart, $prevEnd, $countryId);

        $changes = [];
        foreach (['gmv', 'orders', 'revenue', 'sellers'] as $key) {
            $prev = $previous[$key] ?? 0;
            $curr = $current[$key] ?? 0;
            $changes[$key] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 1)
                : ($curr > 0 ? 100.0 : 0.0);
        }

        return response()->json([
            'data' => [
                'gmv' => $this->formatMoney($current['gmv']),
                'orders' => number_format($current['orders']),
                'revenue' => $this->formatMoney($current['revenue']),
                'sellers' => number_format($current['sellers']),
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * AJAX — revenue line chart.
     * Accepts: range (7|30|90), country_id
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $days = (int) $request->input('range', 30);
        $days = in_array($days, [7, 30, 90]) ? $days : 30;

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $orderTotalColumn = $this->orderTotalColumn();
        $hasOrderCountry = Schema::hasColumn('orders', 'country_id');
        $countryId = $request->input('country_id');

        // Build date-bucketed GMV
        $orderRows = Order::query()
            ->selectRaw("DATE(created_at) as date, SUM({$orderTotalColumn}) as gmv")
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($countryId && $hasOrderCountry, fn($q) => $q->where('country_id', $countryId))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Commission is sourced from order_items.commission_amount in this schema.
        $commRows = collect();
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'commission_amount')) {
            $commRows = OrderItem::query()->from('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->selectRaw('DATE(o.created_at) as date, SUM(oi.commission_amount) as commission')
                ->whereBetween('o.created_at', [$start, $end])
                ->whereNotIn('o.status', ['cancelled', 'refunded'])
                ->when($countryId && $hasOrderCountry, fn($q) => $q->where('o.country_id', $countryId))
                ->groupBy('date')
                ->get()
                ->keyBy('date');
        }

        $labels = [];
        $gmvData = [];
        $commData = [];
        $labelFmt = $days > 30 ? 'M d' : 'd M';

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format($labelFmt);
            $gmvData[] = (int) ($orderRows[$date]->gmv ?? 0);
            $commData[] = (int) ($commRows[$date]->commission ?? 0);
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'gmv' => $gmvData,
                'commission' => $commData,
            ],
        ]);
    }

    /**
     * AJAX — donut chart: orders broken down by status.
     */
    public function ordersByStatus(): JsonResponse
    {
        $rawCounts = Order::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusConfig = [
            'pending' => ['label' => 'Pending', 'color' => '#f59e0b'],
            'processing' => ['label' => 'Processing', 'color' => '#3b82f6'],
            'shipped' => ['label' => 'Shipped', 'color' => '#8b5cf6'],
            'delivered' => ['label' => 'Delivered', 'color' => '#22c55e'],
            'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
            'refunded' => ['label' => 'Refunded', 'color' => '#6b7280'],
        ];

        $labels = $values = $colors = [];

        foreach ($statusConfig as $status => $cfg) {
            $count = (int) ($rawCounts[$status] ?? 0);
            if ($count > 0) {
                $labels[] = $cfg['label'];
                $values[] = $count;
                $colors[] = $cfg['color'];
            }
        }

        // Ensure at least placeholder data so chart renders on a fresh install
        if (empty($values)) {
            $labels = array_column($statusConfig, 'label');
            $values = array_fill(0, count($statusConfig), 0);
            $colors = array_column($statusConfig, 'color');
        }

        return response()->json(['data' => compact('labels', 'values', 'colors')]);
    }

    /**
     * AJAX — last 10 orders for the activity feed.
     */
    public function recentOrders(): JsonResponse
    {
        $orders = Order::query()->from('orders as o')
            ->select([
                'o.id',
                'o.order_number',
                'o.total',
                'o.status',
                'o.created_at',
                DB::raw("COALESCE(c.name, 'Guest') as customer_name"),
            ])
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->orderByDesc('o.created_at')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'id' => $row->id,
                'order_number' => $row->order_number ?? '#—',
                'customer_name' => $row->customer_name,
                'total' => $this->formatMoney($row->total),
                'status' => $row->status,
                'created_at' => Carbon::parse($row->created_at)->diffForHumans(),
            ]);

        return response()->json(['data' => $orders]);
    }

    /**
     * AJAX — top 10 sellers by GMV (last 30 days).
     */
    public function topSellers(): JsonResponse
    {
        $sellers = OrderItem::query()->from('order_items as oi')
            ->select([
                'v.id',
                'v.business_name',
                DB::raw('SUM(oi.line_total) as gmv'),
                DB::raw('COUNT(DISTINCT oi.order_id) as order_count'),
                DB::raw('AVG(v.store_rating_avg) as rating'),
            ])
            ->join('vendors as v', 'v.id', '=', 'oi.vendor_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.created_at', '>=', now()->subDays(30))
            ->whereNotIn('o.status', ['cancelled', 'refunded'])
            ->groupBy('v.id', 'v.business_name', 'v.store_rating_avg')
            ->orderByDesc('gmv')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'id' => $row->id,
                'business_name' => $row->business_name,
                'gmv' => $this->formatMoney($row->gmv),
                'order_count' => (int) $row->order_count,
                'rating' => $row->rating ? number_format($row->rating, 1) : '—',
            ]);

        return response()->json(['data' => $sellers]);
    }

    /**
     * AJAX — counts of items awaiting admin action.
     */
    public function pendingItems(): JsonResponse
    {
        $counts = [
            'products' => Product::query()->where('status', 'pending_review')->count(),
            'vendors' => Vendor::query()->whereIn('status', ['pending', 'pending_approval'])->count(),
            'disputes' => Dispute::query()->where('status', 'open')->count(),
            'withdrawals' => Schema::hasTable('withdrawal_requests')
                ? WithdrawalRequest::query()->where('status', 'pending')->count()
                : Payout::query()->whereIn('status', ['pending', 'requested'])->count(),
            'returns' => ReturnRequest::query()->where('status', 'pending')->count(),
        ];

        $total = array_sum($counts);

        return response()->json(['data' => array_merge($counts, ['total' => $total])]);
    }

    /**
     * AJAX — products with stock ≤ 5.
     */
    public function lowStock(): JsonResponse
    {
        $products = WarehouseInventory::query()->from('warehouse_inventories as wi')
            ->select([
                'p.id as product_id',
                'p.name_en as name',
                'wi.quantity_available as stock',
                'v.business_name as vendor',
            ])
            ->join('vendor_listings as vl', 'vl.id', '=', 'wi.vendor_listing_id')
            ->join('product_variants as pv', 'pv.id', '=', 'vl.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->join('vendors as v', 'v.id', '=', 'vl.vendor_id')
            ->where('wi.quantity_available', '<=', 5)
            ->whereIn('vl.status', ['active', 'approved'])
            ->orderBy('wi.quantity_available')
            ->limit(20)
            ->get()
            ->map(fn($row) => [
                'id' => $row->product_id,
                'name' => $row->name,
                'stock' => (int) $row->stock,
                'vendor' => $row->vendor ?? '—',
            ]);

        return response()->json(['data' => $products]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /** Returns [currentStart, currentEnd, previousStart, previousEnd] as Carbon instances. */
    private function periodDates(string $period): array
    {
        [$start, $end] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],  // week
        };

        $lengthSeconds = $start->diffInSeconds($end);
        $prevEnd = $start->clone()->subSecond();
        $prevStart = $prevEnd->clone()->subSeconds($lengthSeconds);

        return [$start, $end, $prevStart, $prevEnd];
    }

    /** Fetch raw numeric stats for a time window. */
    private function fetchStats(Carbon $start, Carbon $end, ?string $countryId): array
    {
        $orderTotalColumn = $this->orderTotalColumn();
        $hasOrderCountry = Schema::hasColumn('orders', 'country_id');

        $gmv = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($countryId && $hasOrderCountry, fn($q) => $q->where('country_id', $countryId))
            ->sum($orderTotalColumn);

        $orders = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->when($countryId && $hasOrderCountry, fn($q) => $q->where('country_id', $countryId))
            ->count();

        $revenue = 0;
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'commission_amount')) {
            $revenue = (int) OrderItem::query()->from('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->whereBetween('o.created_at', [$start, $end])
                ->whereNotIn('o.status', ['cancelled', 'refunded'])
                ->when($countryId && $hasOrderCountry, fn($q) => $q->where('o.country_id', $countryId))
                ->sum('oi.commission_amount');
        }

        $sellers = (int) Vendor::query()->where('status', 'active')->count();

        return compact('gmv', 'orders', 'revenue', 'sellers');
    }

    private function orderTotalColumn(): string
    {
        if (Schema::hasColumn('orders', 'total_amount')) {
            return 'total_amount';
        }

        return 'total';
    }

    /** Format a cents integer as "EGP 1,234.56". */
    private function formatMoney(int|float|null $cents): string
    {
        return 'EGP ' . number_format((int) $cents / 100, 2);
    }
}
