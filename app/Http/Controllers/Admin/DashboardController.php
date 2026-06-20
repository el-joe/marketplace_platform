<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
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
    public function index(Request $request): View
    {
        $countries = Country::where('is_active', 1)->orderBy('name_en')->get();
        $defaultCountryId = auth()->guard('admin')->user()?->country_id;

        return view('admin.dashboard.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
            'countries' => $countries,
            'defaultCountryId' => $defaultCountryId,
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

        $currency = $countryId
            ? Country::find($countryId)?->currency_code
            : null;

        return response()->json([
            'data' => [
                'gmv' => $this->formatMoney($current['gmv'], $currency),
                'orders' => number_format($current['orders']),
                'revenue' => $this->formatMoney($current['revenue'], $currency),
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

        $countryId = $request->input('country_id');

        // Build date-bucketed GMV
        $orderRows = Order::query()
            ->selectRaw('DATE(placed_at) as date, SUM(total) as gmv')
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->when($countryId, fn($q) => $q->whereHas(
                'customer', fn($cq) => $cq->where('country_id', $countryId)
            ))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Commission is sourced from order_items.commission_amount in this schema.
        $commRows = collect();
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'commission_amount')) {
            $commRows = OrderItem::query()->from('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->selectRaw('DATE(o.placed_at) as date, SUM(oi.commission_amount) as commission')
                ->whereBetween('o.placed_at', [$start, $end])
                ->whereNotIn('o.status', ['cancelled'])
                ->when($countryId, function ($q) use ($countryId) {
                    $q->whereExists(function ($sub) use ($countryId) {
                        $sub->from('customers')
                            ->whereColumn('customers.id', 'o.customer_id')
                            ->where('customers.country_id', $countryId);
                    });
                })
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
    public function ordersByStatus(Request $request): JsonResponse
    {
        $period = $request->input('period', 'week');
        $countryId = $request->input('country_id');

        [$start, $end] = $this->periodWindowDates($period);

        // Maps the 6 UI labels to the actual orders.status enum values
        $statusGroups = [
            'Pending'    => ['placed'],
            'Processing' => ['confirmed', 'partially_shipped'],
            'Shipped'    => ['shipped', 'partially_delivered'],
            'Delivered'  => ['delivered', 'completed'],
            'Cancelled'  => ['cancelled'],
            'Refunded'   => ['refunded'],
        ];

        $colors = [
            'Pending'    => '#f59e0b',
            'Processing' => '#3b82f6',
            'Shipped'    => '#8b5cf6',
            'Delivered'  => '#22c55e',
            'Cancelled'  => '#ef4444',
            'Refunded'   => '#6b7280',
        ];

        $base = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->when($countryId, fn($q) => $q->whereHas(
                'customer', fn($cq) => $cq->where('country_id', $countryId)
            ));

        $labels = $values = $colorList = [];

        foreach ($statusGroups as $label => $statuses) {
            $count = (int) (clone $base)->whereIn('status', $statuses)->count();
            $labels[] = $label;
            $values[] = $count;
            $colorList[] = $colors[$label];
        }

        return response()->json(['data' => [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colorList,
        ]]);
    }

    /**
     * AJAX — last 10 orders for the activity feed.
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $countryId = $request->input('country_id');

        // Use the Eloquent relationship so SoftDeletes scope is applied correctly.
        $orders = Order::with('customer')
            ->when($countryId, fn($q) => $q->whereHas(
                'customer', fn($cq) => $cq->where('country_id', $countryId)
            ))
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '#—',
                'customer_name' => $order->customer?->name ?? 'Guest',
                'total' => number_format($order->total / 100, 2) . ' ' . ($order->currency ?? ''),
                'status' => $order->status,
                'created_at' => Carbon::parse($order->placed_at)->diffForHumans(),
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
            'vendors' => Vendor::query()->whereIn('global_status', ['pending', 'under_review'])->count(),
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
        [$start, $end] = $this->periodWindowDates($period);

        $lengthSeconds = $start->diffInSeconds($end);
        $prevEnd = $start->clone()->subSecond();
        $prevStart = $prevEnd->clone()->subSeconds($lengthSeconds);

        return [$start, $end, $prevStart, $prevEnd];
    }

    /** Returns [start, end] for the given period. */
    private function periodWindowDates(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }

    /** Fetch raw numeric stats for a time window. */
    private function fetchStats(Carbon $start, Carbon $end, ?string $countryId): array
    {
        $gmv = (int) Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->when($countryId, fn($q) => $q->whereHas(
                'customer', fn($cq) => $cq->where('country_id', $countryId)
            ))
            ->sum('total');

        $orders = (int) Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->when($countryId, fn($q) => $q->whereHas(
                'customer', fn($cq) => $cq->where('country_id', $countryId)
            ))
            ->count();

        $revenue = 0;
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'commission_amount')) {
            $revenue = (int) OrderItem::query()->from('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->whereBetween('o.placed_at', [$start, $end])
                ->whereNotIn('o.status', ['cancelled'])
                ->when($countryId, function ($q) use ($countryId) {
                    $q->whereExists(function ($sub) use ($countryId) {
                        $sub->from('customers')
                            ->whereColumn('customers.id', 'o.customer_id')
                            ->where('customers.country_id', $countryId);
                    });
                })
                ->sum('oi.commission_amount');
        }

        $sellers = (int) Vendor::query()
            ->where('global_status', 'active')
            ->when($countryId, fn($q) => $q->where('country_id', $countryId))
            ->count();

        return compact('gmv', 'orders', 'revenue', 'sellers');
    }

    /** Format a cents integer. Prepends currency code when known. */
    private function formatMoney(int|float|null $cents, ?string $currency = null): string
    {
        $amount = number_format((int) $cents / 100, 2);
        return $currency ? "{$amount} {$currency}" : $amount;
    }
}
