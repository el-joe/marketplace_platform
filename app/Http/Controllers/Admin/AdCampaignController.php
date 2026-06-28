<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Notifications\Vendor\AdCampaignApproved;
use App\Notifications\Vendor\AdCampaignRejected;
use Illuminate\Support\Facades\Notification;
use App\Models\AdDailyStat;
use App\Models\AdFraudPattern;
use App\Models\Country;
use App\Models\Vendor;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdCampaignController extends Controller
{
    use HasDataTable;

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $today = today();

        $stats = [
            'pending' => AdCampaign::where('status', 'pending_review')->count(),
            'active' => AdCampaign::where('status', 'active')->count(),
            'paused' => AdCampaign::where('status', 'paused')->count(),
            'spend_today' => (int) AdDailyStat::whereDate('date', $today)->sum('spend_cents'),
        ];

        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'flag_emoji']);

        return view('admin.ad-campaigns.index', compact('stats', 'countries'));
    }

    // ─── DataTable ────────────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $query = AdCampaign::query()
            ->select('ad_campaigns.*')
            ->with(['vendor', 'country'])
            ->join('vendors', 'vendors.id', '=', 'ad_campaigns.vendor_id');

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('ad_campaigns.status', $v),
            'type' => fn($q, $v) => $q->where('ad_campaigns.type', $v),
            'vendor_id' => fn($q, $v) => $q->where('ad_campaigns.vendor_id', $v),
            'country_id' => fn($q, $v) => $q->where('ad_campaigns.country_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('ad_campaigns.starts_at', '>=', $v),
            'date_to' => fn($q, $v) => $q->whereDate('ad_campaigns.ends_at', '<=', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['vendors.store_name'], 'orderable_column' => 'vendors.store_name'],
            ['searchable_columns' => ['ad_campaigns.name'], 'orderable_column' => 'ad_campaigns.name'],
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.type'],
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.status'],
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.budget_total'],
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.budget_spent_total'],
            ['searchable_columns' => [], 'orderable_column' => null], // utilization
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.quality_score'],
            ['searchable_columns' => [], 'orderable_column' => 'ad_campaigns.starts_at'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        $statusColors = [
            'pending_review' => 'warning',
            'active' => 'success',
            'paused' => 'gray',
            'rejected' => 'danger',
            'ended' => 'gray',
            'draft' => 'gray',
        ];

        $typeColors = ['cpc' => 'primary', 'cpm' => 'info'];

        $canEdit = $admin->hasPermissionTo('ad_campaigns.edit');

        return $this->dataTableResponse($request, $query, $columns, function (AdCampaign $row) use ($statusColors, $typeColors, $canEdit) {
            $budgetTotal = $row->budget_total / 100;
            $budgetSpent = $row->budget_spent_total / 100;
            $utilization = $row->budget_total > 0
                ? min(100, round($row->budget_spent_total / $row->budget_total * 100, 1))
                : 0;

            $barColor = $utilization >= 90 ? 'bg-red-500' : ($utilization >= 70 ? 'bg-yellow-500' : 'bg-green-500');
            $progressBar = <<<HTML
                <div class="w-24">
                    <div class="flex justify-between text-xs text-gray-500 mb-0.5">
                        <span>{$utilization}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="{$barColor} h-1.5 rounded-full" style="width:{$utilization}%"></div>
                    </div>
                </div>
                HTML;

            $qScore = (float) $row->quality_score;
            $qColor = $qScore >= 7 ? 'success' : ($qScore >= 4 ? 'warning' : 'danger');
            $qualityBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-{$qColor}-100 text-{$qColor}-700\">{$qScore}</span>";

            $statusColor = $statusColors[$row->status] ?? 'gray';
            $statusLabel = ucwords(str_replace('_', ' ', $row->status));
            $statusBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-700\">{$statusLabel}</span>";

            $typeColor = $typeColors[$row->type] ?? 'gray';
            $typeLabel = strtoupper($row->type);
            $typeBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-{$typeColor}-100 text-{$typeColor}-700\">{$typeLabel}</span>";

            $dateRange = Carbon::parse($row->starts_at)->format('d M') . ' – ' . ($row->ends_at ? Carbon::parse($row->ends_at)->format('d M Y') : '∞');

            return [
                'vendor' => e($row->vendor?->store_name ?? '—'),
                'name' => e($row->name),
                'type' => $typeBadge,
                'status' => $statusBadge,
                'budget' => '$' . number_format($budgetTotal, 2),
                'spend' => '$' . number_format($budgetSpent, 2),
                'utilization' => $progressBar,
                'quality' => $qualityBadge,
                'date_range' => $dateRange,
                'actions' => $this->buildCampaignRowActions($row, $canEdit),
                'DT_RowData' => ['id' => $row->id, 'status' => $row->status],
            ];
        });
    }

    private function buildCampaignRowActions(AdCampaign $campaign, bool $canEdit): string
    {
        $showUrl = route('admin.ad-campaigns.show', $campaign->id);
        $approveUrl = route('admin.ad-campaigns.approve', $campaign->id);
        $rejectUrl = route('admin.ad-campaigns.reject', $campaign->id);
        $pauseUrl = route('admin.ad-campaigns.pause', $campaign->id);
        $resumeUrl = route('admin.ad-campaigns.resume', $campaign->id);

        $html = '<div class="flex items-center gap-1">';
        $html .= "<a href=\"{$showUrl}\" class=\"btn btn-xs btn-secondary\">View</a>";

        if ($canEdit) {
            if ($campaign->status === 'pending_review') {
                $html .= "<button type=\"button\" class=\"btn btn-xs btn-success js-approve-btn\" data-url=\"{$approveUrl}\" data-name=\"" . e($campaign->name) . "\">Approve</button>";
                $html .= "<button type=\"button\" class=\"btn btn-xs btn-danger js-reject-btn\" data-url=\"{$rejectUrl}\" data-name=\"" . e($campaign->name) . "\">Reject</button>";
            } elseif ($campaign->status === 'active') {
                $html .= "<button type=\"button\" class=\"btn btn-xs btn-warning js-pause-btn\" data-url=\"{$pauseUrl}\" data-name=\"" . e($campaign->name) . "\">Pause</button>";
            } elseif ($campaign->status === 'paused') {
                $html .= "<button type=\"button\" class=\"btn btn-xs btn-success js-resume-btn\" data-url=\"{$resumeUrl}\" data-name=\"" . e($campaign->name) . "\">Resume</button>";
            }
        }
        $html .= '</div>';
        return $html;
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(AdCampaign $campaign): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $campaign->load(['vendor', 'country', 'approvedByAdmin', 'products.vendorListing', 'keywords', 'categoryTargets.category', 'fraudPatterns']);

        // Last 7-day performance
        $perf7 = $campaign->dailyStats()
            ->orderBy('date', 'desc')
            ->take(7)
            ->get();

        $perfSummary = [
            'impressions' => $perf7->sum('impressions'),
            'clicks' => $perf7->sum('clicks'),
            'conversions' => $perf7->sum('conversions'),
            'spend_cents' => $perf7->sum('spend_cents'),
            'revenue_attributed_cents' => $perf7->sum('revenue_attributed_cents'),
            'ctr' => $perf7->avg('ctr'),
            'acos' => $perf7->avg('acos'),
        ];

        // Quality score breakdown (most recent)
        $qualityScore = $campaign->qualityScore;

        // Performance chart data (last 30 days)
        $chartStats = $campaign->dailyStats()
            ->orderBy('date')
            ->take(30)
            ->get(['date', 'impressions', 'clicks', 'spend_cents']);

        $chartLabels = $chartStats->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d M'))->values();
        $chartImpressions = $chartStats->pluck('impressions')->values();
        $chartClicks = $chartStats->pluck('clicks')->values();

        // All daily stats for table
        $dailyStats = $campaign->dailyStats()->orderBy('date', 'desc')->take(30)->get();

        return view('admin.ad-campaigns.show', compact(
            'campaign',
            'perfSummary',
            'qualityScore',
            'chartLabels',
            'chartImpressions',
            'chartClicks',
            'dailyStats'
        ));
    }

    // ─── Approve ──────────────────────────────────────────────────────────────

    public function approve(AdCampaign $campaign): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        if (!in_array($campaign->status, ['pending_review', 'paused'])) {
            return response()->json(['message' => 'Campaign is not pending review.'], 422);
        }

        $campaign->update([
            'status' => 'active',
            'approved_by_admin_id' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        Notification::send($campaign->vendor->vendorAdmins, new AdCampaignApproved($campaign));

        return response()->json(['message' => 'Campaign approved and set to active.']);
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    public function reject(Request $request, AdCampaign $campaign): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $campaign->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        Notification::send($campaign->vendor->vendorAdmins, new AdCampaignRejected($campaign, $request->input('rejection_reason')));

        return response()->json(['message' => 'Campaign rejected.']);
    }

    // ─── Pause ────────────────────────────────────────────────────────────────

    public function pauseCampaign(AdCampaign $campaign): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        if ($campaign->status !== 'active') {
            return response()->json(['message' => 'Campaign is not active.'], 422);
        }

        $campaign->update(['status' => 'paused']);

        return response()->json(['message' => 'Campaign paused.']);
    }

    // ─── Resume ───────────────────────────────────────────────────────────────

    public function resumeCampaign(AdCampaign $campaign): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        if ($campaign->status !== 'paused') {
            return response()->json(['message' => 'Campaign is not paused.'], 422);
        }

        $campaign->update(['status' => 'active']);

        return response()->json(['message' => 'Campaign resumed.']);
    }

    // ─── Fraud Alerts ─────────────────────────────────────────────────────────

    public function fraudAlerts(): \Illuminate\View\View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $unblocked = AdFraudPattern::where('is_blocked', false)->count();
        $blocked = AdFraudPattern::where('is_blocked', true)->count();

        $stats = [
            'unblocked' => $unblocked,
            'blocked' => $blocked,
            'total' => $unblocked + $blocked,
        ];

        return view('admin.ad-campaigns.fraud-alerts', compact('stats'));
    }

    public function fraudDatatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.view'), 403);

        $query = AdFraudPattern::query()
            ->with(['campaign.vendor']);

        $query = $this->applyFilters($query, $request, [
            'is_blocked' => fn($q, $v) => $q->where('is_blocked', (int) $v),
            'campaign_id' => fn($q, $v) => $q->where('ad_campaign_id', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['ad_fraud_patterns.ip_address'], 'orderable_column' => 'ad_fraud_patterns.ip_address'],
            ['searchable_columns' => [], 'orderable_column' => null], // vendor/campaign
            ['searchable_columns' => [], 'orderable_column' => 'clicks_last_hour'],
            ['searchable_columns' => [], 'orderable_column' => 'clicks_last_24h'],
            ['searchable_columns' => [], 'orderable_column' => 'is_blocked'],
            ['searchable_columns' => [], 'orderable_column' => 'blocked_at'],
            ['searchable_columns' => [], 'orderable_column' => null], // block_reason
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        $canEdit = $admin->hasPermissionTo('ad_campaigns.edit');

        return $this->dataTableResponse($request, $query, $columns, function (AdFraudPattern $row) use ($canEdit) {
            $blockedBadge = $row->is_blocked
                ? '<span class="badge badge-danger text-xs">Blocked</span>'
                : '<span class="badge badge-warning text-xs">Suspicious</span>';

            $blockUrl = route('admin.ad-campaigns.fraud.block', $row->id);
            $actions = '';
            if ($canEdit && !$row->is_blocked) {
                $actions = "<button type=\"button\" class=\"btn btn-xs btn-danger js-block-ip-btn\" data-url=\"{$blockUrl}\" data-ip=\"" . e($row->ip_address) . "\">Block IP</button>";
            }

            return [
                'ip_address' => e($row->ip_address),
                'campaign' => e($row->campaign?->name ?? '—') . '<br><span class="text-xs text-gray-400">' . e($row->campaign?->vendor?->store_name ?? '') . '</span>',
                'clicks_last_hour' => $row->clicks_last_hour,
                'clicks_last_24h' => $row->clicks_last_24h,
                'is_blocked' => $blockedBadge,
                'blocked_at' => $row->blocked_at ? Carbon::parse($row->blocked_at)->format('d M Y H:i') : '—',
                'block_reason' => $row->block_reason ? e($row->block_reason) : '—',
                'actions' => $actions,
                'DT_RowData' => ['id' => $row->id],
            ];
        });
    }

    public function blockFraudPattern(Request $request, AdFraudPattern $pattern): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('ad_campaigns.edit'), 403);

        $request->validate([
            'block_reason' => ['nullable', 'string', 'max:100'],
        ]);

        $pattern->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'block_reason' => $request->input('block_reason', 'Blocked by admin'),
        ]);

        return response()->json(['message' => 'IP blocked.']);
    }
}
