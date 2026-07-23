<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Enums\MarketerCampaignStatus;
use App\Enums\MarketerTrackingStatus;
use App\Traits\HasExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    use HasExport;

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        [$from, $to, $range] = $this->resolveDateRange($request);

        if ($request->filled('export')) {
            return $this->exportCampaignBreakdown($request, $marketer, $from, $to);
        }

        $hasClicksTable = Schema::hasTable('marketer_clicks');

        // ── Summary cards ────────────────────────────────────────────────────
        $totalClicks = $hasClicksTable
            ? $marketer->clicks()->whereBetween('clicked_at', [$from, $to])->count()
            : null;

        $conversionsQuery = $marketer->conversions()->whereBetween('created_at', [$from, $to]);
        $totalConversions = (clone $conversionsQuery)->count();

        $conversionRate = ($hasClicksTable && $totalClicks > 0)
            ? round(($totalConversions / $totalClicks) * 100, 2)
            : null;

        // Commission earned, grouped by currency — never sum across currencies.
        $earnedByCurrency = (clone $conversionsQuery)
            ->selectRaw('currency, SUM(commission_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $activeCampaignsCount = $marketer->campaigns()
            ->where('status', MarketerCampaignStatus::Active)
            ->count();

        $bestCampaign = $marketer->campaigns()
            ->withCount(['conversions' => function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            }])
            ->orderByDesc('conversions_count')
            ->first();

        // ── Line chart: daily conversions ───────────────────────────────────
        $dailyConversions = (clone $conversionsQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date');

        $lineLabels = [];
        $lineData = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $lineLabels[] = $key;
            $lineData[] = (int) ($dailyConversions[$key] ?? 0);
            $cursor->addDay();
        }

        // ── Bar chart: earnings per campaign (top 5) ─────────────────────────
        // Earnings are summed within a single campaign's dominant currency to
        // avoid mixing currencies on one bar; campaigns report in one currency here.
        $earningsPerCampaign = $marketer->campaigns()
            ->select('marketer_campaigns.id', 'marketer_campaigns.name')
            ->selectSub(
                fn ($q) => $q->selectRaw('COALESCE(SUM(commission_amount), 0)')
                    ->from('marketer_conversions')
                    ->whereColumn('marketer_conversions.campaign_id', 'marketer_campaigns.id')
                    ->whereBetween('marketer_conversions.created_at', [$from, $to]),
                'earned'
            )
            ->orderByDesc('earned')
            ->limit(5)
            ->get();

        // ── Donut chart: conversions by campaign type ────────────────────────
        $conversionsByType = $marketer->conversions()
            ->whereBetween('marketer_conversions.created_at', [$from, $to])
            ->join('marketer_campaigns', 'marketer_campaigns.id', '=', 'marketer_conversions.campaign_id')
            ->selectRaw('marketer_campaigns.campaign_type as type, COUNT(*) as cnt')
            ->groupBy('marketer_campaigns.campaign_type')
            ->pluck('cnt', 'type');

        // ── Per-campaign breakdown table ──────────────────────────────────────
        $campaignBreakdown = $this->buildCampaignBreakdown($marketer, $from, $to, $hasClicksTable);

        return view('marketer.analytics.index', [
            'marketer' => $marketer,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'hasClicksTable' => $hasClicksTable,
            'totalClicks' => $totalClicks,
            'totalConversions' => $totalConversions,
            'conversionRate' => $conversionRate,
            'earnedByCurrency' => $earnedByCurrency,
            'activeCampaignsCount' => $activeCampaignsCount,
            'bestCampaign' => $bestCampaign,
            'lineLabels' => $lineLabels,
            'lineData' => $lineData,
            'earningsPerCampaign' => $earningsPerCampaign,
            'conversionsByType' => $conversionsByType,
            'campaignBreakdown' => $campaignBreakdown,
        ]);
    }

    private function buildCampaignBreakdown($marketer, Carbon $from, Carbon $to, bool $hasClicksTable)
    {
        return $marketer->campaigns()
            ->select('marketer_campaigns.*')
            ->selectSub(
                fn ($q) => $q->selectRaw('COUNT(*)')
                    ->from('marketer_conversions')
                    ->whereColumn('marketer_conversions.campaign_id', 'marketer_campaigns.id')
                    ->whereBetween('marketer_conversions.created_at', [$from, $to]),
                'period_conversions'
            )
            ->selectSub(
                fn ($q) => $q->selectRaw('COALESCE(SUM(commission_amount), 0)')
                    ->from('marketer_conversions')
                    ->whereColumn('marketer_conversions.campaign_id', 'marketer_campaigns.id')
                    ->whereBetween('marketer_conversions.created_at', [$from, $to]),
                'period_earned'
            )
            ->selectSub(
                fn ($q) => $q->select('currency')
                    ->from('marketer_conversions')
                    ->whereColumn('marketer_conversions.campaign_id', 'marketer_campaigns.id')
                    ->whereBetween('marketer_conversions.created_at', [$from, $to])
                    ->orderByDesc('created_at')
                    ->limit(1),
                'period_currency'
            )
            ->when($hasClicksTable, function ($q) use ($from, $to) {
                $q->selectSub(
                    fn ($sub) => $sub->selectRaw('COUNT(*)')
                        ->from('marketer_clicks')
                        ->whereColumn('marketer_clicks.campaign_id', 'marketer_campaigns.id')
                        ->whereBetween('marketer_clicks.clicked_at', [$from, $to]),
                    'period_clicks'
                );
            })
            ->orderByDesc('period_earned')
            ->get()
            ->map(function ($campaign) use ($hasClicksTable) {
                $clicks = $hasClicksTable ? (int) $campaign->period_clicks : null;
                $conversions = (int) $campaign->period_conversions;
                $campaign->conv_rate = ($clicks !== null && $clicks > 0)
                    ? round(($conversions / $clicks) * 100, 2)
                    : null;
                return $campaign;
            });
    }

    private function exportCampaignBreakdown(Request $request, $marketer, Carbon $from, Carbon $to): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $hasClicksTable = Schema::hasTable('marketer_clicks');
        $campaignBreakdown = $this->buildCampaignBreakdown($marketer, $from, $to, $hasClicksTable);

        $headers = ['Campaign', 'Clicks', 'Conversions', 'Conv. Rate', 'Earned', 'Currency'];

        $rows = $campaignBreakdown->map(fn($c) => [
            $c->name,
            $c->period_clicks ?? '—',
            $c->period_conversions,
            $c->conv_rate !== null ? $c->conv_rate . '%' : '—',
            number_format($c->period_earned / 100, 2),
            $c->period_currency ?? '—',
        ]);

        return match ($request->input('export')) {
            'excel' => $this->exportExcel('marketer-analytics', $headers, $rows),
            'csv' => $this->exportCsv('marketer-analytics', $headers, $rows),
            'word' => $this->exportWord('marketer-analytics', 'Campaign Analytics', $rows),
            default => abort(400, 'Invalid export format.'),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveDateRange(Request $request): array
    {
        $range = $request->string('range')->value() ?: '30d';

        if ($range === 'custom') {
            $from = $request->filled('from') ? Carbon::parse($request->string('from')->value())->startOfDay() : now()->subDays(29)->startOfDay();
            $to = $request->filled('to') ? Carbon::parse($request->string('to')->value())->endOfDay() : now()->endOfDay();
            return [$from, $to, 'custom'];
        }

        $days = match ($range) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        return [now()->subDays($days - 1)->startOfDay(), now()->endOfDay(), $range];
    }
}
