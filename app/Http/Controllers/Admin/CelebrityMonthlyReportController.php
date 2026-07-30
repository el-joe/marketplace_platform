<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Traits\HasDataTable;
use App\Traits\HasExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CelebrityMonthlyReportController extends Controller
{
    use HasDataTable;
    use HasExport;

    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $stats = MarketerMonthlyQuotaProgress::query()
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        $activeCelebrities = $stats->pluck('marketer_id')->unique()->count();
        $meetingMinimum = $stats->filter(fn ($s) => !$s->is_below_quota)->count();
        $belowMinimum = $stats->filter(fn ($s) => $s->is_below_quota)->count();
        $penaltiesApplied = $stats->where('penalty_applied', true)->count();

        return view('admin.celebrities.monthly_report', [
            'year' => $year,
            'month' => $month,
            'stats' => [
                'active_celebrities' => $activeCelebrities,
                'meeting_minimum' => $meetingMinimum,
                'below_minimum' => $belowMinimum,
                'penalties_applied' => $penaltiesApplied,
            ],
            'tiers' => MarketerMonthlyQuotaProgress::query()->distinct()->orderBy('promotion_category')->pluck('promotion_category'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = MarketerMonthlyQuotaProgress::query()
            ->leftJoin('marketers', 'marketers.id', '=', 'marketer_monthly_quota_progress.marketer_id')
            ->select('marketer_monthly_quota_progress.*', 'marketers.name as marketer_name');

        $query = $this->applyFilters($query, $request, [
            'tier' => fn ($q, $v) => $q->where('marketer_monthly_quota_progress.promotion_category', $v),
            'year' => fn ($q, $v) => $q->where('marketer_monthly_quota_progress.year', $v),
            'month' => fn ($q, $v) => $q->where('marketer_monthly_quota_progress.month', $v),
            'status' => function ($q, $v) {
                match ($v) {
                    'meeting' => $q->whereColumn('marketer_monthly_quota_progress.completed_count', '>=', 'marketer_monthly_quota_progress.quota_target'),
                    'below' => $q->whereColumn('marketer_monthly_quota_progress.completed_count', '<', 'marketer_monthly_quota_progress.quota_target'),
                    'penalty' => $q->where('marketer_monthly_quota_progress.penalty_applied', true),
                    default => null,
                };
            },
        ]);

        $columns = [
            0 => ['searchable_columns' => ['marketers.name']],
            1 => ['orderable_column' => 'marketer_monthly_quota_progress.promotion_category'],
            2 => ['orderable_column' => 'marketer_monthly_quota_progress.year'],
            3 => ['orderable_column' => 'marketer_monthly_quota_progress.completed_count'],
            4 => ['orderable_column' => 'marketer_monthly_quota_progress.quota_target'],
            5 => [],
            6 => [],
            7 => ['orderable_column' => 'marketer_monthly_quota_progress.penalty_applied'],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (MarketerMonthlyQuotaProgress $stat) {
            $completed = (int) $stat->completed_count;
            $minimum = max(1, (int) $stat->quota_target);
            $pct = min(100, (int) round(($completed / $minimum) * 100));

            $status = $stat->penalty_applied
                ? 'penalty'
                : ($stat->is_below_quota ? 'below' : 'meeting');

            return [
                'DT_RowId' => 'stat-' . $stat->id,
                'marketer' => '<span class="text-sm font-medium text-gray-900">' . e($stat->marketer_name ?? '—') . '</span>',
                'tier' => '<span class="text-sm text-gray-700">' . e(__('admin.celebrity_report.tier_label', ['tier' => $stat->promotion_category])) . '</span>',
                'period' => '<span class="text-sm text-gray-700">' . sprintf('%02d/%04d', $stat->month, $stat->year) . '</span>',
                'completed' => '<span class="text-sm text-gray-700">' . $completed . '</span>',
                'minimum' => '<span class="text-sm text-gray-700">' . (int) $stat->quota_target . '</span>',
                'progress' => '<div x-data="{ completed: ' . $completed . ', minimum: ' . $minimum . ' }" class="w-32">'
                    . '<div class="flex items-center justify-between text-xs text-gray-500 mb-0.5"><span>' . $completed . '/' . (int) $stat->quota_target . '</span><span>' . $pct . '%</span></div>'
                    . '<div class="h-1.5 w-full rounded-full bg-gray-100">'
                    . '<div class="h-1.5 rounded-full" :class="completed >= minimum ? \'bg-green-500\' : completed >= minimum * 0.5 ? \'bg-orange-500\' : \'bg-red-500\'" style="width:' . $pct . '%"></div>'
                    . '</div></div>',
                'status' => $this->statusBadgeHtml($status),
                'penalty' => $stat->penalty_applied
                    ? '<span class="text-sm text-red-600">' . number_format((int) $stat->penalty_amount) . '</span>'
                    : '<span class="text-xs text-gray-300">—</span>',
            ];
        });
    }

    public function export(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $stats = MarketerMonthlyQuotaProgress::query()
            ->leftJoin('marketers', 'marketers.id', '=', 'marketer_monthly_quota_progress.marketer_id')
            ->select('marketer_monthly_quota_progress.*', 'marketers.name as marketer_name')
            ->where('marketer_monthly_quota_progress.year', $year)
            ->where('marketer_monthly_quota_progress.month', $month)
            ->get();

        $headers = [
            __('admin.celebrity_report.marketer_column'),
            __('admin.celebrity_report.tier_column'),
            __('admin.celebrity_report.period_column'),
            __('admin.celebrity_report.completed_column'),
            __('admin.celebrity_report.minimum_column'),
            __('admin.celebrity_report.status_column'),
            __('admin.celebrity_report.penalty_column'),
        ];

        $rows = $stats->map(fn (MarketerMonthlyQuotaProgress $stat) => [
            $stat->marketer_name ?? '—',
            $stat->promotion_category,
            sprintf('%02d/%04d', $stat->month, $stat->year),
            $stat->completed_count,
            $stat->quota_target,
            $stat->penalty_applied ? 'Penalty Applied' : ($stat->is_below_quota ? 'Below Minimum' : 'Meeting Minimum'),
            $stat->penalty_applied ? $stat->penalty_amount : '',
        ]);

        return $this->exportCsv('celebrity-monthly-report-' . $year . '-' . $month, $headers, $rows);
    }

    private function statusBadgeHtml(string $status): string
    {
        $class = match ($status) {
            'meeting' => 'bg-green-100 text-green-700',
            'below' => 'bg-orange-100 text-orange-700',
            'penalty' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-500',
        };

        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $class . '">' . e(__('admin.celebrity_report.status_' . $status)) . '</span>';
    }
}
