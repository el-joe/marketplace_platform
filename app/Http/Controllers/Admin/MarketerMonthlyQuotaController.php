<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionCategory;
use App\Http\Controllers\Controller;
use App\Models\Marketer;
use App\Models\MarketerMonthlyQuota;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Notifications\Marketer\MonthlyQuotaWarning;
use App\Traits\HasDataTable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class MarketerMonthlyQuotaController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        return view('admin.marketer-quotas.index', [
            'marketers' => Marketer::orderBy('name')->get(['id', 'name']),
            'categories' => PromotionCategory::options(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = MarketerMonthlyQuota::query()
            ->leftJoin('marketers', 'marketers.id', '=', 'marketer_monthly_quotas.marketer_id')
            ->select('marketer_monthly_quotas.*', 'marketers.name as marketer_name');

        $query = $this->applyFilters($query, $request, [
            'marketer_id' => fn ($q, $v) => $q->where('marketer_monthly_quotas.marketer_id', $v),
            'promotion_category' => fn ($q, $v) => $q->where('marketer_monthly_quotas.promotion_category', $v),
            'is_active' => fn ($q, $v) => $q->where('marketer_monthly_quotas.is_active', (bool) (int) $v),
        ]);

        $columns = [
            0 => ['searchable_columns' => ['marketers.name']],
            1 => ['orderable_column' => 'marketer_monthly_quotas.promotion_category'],
            2 => ['orderable_column' => 'marketer_monthly_quotas.min_promotions_per_month'],
            3 => ['orderable_column' => 'marketer_monthly_quotas.penalty_per_missing'],
            4 => ['orderable_column' => 'marketer_monthly_quotas.is_active'],
            5 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function ($q) {
            $category = PromotionCategory::tryFrom($q->promotion_category);

            return [
                'DT_RowId' => 'quota-' . $q->id,
                'marketer' => $q->marketer_id
                    ? '<span class="text-sm font-medium text-gray-900">' . e($q->marketer_name ?? '—') . '</span>'
                    : '<span class="text-sm font-medium text-primary-600">' . e(__('admin.marketer_quotas.global_default')) . '</span>',
                'category' => '<span class="text-sm text-gray-700">' . e($category?->label() ?? '—') . '</span>',
                'min_per_month' => '<span class="text-sm text-gray-700">' . (int) $q->min_promotions_per_month . '</span>',
                'penalty' => '<span class="text-sm text-gray-700">' . number_format($q->penalty_per_missing) . ($q->penalty_currency ? ' ' . e($q->penalty_currency) : '') . '</span>',
                'active' => '<button type="button" dir="ltr" class="btn-toggle-active relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none ' . ($q->is_active ? 'bg-emerald-500' : 'bg-gray-200') . '" data-id="' . $q->id . '"><span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform ' . ($q->is_active ? 'translate-x-4' : 'translate-x-0.5') . '"></span></button>',
                'actions' => '<button type="button" class="btn-edit-quota text-xs text-primary-600 font-medium hover:underline" data-quota="' . e(json_encode($q->only(['id', 'marketer_id', 'promotion_category', 'min_promotions_per_month', 'penalty_per_missing', 'penalty_currency', 'is_active']))) . '">' . e(__('common.edit')) . '</button> <button type="button" class="btn-delete-quota ms-2 text-xs text-red-500 hover:underline" data-id="' . $q->id . '">' . e(__('common.delete')) . '</button>',
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'marketer_id' => 'nullable|uuid|exists:marketers,id',
            'promotion_category' => 'required|integer|in:1,2,3,4',
            'min_promotions_per_month' => 'required|integer|min:0',
            'penalty_per_missing' => 'nullable|integer|min:0',
            'penalty_currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $quota = MarketerMonthlyQuota::create([
                'marketer_id' => $data['marketer_id'] ?? null,
                'promotion_category' => $data['promotion_category'],
                'min_promotions_per_month' => $data['min_promotions_per_month'],
                'penalty_per_missing' => $data['penalty_per_missing'] ?? 0,
                'penalty_currency' => $data['penalty_currency'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'set_by_admin_id' => $admin->id,
            ]);
        } catch (QueryException) {
            return response()->json(['success' => false, 'message' => __('admin.marketer_quotas.duplicate_quota')], 422);
        }

        return response()->json(['success' => true, 'message' => __('admin.marketer_quotas.created_success'), 'quota' => $quota]);
    }

    public function update(Request $request, MarketerMonthlyQuota $quota): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'marketer_id' => 'nullable|uuid|exists:marketers,id',
            'promotion_category' => 'required|integer|in:1,2,3,4',
            'min_promotions_per_month' => 'required|integer|min:0',
            'penalty_per_missing' => 'nullable|integer|min:0',
            'penalty_currency' => 'nullable|string|size:3',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $quota->update([
                'marketer_id' => $data['marketer_id'] ?? null,
                'promotion_category' => $data['promotion_category'],
                'min_promotions_per_month' => $data['min_promotions_per_month'],
                'penalty_per_missing' => $data['penalty_per_missing'] ?? 0,
                'penalty_currency' => $data['penalty_currency'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'set_by_admin_id' => $admin->id,
            ]);
        } catch (QueryException) {
            return response()->json(['success' => false, 'message' => __('admin.marketer_quotas.duplicate_quota')], 422);
        }

        return response()->json(['success' => true, 'message' => __('admin.marketer_quotas.updated_success'), 'quota' => $quota->fresh()]);
    }

    public function destroy(MarketerMonthlyQuota $quota): JsonResponse
    {
        $quota->delete();

        return response()->json(['success' => true, 'message' => __('admin.marketer_quotas.deleted_success')]);
    }

    public function toggleActive(MarketerMonthlyQuota $quota): JsonResponse
    {
        $quota->update(['is_active' => !$quota->is_active]);

        return response()->json(['success' => true, 'is_active' => $quota->is_active]);
    }

    public function progress(Request $request): View
    {
        return view('admin.marketer-quotas.progress', [
            'categories' => PromotionCategory::options(),
            'year' => (int) $request->input('year', now()->year),
            'month' => (int) $request->input('month', now()->month),
        ]);
    }

    public function progressDatatable(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $query = Marketer::query()
            ->whereHas('monthlyQuotaProgress', fn ($q) => $q->forPeriod($year, $month));

        $query = $this->applyFilters($query, $request, [
            'search' => fn ($q, $v) => $q->where('name', 'like', '%' . $v . '%'),
        ]);

        $columns = [
            0 => ['searchable_columns' => ['name']],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
        ];

        return $this->dataTableResponse($request, $query, $columns, function (Marketer $marketer) use ($year, $month) {
            $rows = $marketer->monthlyQuotaProgress()->forPeriod($year, $month)->get()->keyBy('promotion_category');

            $cells = [];
            $overallStatus = 'met';
            foreach (PromotionCategory::cases() as $category) {
                $row = $rows->get($category->value);

                if (!$row) {
                    $cells[] = '<span class="text-xs text-gray-300">—</span>';
                    continue;
                }

                $cells[] = $this->progressCellHtml($row);
                $overallStatus = $this->worseStatus($overallStatus, $row->status);
            }

            return [
                'DT_RowId' => 'progress-' . $marketer->id,
                'marketer' => '<span class="text-sm font-medium text-gray-900">' . e($marketer->name) . '</span>',
                'cat_1' => $cells[0],
                'cat_2' => $cells[1],
                'cat_3' => $cells[2],
                'cat_4' => $cells[3],
                'status' => $this->statusBadgeHtml($overallStatus),
            ];
        });
    }

    public function sendBulkWarnings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $sent = 0;

        MarketerMonthlyQuotaProgress::query()
            ->where('year', $data['year'])
            ->where('month', $data['month'])
            ->where('status', 'in_progress')
            ->whereColumn('quota_target', '>', 'completed_count')
            ->whereRaw('completed_count < quota_target * 0.5')
            ->with('marketer')
            ->chunkById(200, function ($progressRows) use (&$sent) {
                foreach ($progressRows as $progress) {
                    if (!$progress->marketer) {
                        continue;
                    }

                    Notification::send($progress->marketer, new MonthlyQuotaWarning($progress));

                    $progress->update([
                        'warning_sent' => true,
                        'warning_sent_at' => now(),
                        'status' => 'warning_sent',
                    ]);

                    $sent++;
                }
            });

        return response()->json([
            'success' => true,
            'message' => trans_choice('admin.marketer_quotas.warnings_sent', $sent, ['count' => $sent]),
            'sent' => $sent,
        ]);
    }

    private function progressCellHtml(MarketerMonthlyQuotaProgress $row): string
    {
        $target = max(1, $row->quota_target);
        $pct = min(100, (int) round(($row->completed_count / $target) * 100));
        $badgeClass = $this->statusBarClass($row->status);

        return '<div class="w-32"><div class="flex items-center justify-between text-xs text-gray-500 mb-0.5"><span>' . (int) $row->completed_count . '/' . (int) $row->quota_target . '</span><span>' . $pct . '%</span></div><div class="h-1.5 w-full rounded-full bg-gray-100"><div class="h-1.5 rounded-full ' . $badgeClass . '" style="width:' . $pct . '%"></div></div></div>';
    }

    private function statusBarClass(string $status): string
    {
        return match ($status) {
            'met' => 'bg-green-500',
            'warning_sent' => 'bg-yellow-500',
            'failed', 'penalised' => 'bg-red-500',
            default => 'bg-gray-400',
        };
    }

    private function statusBadgeHtml(string $status): string
    {
        $class = match ($status) {
            'met' => 'bg-green-100 text-green-700',
            'warning_sent' => 'bg-yellow-100 text-yellow-700',
            'failed', 'penalised' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-500',
        };

        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $class . '">' . e(__('admin.marketer_quotas.status_' . $status)) . '</span>';
    }

    private function worseStatus(string $current, string $candidate): string
    {
        $rank = ['met' => 0, 'in_progress' => 1, 'warning_sent' => 2, 'failed' => 3, 'penalised' => 3];

        return ($rank[$candidate] ?? 0) > ($rank[$current] ?? 0) ? $candidate : $current;
    }
}
