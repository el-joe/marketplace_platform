<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ── Period resolution ────────────────────────────────────────────────────

    /**
     * Returns ['from', 'to', 'prev_from', 'prev_to'] as Carbon instances.
     */
    private function dateRange(Request $request): array
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $from = $now->copy()->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'week':
                $from = $now->copy()->subDays(6)->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'quarter':
                $from = $now->copy()->subDays(89)->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'year':
                $from = $now->copy()->subDays(364)->startOfDay();
                $to = $now->copy()->endOfDay();
                break;
            case 'custom':
                $from = Carbon::parse($request->input('date_from', $now->copy()->subDays(29)->toDateString()))->startOfDay();
                $to = Carbon::parse($request->input('date_to', $now->toDateString()))->endOfDay();
                break;
            default: // month
                $from = $now->copy()->subDays(29)->startOfDay();
                $to = $now->copy()->endOfDay();
        }

        $diff = $from->diffInSeconds($to);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($diff);

        return compact('from', 'to', 'prevFrom', 'prevTo');
    }

    private function cacheKey(string $method, Request $request): string
    {
        $period = $request->input('period', 'month');
        $countryId = $request->input('country_id', 'all');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');
        return "analytics.{$method}.{$period}.{$countryId}.{$dateFrom}.{$dateTo}";
    }

    private function cacheTtl(string $period): int
    {
        return ($period === 'today') ? 300 : 3600;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function changePct($current, $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;
        $pct = $previous > 0 ? round((($current - $previous) / $previous) * 100, 2) : null;

        return [
            'value' => $current,
            'previous' => $previous,
            'change_pct' => $pct,
            'direction' => $pct === null ? 'flat' : ($pct >= 0 ? 'up' : 'down'),
        ];
    }

    private function countryJoin(string $alias = 'o'): string
    {
        return '';  // Applied per-query where country_id is directly available
    }

    // ── Overview ─────────────────────────────────────────────────────────────

    public function overview(Request $request): array
    {
        $key = $this->cacheKey('overview', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $countryId = $request->input('country_id');

            $countryJoin = $countryId ? 'JOIN customers c ON o.customer_id = c.id' : '';
            $countryWhere = $countryId ? ' AND c.country_id = ?' : '';
            $bindings = fn($from, $to) => array_filter(
                [$from->toDateTimeString(), $to->toDateTimeString(), $countryId ?: null],
                fn($v) => $v !== null
            );

            // Current-period order metrics
            $cur = DB::selectOne("
                SELECT
                    COALESCE(SUM(o.total), 0)                            AS gmv,
                    COALESCE(SUM(so.commission), 0)                      AS commission,
                    COUNT(DISTINCT o.id)                                  AS orders_count
                FROM orders o
                {$countryJoin}
                LEFT JOIN (
                    SELECT order_id, SUM(platform_commission) AS commission
                    FROM sub_orders GROUP BY order_id
                ) so ON so.order_id = o.id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                {$countryWhere}
            ", array_values($bindings($r['from'], $r['to'])));

            $prev = DB::selectOne("
                SELECT
                    COALESCE(SUM(o.total), 0)                            AS gmv,
                    COALESCE(SUM(so.commission), 0)                      AS commission,
                    COUNT(DISTINCT o.id)                                  AS orders_count
                FROM orders o
                {$countryJoin}
                LEFT JOIN (
                    SELECT order_id, SUM(platform_commission) AS commission
                    FROM sub_orders GROUP BY order_id
                ) so ON so.order_id = o.id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                {$countryWhere}
            ", array_values($bindings($r['prevFrom'], $r['prevTo'])));

            $curGmv = $cur->gmv / 100;
            $prevGmv = $prev->gmv / 100;
            $curOrders = (int) $cur->orders_count;
            $prevOrders = (int) $prev->orders_count;
            $curAov = $curOrders > 0 ? $curGmv / $curOrders : 0;
            $prevAov = $prevOrders > 0 ? $prevGmv / $prevOrders : 0;

            // New customers
            $newCur = DB::selectOne(
                'SELECT COUNT(*) AS cnt FROM customers WHERE created_at >= ? AND created_at <= ?',
                [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]
            );
            $newPrev = DB::selectOne(
                'SELECT COUNT(*) AS cnt FROM customers WHERE created_at >= ? AND created_at <= ?',
                [$r['prevFrom']->toDateTimeString(), $r['prevTo']->toDateTimeString()]
            );

            // Active vendors (had sub-orders in period)
            $avCur = DB::selectOne("
                SELECT COUNT(DISTINCT so.vendor_id) AS cnt
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);
            $avPrev = DB::selectOne("
                SELECT COUNT(DISTINCT so.vendor_id) AS cnt
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
            ", [$r['prevFrom']->toDateTimeString(), $r['prevTo']->toDateTimeString()]);

            // SLA breach rate
            $slaCur = DB::selectOne("
                SELECT
                    COALESCE(SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END), 0) AS breached,
                    COUNT(so.id) AS total
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);
            $slaPrev = DB::selectOne("
                SELECT
                    COALESCE(SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END), 0) AS breached,
                    COUNT(so.id) AS total
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
            ", [$r['prevFrom']->toDateTimeString(), $r['prevTo']->toDateTimeString()]);

            $slaCompCur = $slaCur->total > 0 ? round((1 - $slaCur->breached / $slaCur->total) * 100, 2) : 100.0;
            $slaCompPrev = $slaPrev->total > 0 ? round((1 - $slaPrev->breached / $slaPrev->total) * 100, 2) : 100.0;

            // Return rate
            $retCur = DB::selectOne("
                SELECT COUNT(*) AS cnt FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);
            $retPrev = DB::selectOne("
                SELECT COUNT(*) AS cnt FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
            ", [$r['prevFrom']->toDateTimeString(), $r['prevTo']->toDateTimeString()]);

            $retRateCur = $curOrders > 0 ? round(($retCur->cnt / $curOrders) * 100, 2) : 0.0;
            $retRatePrev = $prevOrders > 0 ? round(($retPrev->cnt / $prevOrders) * 100, 2) : 0.0;

            return [
                'gmv' => $this->changePct($curGmv, $prevGmv),
                'revenue' => $this->changePct($cur->commission / 100, $prev->commission / 100),
                'orders_count' => $this->changePct($curOrders, $prevOrders),
                'avg_order_value' => $this->changePct($curAov, $prevAov),
                'new_customers' => $this->changePct($newCur->cnt, $newPrev->cnt),
                'active_vendors' => $this->changePct($avCur->cnt, $avPrev->cnt),
                'sla_compliance' => $this->changePct($slaCompCur, $slaCompPrev),
                'return_rate' => $this->changePct($retRateCur, $retRatePrev),
            ];
        });
    }

    // ── Revenue chart ─────────────────────────────────────────────────────────

    public function revenueChart(Request $request): array
    {
        $key = $this->cacheKey('revenue-chart', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT
                    DATE(o.placed_at)                           AS date,
                    COALESCE(SUM(o.total), 0)                   AS gmv,
                    COALESCE(SUM(so.commission), 0)             AS commission,
                    COALESCE(SUM(so.payout), 0)                 AS vendor_payouts,
                    COALESCE(SUM(rf.refunded), 0)               AS refunds
                FROM orders o
                LEFT JOIN (
                    SELECT order_id,
                           SUM(platform_commission) AS commission,
                           SUM(vendor_payout)       AS payout
                    FROM sub_orders GROUP BY order_id
                ) so ON so.order_id = o.id
                LEFT JOIN (
                    SELECT order_id, SUM(amount) AS refunded
                    FROM refunds WHERE status = 'completed'
                    GROUP BY order_id
                ) rf ON rf.order_id = o.id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY DATE(o.placed_at)
                ORDER BY date ASC
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            $labels = [];
            $gmv = $commission = $payouts = $refunds = [];

            foreach ($rows as $row) {
                $labels[] = $row->date;
                $gmv[] = (float) ($row->gmv / 100);
                $commission[] = (float) ($row->commission / 100);
                $payouts[] = (float) ($row->vendor_payouts / 100);
                $refunds[] = (float) ($row->refunds / 100);
            }

            return compact('labels', 'gmv', 'commission', 'payouts', 'refunds');
        });
    }

    // ── Orders by status ──────────────────────────────────────────────────────

    public function ordersByStatus(Request $request): array
    {
        $key = $this->cacheKey('orders-by-status', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT status, COUNT(*) AS cnt
                FROM orders
                WHERE placed_at >= ? AND placed_at <= ? AND deleted_at IS NULL
                GROUP BY status
                ORDER BY cnt DESC
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            $colorMap = [
                'placed' => '#6366f1',
                'confirmed' => '#8b5cf6',
                'partially_shipped' => '#f59e0b',
                'shipped' => '#3b82f6',
                'partially_delivered' => '#06b6d4',
                'delivered' => '#10b981',
                'completed' => '#22c55e',
                'cancelled' => '#ef4444',
                'refunded' => '#f97316',
                'disputed' => '#dc2626',
            ];

            $labels = $counts = $colors = [];
            foreach ($rows as $row) {
                $labels[] = ucfirst(str_replace('_', ' ', $row->status));
                $counts[] = (int) $row->cnt;
                $colors[] = $colorMap[$row->status] ?? '#94a3b8';
            }

            return compact('labels', 'counts', 'colors');
        });
    }

    // ── Orders by payment method ──────────────────────────────────────────────

    public function ordersByPaymentMethod(Request $request): array
    {
        $key = $this->cacheKey('orders-by-payment', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT
                    payment_method,
                    COUNT(*) AS orders_count,
                    COALESCE(SUM(total), 0) AS total_amount
                FROM orders
                WHERE placed_at >= ? AND placed_at <= ? AND deleted_at IS NULL
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            $labels = $counts = $amounts = [];
            foreach ($rows as $row) {
                $labels[] = strtoupper($row->payment_method);
                $counts[] = (int) $row->orders_count;
                $amounts[] = (float) ($row->total_amount / 100);
            }

            return compact('labels', 'counts', 'amounts');
        });
    }

    // ── Top products ─────────────────────────────────────────────────────────

    public function topProducts(Request $request): array
    {
        $key = $this->cacheKey('top-products', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT
                    p.id,
                    p.name_en                           AS name,
                    p.sku                               AS sku,
                    SUM(oi.quantity)                    AS units_sold,
                    COALESCE(SUM(oi.line_total), 0)     AS revenue
                FROM order_items oi
                JOIN product_variants pv ON pv.id = oi.product_variant_id
                JOIN products p          ON p.id  = pv.product_id
                JOIN orders o            ON o.id  = oi.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY p.id, p.name_en, p.sku
                ORDER BY units_sold DESC
                LIMIT 20
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            return array_map(fn($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'units_sold' => (int) $row->units_sold,
                'revenue' => round($row->revenue / 100, 2),
            ], $rows);
        });
    }

    // ── Top vendors ──────────────────────────────────────────────────────────

    public function topVendors(Request $request): array
    {
        $key = $this->cacheKey('top-vendors', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT
                    v.id,
                    v.store_name                                  AS store_name,
                    v.store_rating_avg                            AS rating,
                    COUNT(DISTINCT so.id)                         AS orders_count,
                    COALESCE(SUM(so.subtotal), 0)                 AS gmv,
                    COALESCE(SUM(so.platform_commission), 0)      AS commission
                FROM sub_orders so
                JOIN vendors v  ON v.id  = so.vendor_id
                JOIN orders o   ON o.id  = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY v.id, v.store_name, v.store_rating_avg
                ORDER BY gmv DESC
                LIMIT 20
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            return array_map(fn($row) => [
                'id' => $row->id,
                'store_name' => $row->store_name,
                'rating' => (float) $row->rating,
                'orders_count' => (int) $row->orders_count,
                'gmv' => round($row->gmv / 100, 2),
                'commission' => round($row->commission / 100, 2),
            ], $rows);
        });
    }

    // ── Top categories ────────────────────────────────────────────────────────

    public function topCategories(Request $request): array
    {
        $key = $this->cacheKey('top-categories', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            $rows = DB::select("
                SELECT
                    cat.id,
                    cat.name_en                             AS name,
                    SUM(oi.quantity)                        AS units_sold,
                    COALESCE(SUM(oi.line_total), 0)         AS revenue
                FROM order_items oi
                JOIN product_variants pv ON pv.id = oi.product_variant_id
                JOIN products p          ON p.id  = pv.product_id
                JOIN categories cat      ON cat.id = p.category_id
                JOIN orders o            ON o.id  = oi.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY cat.id, cat.name_en
                ORDER BY revenue DESC
                LIMIT 15
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            $labels = $revenues = [];
            foreach ($rows as $row) {
                $labels[] = $row->name;
                $revenues[] = round($row->revenue / 100, 2);
            }

            return compact('labels', 'revenues');
        });
    }

    // ── Customer stats ────────────────────────────────────────────────────────

    public function customerStats(Request $request): array
    {
        $key = $this->cacheKey('customer-stats', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);

            // Daily new customers chart
            $acqRows = DB::select("
                SELECT DATE(created_at) AS date, COUNT(*) AS cnt
                FROM customers
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            // Returning vs new (placed orders in period)
            $rvn = DB::selectOne("
                SELECT
                    SUM(CASE WHEN o.customer_order_rank = 1 THEN 1 ELSE 0 END) AS new_buyers,
                    SUM(CASE WHEN o.customer_order_rank > 1 THEN 1 ELSE 0 END) AS returning_buyers
                FROM (
                    SELECT o.customer_id,
                           ROW_NUMBER() OVER (PARTITION BY o.customer_id ORDER BY o.placed_at ASC) AS customer_order_rank
                    FROM orders o
                    WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                ) o
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            // Top countries by new customers
            $topCountries = DB::select("
                SELECT
                    co.name_en AS country,
                    COUNT(c.id) AS cnt
                FROM customers c
                JOIN countries co ON co.id = c.country_id
                WHERE c.created_at >= ? AND c.created_at <= ?
                GROUP BY co.name_en
                ORDER BY cnt DESC
                LIMIT 10
            ", [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()]);

            return [
                'acquisition_chart' => [
                    'labels' => array_column($acqRows, 'date'),
                    'counts' => array_map(fn($r) => (int) $r->cnt, $acqRows),
                ],
                'returning_vs_new' => [
                    'new_buyers' => (int) ($rvn->new_buyers ?? 0),
                    'returning_buyers' => (int) ($rvn->returning_buyers ?? 0),
                ],
                'top_countries' => array_map(fn($row) => [
                    'country' => $row->country,
                    'count' => (int) $row->cnt,
                ], $topCountries),
            ];
        });
    }

    // ── Search analytics ──────────────────────────────────────────────────────

    public function searchAnalytics(Request $request): array
    {
        $key = $this->cacheKey('search-analytics', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()];

            $topQueries = DB::select("
                SELECT query_normalized AS query, COUNT(*) AS searches,
                       SUM(CASE WHEN clicked_product_id IS NOT NULL THEN 1 ELSE 0 END) AS clicks,
                       ROUND(SUM(CASE WHEN clicked_product_id IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS ctr
                FROM search_logs
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY query_normalized
                ORDER BY searches DESC
                LIMIT 20
            ", $bindings);

            $zeroResults = DB::select("
                SELECT query_normalized AS query, COUNT(*) AS searches
                FROM search_logs
                WHERE created_at >= ? AND created_at <= ? AND results_count = 0
                GROUP BY query_normalized
                ORDER BY searches DESC
                LIMIT 20
            ", $bindings);

            $volumeRows = DB::select("
                SELECT DATE(created_at) AS date, COUNT(*) AS cnt
                FROM search_logs
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", $bindings);

            $totals = DB::selectOne("
                SELECT
                    COUNT(*) AS total_searches,
                    ROUND(SUM(CASE WHEN clicked_product_id IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS avg_ctr,
                    ROUND(SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) AS zero_result_rate
                FROM search_logs
                WHERE created_at >= ? AND created_at <= ?
            ", $bindings);

            return [
                'total_searches' => (int) $totals->total_searches,
                'avg_ctr' => (float) ($totals->avg_ctr ?? 0),
                'zero_result_rate' => (float) ($totals->zero_result_rate ?? 0),
                'top_queries' => array_map(fn($r) => [
                    'query' => $r->query,
                    'searches' => (int) $r->searches,
                    'clicks' => (int) $r->clicks,
                    'ctr' => (float) $r->ctr,
                ], $topQueries),
                'zero_result_queries' => array_map(fn($r) => [
                    'query' => $r->query,
                    'searches' => (int) $r->searches,
                ], $zeroResults),
                'volume_chart' => [
                    'labels' => array_column($volumeRows, 'date'),
                    'counts' => array_map(fn($r) => (int) $r->cnt, $volumeRows),
                ],
            ];
        });
    }

    // ── Product analytics ─────────────────────────────────────────────────────

    public function productAnalytics(Request $request): array
    {
        $key = $this->cacheKey('product-analytics', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()];

            $totals = DB::selectOne("
                SELECT COUNT(*) AS total_views
                FROM product_views
                WHERE created_at >= ? AND created_at <= ?
            ", $bindings);

            $topViewed = DB::select("
                SELECT p.id, p.name_en AS name, COUNT(pv.id) AS views
                FROM product_views pv
                JOIN products p ON p.id = pv.product_id
                WHERE pv.created_at >= ? AND pv.created_at <= ?
                GROUP BY p.id, p.name_en
                ORDER BY views DESC
                LIMIT 20
            ", $bindings);

            $viewSources = DB::select("
                SELECT source, COUNT(*) AS cnt
                FROM product_views
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY source
                ORDER BY cnt DESC
            ", $bindings);

            // Conversion funnel: views → add_to_carts → orders
            $funnel = DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM product_views
                     WHERE created_at >= ? AND created_at <= ?) AS views,
                    (SELECT COUNT(*) FROM cart_items ci
                     JOIN carts ca ON ca.id = ci.cart_id
                     WHERE ca.created_at >= ? AND ca.created_at <= ?) AS cart_adds,
                    (SELECT COUNT(*) FROM order_items oi
                     JOIN orders o ON o.id = oi.order_id
                     WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL) AS purchases
            ", [
                $r['from']->toDateTimeString(),
                $r['to']->toDateTimeString(),
                $r['from']->toDateTimeString(),
                $r['to']->toDateTimeString(),
                $r['from']->toDateTimeString(),
                $r['to']->toDateTimeString(),
            ]);

            return [
                'total_views' => (int) $totals->total_views,
                'top_viewed' => array_map(fn($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'views' => (int) $row->views,
                ], $topViewed),
                'view_sources' => array_map(fn($row) => [
                    'source' => $row->source,
                    'count' => (int) $row->cnt,
                ], $viewSources),
                'conversion_funnel' => [
                    'views' => (int) $funnel->views,
                    'cart_adds' => (int) $funnel->cart_adds,
                    'purchases' => (int) $funnel->purchases,
                ],
            ];
        });
    }

    // ── SLA metrics ───────────────────────────────────────────────────────────

    public function slaMetrics(Request $request): array
    {
        $key = $this->cacheKey('sla-metrics', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()];

            $overall = DB::selectOne("
                SELECT
                    COUNT(so.id) AS total_sub_orders,
                    SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END) AS breached,
                    ROUND(AVG(CASE WHEN so.shipped_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, o.placed_at, so.shipped_at)
                    END), 2) AS avg_ship_hours,
                    ROUND(AVG(CASE WHEN so.delivered_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, so.shipped_at, so.delivered_at)
                    END), 2) AS avg_delivery_hours
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
            ", $bindings);

            $byVendor = DB::select("
                SELECT
                    v.store_name,
                    COUNT(so.id) AS total,
                    SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END) AS breached,
                    ROUND(SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(so.id), 0), 2) AS breach_rate
                FROM sub_orders so
                JOIN vendors v  ON v.id  = so.vendor_id
                JOIN orders o   ON o.id  = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY v.id, v.store_name
                HAVING total > 0
                ORDER BY breach_rate DESC
                LIMIT 20
            ", $bindings);

            $trend = DB::select("
                SELECT
                    DATE(o.placed_at) AS date,
                    ROUND(SUM(CASE WHEN so.sla_breached = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(so.id), 0), 2) AS breach_rate
                FROM sub_orders so
                JOIN orders o ON o.id = so.order_id
                WHERE o.placed_at >= ? AND o.placed_at <= ? AND o.deleted_at IS NULL
                GROUP BY DATE(o.placed_at)
                ORDER BY date ASC
            ", $bindings);

            $total = (int) $overall->total_sub_orders;
            $breached = (int) $overall->breached;
            $breachRate = $total > 0 ? round($breached / $total * 100, 2) : 0;

            return [
                'breach_rate' => $breachRate,
                'sla_compliance_pct' => round(100 - $breachRate, 2),
                'avg_ship_hours' => (float) ($overall->avg_ship_hours ?? 0),
                'avg_delivery_hours' => (float) ($overall->avg_delivery_hours ?? 0),
                'breach_by_vendor' => array_map(fn($row) => [
                    'store_name' => $row->store_name,
                    'total' => (int) $row->total,
                    'breached' => (int) $row->breached,
                    'breach_rate' => (float) $row->breach_rate,
                ], $byVendor),
                'trend' => [
                    'labels' => array_column($trend, 'date'),
                    'breach_rates' => array_map(fn($r) => (float) $r->breach_rate, $trend),
                ],
            ];
        });
    }

    // ── Ad performance ────────────────────────────────────────────────────────

    public function adPerformance(Request $request): array
    {
        $key = $this->cacheKey('ad-performance', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $countryId = $request->input('country_id');
            $bindings = [$r['from']->toDateString(), $r['to']->toDateString()];
            $where = 'WHERE ads.date >= ? AND ads.date <= ?';
            if ($countryId) {
                $where .= ' AND ads.country_id = ?';
                $bindings[] = $countryId;
            }

            $totals = DB::selectOne("
                SELECT
                    COALESCE(SUM(ads.impressions), 0)                 AS impressions,
                    COALESCE(SUM(ads.clicks), 0)                      AS clicks,
                    COALESCE(SUM(ads.conversions), 0)                 AS conversions,
                    COALESCE(SUM(ads.spend_cents), 0)                 AS spend_cents,
                    COALESCE(SUM(ads.revenue_attributed_cents), 0)    AS revenue_cents,
                    ROUND(SUM(ads.clicks) * 100.0 / NULLIF(SUM(ads.impressions), 0), 4) AS avg_ctr,
                    ROUND(SUM(ads.spend_cents) * 100.0 / NULLIF(SUM(ads.revenue_attributed_cents), 0), 4) AS avg_acos
                FROM ad_daily_stats ads
                {$where}
            ", $bindings);

            $topCampaigns = DB::select("
                SELECT
                    ac.name                                          AS campaign_name,
                    SUM(ads.impressions)                            AS impressions,
                    SUM(ads.clicks)                                 AS clicks,
                    COALESCE(SUM(ads.spend_cents), 0)               AS spend_cents,
                    COALESCE(SUM(ads.revenue_attributed_cents), 0)  AS revenue_cents,
                    ROUND(SUM(ads.clicks) * 100.0 / NULLIF(SUM(ads.impressions), 0), 4) AS ctr,
                    ROUND(SUM(ads.spend_cents) * 100.0 / NULLIF(SUM(ads.revenue_attributed_cents), 0), 4) AS acos
                FROM ad_daily_stats ads
                JOIN ad_campaigns ac ON ac.id = ads.ad_campaign_id
                {$where}
                GROUP BY ac.id, ac.name
                ORDER BY spend_cents DESC
                LIMIT 20
            ", $bindings);

            $perfChart = DB::select("
                SELECT
                    ads.date,
                    SUM(ads.impressions)                            AS impressions,
                    SUM(ads.clicks)                                 AS clicks,
                    COALESCE(SUM(ads.spend_cents), 0)               AS spend_cents,
                    COALESCE(SUM(ads.revenue_attributed_cents), 0)  AS revenue_cents
                FROM ad_daily_stats ads
                {$where}
                GROUP BY ads.date
                ORDER BY ads.date ASC
            ", $bindings);

            return [
                'total_impressions' => (int) $totals->impressions,
                'total_clicks' => (int) $totals->clicks,
                'total_conversions' => (int) $totals->conversions,
                'total_spend' => round($totals->spend_cents / 100, 2),
                'total_revenue' => round($totals->revenue_cents / 100, 2),
                'avg_ctr' => (float) ($totals->avg_ctr ?? 0),
                'avg_acos' => (float) ($totals->avg_acos ?? 0),
                'top_campaigns' => array_map(fn($row) => [
                    'name' => $row->campaign_name,
                    'impressions' => (int) $row->impressions,
                    'clicks' => (int) $row->clicks,
                    'spend' => round($row->spend_cents / 100, 2),
                    'revenue' => round($row->revenue_cents / 100, 2),
                    'ctr' => (float) $row->ctr,
                    'acos' => (float) $row->acos,
                ], $topCampaigns),
                'performance_chart' => [
                    'labels' => array_column($perfChart, 'date'),
                    'impressions' => array_map(fn($r) => (int) $r->impressions, $perfChart),
                    'clicks' => array_map(fn($r) => (int) $r->clicks, $perfChart),
                    'spend' => array_map(fn($r) => round($r->spend_cents / 100, 2), $perfChart),
                    'revenue' => array_map(fn($r) => round($r->revenue_cents / 100, 2), $perfChart),
                ],
            ];
        });
    }

    // ── Flash sale analytics ──────────────────────────────────────────────────

    public function flashSaleAnalytics(Request $request): array
    {
        $key = $this->cacheKey('flash-sales', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateString(), $r['to']->toDateString()];

            $totals = DB::selectOne("
                SELECT
                    COALESCE(SUM(units_sold), 0)             AS total_units,
                    COALESCE(SUM(gross_revenue), 0)          AS total_revenue,
                    COALESCE(SUM(discount_given), 0)         AS total_discount,
                    ROUND(AVG(conversion_rate) * 100, 4)     AS avg_conversion_rate,
                    COUNT(DISTINCT flash_sale_id)             AS total_flash_sales
                FROM flash_sale_analytics
                WHERE date >= ? AND date <= ?
            ", $bindings);

            $topSales = DB::select("
                SELECT
                    fs.title                                  AS title,
                    SUM(fsa.units_sold)                      AS units_sold,
                    COALESCE(SUM(fsa.gross_revenue), 0)      AS revenue,
                    ROUND(AVG(fsa.conversion_rate) * 100, 2) AS avg_cvr
                FROM flash_sale_analytics fsa
                JOIN flash_sales fs ON fs.id = fsa.flash_sale_id
                WHERE fsa.date >= ? AND fsa.date <= ?
                GROUP BY fs.id, fs.title
                ORDER BY revenue DESC
                LIMIT 10
            ", $bindings);

            $topVendors = DB::select("
                SELECT
                    v.store_name,
                    SUM(fsa.units_sold)                      AS units_sold,
                    COALESCE(SUM(fsa.gross_revenue), 0)      AS revenue
                FROM flash_sale_analytics fsa
                JOIN vendors v ON v.id = fsa.vendor_id
                WHERE fsa.date >= ? AND fsa.date <= ?
                GROUP BY v.id, v.store_name
                ORDER BY revenue DESC
                LIMIT 10
            ", $bindings);

            return [
                'total_units_sold' => (int) $totals->total_units,
                'total_revenue' => round($totals->total_revenue / 100, 2),
                'total_discount' => round($totals->total_discount / 100, 2),
                'avg_conversion_rate' => (float) ($totals->avg_conversion_rate ?? 0),
                'total_flash_sales' => (int) $totals->total_flash_sales,
                'top_performing_sales' => array_map(fn($row) => [
                    'title' => $row->title,
                    'units_sold' => (int) $row->units_sold,
                    'revenue' => round($row->revenue / 100, 2),
                    'avg_cvr' => (float) $row->avg_cvr,
                ], $topSales),
                'top_performing_vendors' => array_map(fn($row) => [
                    'store_name' => $row->store_name,
                    'units_sold' => (int) $row->units_sold,
                    'revenue' => round($row->revenue / 100, 2),
                ], $topVendors),
            ];
        });
    }

    // ── Return analytics ──────────────────────────────────────────────────────

    public function returnAnalytics(Request $request): array
    {
        $key = $this->cacheKey('return-analytics', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()];

            $byReason = DB::select("
                SELECT reason, COUNT(*) AS cnt
                FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY reason
                ORDER BY cnt DESC
            ", $bindings);

            $byVendor = DB::select("
                SELECT v.store_name, COUNT(rr.id) AS returns_count
                FROM return_requests rr
                JOIN vendors v ON v.id = rr.vendor_id
                WHERE rr.created_at >= ? AND rr.created_at <= ?
                GROUP BY v.id, v.store_name
                ORDER BY returns_count DESC
                LIMIT 15
            ", $bindings);

            $byLiability = DB::select("
                SELECT
                    COALESCE(liability, 'unresolved') AS liability,
                    COUNT(*) AS cnt
                FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY liability
                ORDER BY cnt DESC
            ", $bindings);

            $monthlyTrend = DB::select("
                SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
                FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY month
                ORDER BY month ASC
            ", $bindings);

            $totals = DB::selectOne("
                SELECT
                    COUNT(*) AS total_returns,
                    (SELECT COUNT(DISTINCT id) FROM orders
                     WHERE placed_at >= ? AND placed_at <= ? AND deleted_at IS NULL) AS total_orders
                FROM return_requests
                WHERE created_at >= ? AND created_at <= ?
            ", [
                $r['from']->toDateTimeString(),
                $r['to']->toDateTimeString(),
                $r['from']->toDateTimeString(),
                $r['to']->toDateTimeString(),
            ]);

            $retRate = $totals->total_orders > 0
                ? round($totals->total_returns / $totals->total_orders * 100, 2)
                : 0;

            return [
                'total_returns' => (int) $totals->total_returns,
                'return_rate' => $retRate,
                'by_reason' => array_map(fn($r) => [
                    'reason' => ucfirst(str_replace('_', ' ', $r->reason)),
                    'count' => (int) $r->cnt,
                ], $byReason),
                'by_vendor' => array_map(fn($r) => [
                    'store_name' => $r->store_name,
                    'count' => (int) $r->returns_count,
                ], $byVendor),
                'by_liability' => array_map(fn($r) => [
                    'liability' => ucfirst($r->liability),
                    'count' => (int) $r->cnt,
                ], $byLiability),
                'monthly_trend' => [
                    'labels' => array_column($monthlyTrend, 'month'),
                    'counts' => array_map(fn($r) => (int) $r->cnt, $monthlyTrend),
                ],
            ];
        });
    }

    // ── Support metrics ───────────────────────────────────────────────────────

    public function supportMetrics(Request $request): array
    {
        $key = $this->cacheKey('support-metrics', $request);
        $ttl = $this->cacheTtl($request->input('period', 'month'));

        return Cache::remember($key, $ttl, function () use ($request) {
            $r = $this->dateRange($request);
            $bindings = [$r['from']->toDateTimeString(), $r['to']->toDateTimeString()];

            $totals = DB::selectOne("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_tickets,
                    ROUND(AVG(CASE WHEN first_response_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, created_at, first_response_at)
                    END) / 60.0, 2) AS avg_first_response_hours,
                    ROUND(AVG(CASE WHEN resolved_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at)
                    END) / 60.0, 2) AS avg_resolution_hours,
                    ROUND(AVG(satisfaction_rating), 2) AS avg_satisfaction
                FROM support_tickets
                WHERE created_at >= ? AND created_at <= ?
            ", $bindings);

            $byCategory = DB::select("
                SELECT category, COUNT(*) AS cnt
                FROM support_tickets
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY category
                ORDER BY cnt DESC
            ", $bindings);

            $byPriority = DB::select("
                SELECT priority, COUNT(*) AS cnt
                FROM support_tickets
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY priority
                ORDER BY FIELD(priority, 'urgent', 'high', 'normal', 'low')
            ", $bindings);

            $trend = DB::select("
                SELECT DATE(created_at) AS date, COUNT(*) AS created,
                       SUM(CASE WHEN resolved_at IS NOT NULL AND DATE(resolved_at) = DATE(created_at) THEN 1 ELSE 0 END) AS resolved
                FROM support_tickets
                WHERE created_at >= ? AND created_at <= ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", $bindings);

            return [
                'total' => (int) $totals->total,
                'open_tickets' => (int) $totals->open_tickets,
                'avg_first_response_hours' => (float) ($totals->avg_first_response_hours ?? 0),
                'avg_resolution_hours' => (float) ($totals->avg_resolution_hours ?? 0),
                'avg_satisfaction' => (float) ($totals->avg_satisfaction ?? 0),
                'by_category' => array_map(fn($r) => [
                    'category' => ucfirst(str_replace('_', ' ', $r->category)),
                    'count' => (int) $r->cnt,
                ], $byCategory),
                'by_priority' => array_map(fn($r) => [
                    'priority' => ucfirst($r->priority),
                    'count' => (int) $r->cnt,
                ], $byPriority),
                'resolution_trend' => [
                    'labels' => array_column($trend, 'date'),
                    'created' => array_map(fn($r) => (int) $r->created, $trend),
                    'resolved' => array_map(fn($r) => (int) $r->resolved, $trend),
                ],
            ];
        });
    }
}
