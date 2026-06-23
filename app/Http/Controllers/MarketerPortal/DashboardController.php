<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Models\MarketerConversion;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $today = today();

        $stats = [
            'clicks_today' => $marketer->clicks()->whereDate('clicked_at', $today)->count(),
            'conversions_today' => $marketer->conversions()->whereDate('created_at', $today)->count(),
            'conversions_month' => $marketer->conversions()->whereMonth('created_at', $today->month)->count(),
            'pending_earnings' => $marketer->conversions()->whereIn('status', ['pending', 'approved'])->sum('commission_amount_cents'),
            'paid_earnings' => $marketer->conversions()->where('status', 'paid')->sum('commission_amount_cents'),
            'conversion_rate' => $marketer->total_clicks > 0
                ? round(($marketer->total_conversions / $marketer->total_clicks) * 100, 2)
                : 0,
        ];

        // Top 5 performing campaigns
        $topCampaigns = $marketer->campaigns()
            ->where('status', 'active')
            ->orderByDesc('total_conversions')
            ->limit(5)
            ->get(['id', 'name', 'total_clicks', 'total_conversions', 'total_revenue_cents', 'status']);

        // Revenue trend: last 30 days (conversions)
        $revenueTrend = $marketer->conversions()
            ->selectRaw('DATE(created_at) as date, SUM(commission_amount_cents) as total, COUNT(*) as cnt')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenueData = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels[] = $d;
            $revenueData[] = isset($revenueTrend[$d]) ? $revenueTrend[$d]->total / 100 : 0;
        }

        // Tier progress
        $salesCount = $marketer->total_conversions ?? 0;
        $allTiers = $marketer->commissionTiers()->whereNull('campaign_id')->orderBy('tier_order')->get();
        $currentTier = $allTiers->first(
            fn($t) =>
            $salesCount >= $t->min_sales_count &&
            ($t->max_sales_count === null || $salesCount <= $t->max_sales_count)
        );
        $nextTier = $allTiers->first(fn($t) => $t->min_sales_count > $salesCount);

        return view('marketer.dashboard', [
            'marketer' => $marketer,
            'stats' => $stats,
            'topCampaigns' => $topCampaigns,
            'chartLabels' => $labels,
            'chartData' => $revenueData,
            'currentTier' => $currentTier ?? null,
            'nextTier' => $nextTier ?? null,
            'salesCount' => $salesCount,
        ]);
    }
}
