<?php

namespace App\Services\Marketer;

use App\Models\Marketer;
use App\Models\MarketerClick;
use App\Models\MarketerCommissionTier;
use App\Models\MarketerConversion;
use App\Models\MarketerPayout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSnapshot(Marketer $marketer): array
    {
        return Cache::remember("marketer_dashboard_{$marketer->id}", 60, function () use ($marketer) {
            $today = Carbon::today();
            $thirtyDaysAgo = Carbon::today()->subDays(29);

            $todayClicks = MarketerClick::where('marketer_id', $marketer->id)
                ->whereDate('clicked_at', $today)
                ->count();

            $todayConversions = MarketerConversion::where('marketer_id', $marketer->id)
                ->whereDate('created_at', $today)
                ->count();

            $conversionRate = $todayClicks > 0
                ? round(($todayConversions / $todayClicks) * 100, 2)
                : 0.0;

            $pendingEarningsCents = MarketerConversion::where('marketer_id', $marketer->id)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('commission_amount_cents');

            $paidEarningsCents = MarketerPayout::where('marketer_id', $marketer->id)
                ->where('status', 'paid')
                ->sum('net_amount_cents');

            // Tier progress
            $salesCount = MarketerConversion::where('marketer_id', $marketer->id)
                ->where('status', '!=', 'reversed')
                ->count();

            $currentTier = MarketerCommissionTier::where('marketer_id', $marketer->id)
                ->whereNull('campaign_id')
                ->active()
                ->forSalesCount($salesCount)
                ->first();

            $nextTier = null;
            if ($currentTier) {
                $nextTier = MarketerCommissionTier::where('marketer_id', $marketer->id)
                    ->whereNull('campaign_id')
                    ->active()
                    ->where('tier_order', '>', $currentTier->tier_order)
                    ->orderBy('tier_order')
                    ->first();
            } else {
                // Not yet on any tier — get first tier
                $nextTier = MarketerCommissionTier::where('marketer_id', $marketer->id)
                    ->whereNull('campaign_id')
                    ->active()
                    ->orderBy('tier_order')
                    ->first();
            }

            $tierData = [
                'name'                      => $currentTier ? 'Tier ' . $currentTier->tier_order : null,
                'commission_rate'           => $currentTier ? (float) $currentTier->commission_rate : (float) $marketer->commission_rate,
                'sales_count'               => $salesCount,
                'next_tier_threshold'       => $nextTier?->min_sales_count,
                'sales_remaining_to_next_tier' => $nextTier
                    ? max(0, $nextTier->min_sales_count - $salesCount)
                    : null,
            ];

            // Revenue chart: last 30 days
            $clicksByDate = MarketerClick::where('marketer_id', $marketer->id)
                ->where('clicked_at', '>=', $thirtyDaysAgo)
                ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as clicks'))
                ->groupBy(DB::raw('DATE(clicked_at)'))
                ->pluck('clicks', 'date');

            $conversionsByDate = MarketerConversion::where('marketer_id', $marketer->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as conversions'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->pluck('conversions', 'date');

            $revenueChart = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->toDateString();
                $revenueChart[] = [
                    'date'        => $date,
                    'clicks'      => (int) ($clicksByDate[$date] ?? 0),
                    'conversions' => (int) ($conversionsByDate[$date] ?? 0),
                ];
            }

            // Recent conversions (last 10, order_number masked to last 4)
            $recentConversions = MarketerConversion::where('marketer_id', $marketer->id)
                ->with('order:id,order_number')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($c) => [
                    'order_number_last4'    => $c->order?->order_number
                        ? '****' . substr($c->order->order_number, -4)
                        : null,
                    'date'                  => $c->created_at->toDateString(),
                    'commission_amount_cents'=> (int) $c->commission_amount_cents,
                    'status'                => $c->status,
                ]);

            return [
                'today_clicks'        => $todayClicks,
                'today_conversions'   => $todayConversions,
                'conversion_rate'     => $conversionRate,
                'pending_earnings_cents' => (int) $pendingEarningsCents,
                'paid_earnings_cents'    => (int) $paidEarningsCents,
                'current_tier'        => $tierData,
                'revenue_chart'       => $revenueChart,
                'recent_conversions'  => $recentConversions,
            ];
        });
    }
}
