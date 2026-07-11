<?php

namespace App\Services\Marketer;

use App\Enums\MarketerTrackingStatus;
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
            $today          = Carbon::today();
            $startOfMonth   = Carbon::today()->startOfMonth();
            $sevenDaysAgo   = Carbon::today()->subDays(6);

            $clicksToday = MarketerClick::where('marketer_id', $marketer->id)
                ->whereDate('clicked_at', $today)
                ->count();

            $clicksThisMonth = MarketerClick::where('marketer_id', $marketer->id)
                ->where('clicked_at', '>=', $startOfMonth)
                ->count();

            $conversionsThisMonth = MarketerConversion::where('marketer_id', $marketer->id)
                ->where('created_at', '>=', $startOfMonth)
                ->count();

            $conversionRatePct = $clicksThisMonth > 0
                ? round(($conversionsThisMonth / $clicksThisMonth) * 100, 2)
                : 0.0;

            $pendingEarningsCents = (int) MarketerConversion::where('marketer_id', $marketer->id)
                ->where('status', MarketerTrackingStatus::Pending)
                ->sum('commission_amount_cents');

            $approvedEarningsCents = (int) MarketerConversion::where('marketer_id', $marketer->id)
                ->where('status', MarketerTrackingStatus::Approved)
                ->sum('commission_amount_cents');

            // Tier progress
            $salesCount = MarketerConversion::where('marketer_id', $marketer->id)
                ->where('status', '!=', MarketerTrackingStatus::Reversed)
                ->count();

            $currentTier = MarketerCommissionTier::where('marketer_id', $marketer->id)
                ->whereNull('campaign_id')
                ->active()
                ->forSalesCount($salesCount)
                ->first();

            $nextTier = $currentTier
                ? MarketerCommissionTier::where('marketer_id', $marketer->id)
                    ->whereNull('campaign_id')
                    ->active()
                    ->where('tier_order', '>', $currentTier->tier_order)
                    ->orderBy('tier_order')
                    ->first()
                : MarketerCommissionTier::where('marketer_id', $marketer->id)
                    ->whereNull('campaign_id')
                    ->active()
                    ->orderBy('tier_order')
                    ->first();

            $currentBase = $currentTier?->min_sales_count ?? 0;
            $progressPct = $nextTier
                ? (int) round(min(100, max(0, ($salesCount - $currentBase) / max(1, $nextTier->min_sales_count - $currentBase) * 100)))
                : 100;

            $tierData = [
                'current_tier_name'    => $currentTier ? 'Tier ' . $currentTier->tier_order : null,
                'next_tier_name'       => $nextTier ? 'Tier ' . $nextTier->tier_order : null,
                'current_sales_count'  => $salesCount,
                'next_tier_threshold'  => $nextTier?->min_sales_count,
                'progress_pct'         => $progressPct,
            ];

            // Revenue chart: last 7 days
            $conversionsByDate = MarketerConversion::where('marketer_id', $marketer->id)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as conversions'),
                    DB::raw('SUM(commission_amount_cents) as commission_cents'),
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->keyBy('date');

            $revenueLast7Days = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i)->toDateString();
                $row  = $conversionsByDate->get($date);
                $revenueLast7Days[] = [
                    'date'             => $date,
                    'commission_cents' => (int) ($row->commission_cents ?? 0),
                    'conversions'      => (int) ($row->conversions ?? 0),
                ];
            }

            // Top campaigns by revenue
            $topCampaigns = $marketer->campaigns()
                ->orderByDesc('total_revenue_cents')
                ->limit(5)
                ->get(['id', 'name', 'total_clicks', 'total_conversions', 'total_revenue_cents', 'status'])
                ->map(fn ($c) => [
                    'id'            => $c->id,
                    'name'          => $c->name,
                    'clicks'        => (int) $c->total_clicks,
                    'conversions'   => (int) $c->total_conversions,
                    'revenue_cents' => (int) $c->total_revenue_cents,
                    'status'        => $c->status,
                ]);

            return [
                'clicks_today'            => $clicksToday,
                'clicks_this_month'       => $clicksThisMonth,
                'conversions_this_month'  => $conversionsThisMonth,
                'conversion_rate_pct'     => $conversionRatePct,
                'pending_earnings_cents'  => $pendingEarningsCents,
                'approved_earnings_cents' => $approvedEarningsCents,
                'total_earnings_cents'    => (int) $marketer->total_earnings_cents,
                'currency'                => $marketer->country?->currency_code ?? 'SAR',
                'commission_rate'         => (float) $marketer->commission_rate,
                'referral_code'           => $marketer->referral_code,
                'tier'                    => $tierData,
                'revenue_last_7_days'     => $revenueLast7Days,
                'top_campaigns'           => $topCampaigns,
            ];
        });
    }
}
