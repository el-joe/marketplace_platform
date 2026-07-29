<?php

namespace App\Services;

use App\Models\Marketer;
use App\Models\MarketerMonthlyQuota;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Models\MarketerPayout;
use App\Notifications\Marketer\MonthlyQuotaWarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MarketerMonthlyQuotaService
{
    /**
     * Resolve the effective monthly quota for a marketer/category, falling back
     * to the global default (marketer_id IS NULL) when no marketer-specific
     * quota is configured. Returns 0 when neither exists.
     */
    public function getEffectiveQuota(Marketer $marketer, int $promotionCategory): int
    {
        $marketerQuota = MarketerMonthlyQuota::query()
            ->where('marketer_id', $marketer->id)
            ->forCategory($promotionCategory)
            ->active()
            ->first();

        if ($marketerQuota) {
            return $marketerQuota->min_promotions_per_month;
        }

        $globalQuota = MarketerMonthlyQuota::query()
            ->globalDefault()
            ->forCategory($promotionCategory)
            ->active()
            ->first();

        return $globalQuota?->min_promotions_per_month ?? 0;
    }

    /**
     * Increment a marketer's progress toward their monthly quota for the given
     * category/period, creating the progress row on first use.
     */
    public function incrementProgress(Marketer $marketer, int $promotionCategory, int $year, int $month): void
    {
        DB::transaction(function () use ($marketer, $promotionCategory, $year, $month) {
            $progress = MarketerMonthlyQuotaProgress::query()
                ->where('marketer_id', $marketer->id)
                ->forCategory($promotionCategory)
                ->forPeriod($year, $month)
                ->lockForUpdate()
                ->first();

            if (!$progress) {
                $progress = MarketerMonthlyQuotaProgress::create([
                    'marketer_id' => $marketer->id,
                    'promotion_category' => $promotionCategory,
                    'year' => $year,
                    'month' => $month,
                    'quota_target' => $this->getEffectiveQuota($marketer, $promotionCategory),
                    'completed_count' => 0,
                    'status' => 'in_progress',
                ]);
            }

            $progress->completed_count += 1;

            if ($progress->completed_count >= $progress->quota_target) {
                $progress->status = 'met';
            }

            $progress->save();
        });
    }

    /**
     * Send WhatsApp warnings to marketers who are still behind quota during the
     * last week of the current month. Intended to be invoked by a scheduled job.
     */
    public function checkAndSendWarnings(): void
    {
        $today = Carbon::today();

        if ($today->day < $today->daysInMonth - 6) {
            return;
        }

        MarketerMonthlyQuotaProgress::query()
            ->where('warning_sent', false)
            ->whereColumn('completed_count', '<', 'quota_target')
            ->where('year', $today->year)
            ->where('month', $today->month)
            ->whereIn('status', ['in_progress'])
            ->with('marketer')
            ->chunkById(200, function ($progressRows) {
                foreach ($progressRows as $progress) {
                    if (!$progress->marketer) {
                        continue;
                    }

                    Notification::send($progress->marketer, new MonthlyQuotaWarning($progress));

                    $progress->warning_sent = true;
                    $progress->warning_sent_at = now();
                    $progress->status = 'warning_sent';
                    $progress->save();
                }
            });
    }

    /**
     * Apply penalties for last month's unmet quotas. Intended to be invoked by a
     * scheduled job on the 1st of each month.
     *
     * Penalty currency comes from marketer_monthly_quotas.penalty_currency; a
     * separate deduction payout is created per progress row so amounts are never
     * summed across currencies.
     */
    public function applyMonthEndPenalties(): void
    {
        $previousMonth = Carbon::today()->subMonthNoOverflow();
        $year = $previousMonth->year;
        $month = $previousMonth->month;

        MarketerMonthlyQuotaProgress::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'met')
            ->where('penalty_applied', false)
            ->chunkById(200, function ($progressRows) {
                foreach ($progressRows as $progress) {
                    $this->applyPenaltyForProgress($progress);
                }
            });
    }

    /**
     * Create fresh in_progress rows for the current month for every active
     * marketer against each promotion category they have a quota for (their
     * own quota, or the global default). Intended to be invoked by a scheduled
     * job on the 1st of each month, alongside applyMonthEndPenalties().
     */
    public function initializeNewMonthProgress(): void
    {
        $today = Carbon::today();
        $year = $today->year;
        $month = $today->month;

        $globalCategories = MarketerMonthlyQuota::query()
            ->globalDefault()
            ->active()
            ->pluck('promotion_category')
            ->all();

        Marketer::query()
            ->active()
            ->with(['monthlyQuotas' => fn ($q) => $q->active()])
            ->chunkById(200, function ($marketers) use ($year, $month, $globalCategories) {
                foreach ($marketers as $marketer) {
                    $categories = $marketer->monthlyQuotas
                        ->pluck('promotion_category')
                        ->merge($globalCategories)
                        ->unique();

                    foreach ($categories as $category) {
                        $exists = MarketerMonthlyQuotaProgress::query()
                            ->where('marketer_id', $marketer->id)
                            ->forCategory($category)
                            ->forPeriod($year, $month)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        MarketerMonthlyQuotaProgress::create([
                            'marketer_id' => $marketer->id,
                            'promotion_category' => $category,
                            'year' => $year,
                            'month' => $month,
                            'quota_target' => $this->getEffectiveQuota($marketer, $category),
                            'completed_count' => 0,
                            'status' => 'in_progress',
                        ]);
                    }
                }
            });
    }

    private function applyPenaltyForProgress(MarketerMonthlyQuotaProgress $progress): void
    {
        DB::transaction(function () use ($progress) {
            $quota = MarketerMonthlyQuota::query()
                ->where('marketer_id', $progress->marketer_id)
                ->forCategory($progress->promotion_category)
                ->active()
                ->first();

            if (!$quota) {
                $quota = MarketerMonthlyQuota::query()
                    ->globalDefault()
                    ->forCategory($progress->promotion_category)
                    ->active()
                    ->first();
            }

            if (!$quota || $quota->penalty_per_missing <= 0) {
                return;
            }

            $missingCount = max(0, $progress->quota_target - $progress->completed_count);

            if ($missingCount === 0) {
                return;
            }

            $penaltyAmount = $missingCount * $quota->penalty_per_missing;

            MarketerPayout::create([
                'marketer_id' => $progress->marketer_id,
                'payout_type' => 'commission',
                'period_start' => Carbon::create($progress->year, $progress->month, 1)->startOfMonth(),
                'period_end' => Carbon::create($progress->year, $progress->month, 1)->endOfMonth(),
                'total_conversions' => 0,
                'gross_commission' => 0,
                'tax_deduction' => 0,
                'net_amount' => -$penaltyAmount,
                'currency' => $quota->penalty_currency,
                'status' => 'pending',
            ]);

            $progress->penalty_applied = true;
            $progress->penalty_amount = $penaltyAmount;
            $progress->penalty_applied_at = now();
            $progress->status = 'penalised';
            $progress->save();
        });
    }
}
