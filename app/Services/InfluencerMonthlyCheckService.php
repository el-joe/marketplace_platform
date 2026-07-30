<?php

namespace App\Services;

use App\Enums\MarketerStatus;
use App\Models\Marketer;
use App\Models\MarketerMonthlyQuota;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Models\Setting;
use App\Models\VendorInfluencerPromotionRequestItem;
use Illuminate\Support\Facades\DB;

class InfluencerMonthlyCheckService
{
    public function __construct(
        private readonly InfluencerPromotionRequestService $promotionRequestService,
    ) {}

    public function expireOverdueItems(): int
    {
        $items = VendorInfluencerPromotionRequestItem::query()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($items as $item) {
            DB::transaction(function () use ($item) {
                $item->status = 'expired';
                $item->save();

                $this->promotionRequestService->autoReassign($item);
            });
        }

        return $items->count();
    }

    public function checkMonthlyMinimums(): void
    {
        $celebrities = Marketer::query()
            ->whereIn('type', ['celebrity', 'influencer'])
            ->where('status', MarketerStatus::Active)
            ->whereNotNull('celebrity_tiers')
            ->get();

        foreach ($celebrities as $celebrity) {
            $tiers = $celebrity->celebrity_tiers ?? [];

            foreach ($tiers as $tier) {
                $progress = MarketerMonthlyQuotaProgress::query()->firstOrCreate(
                    [
                        'marketer_id' => $celebrity->id,
                        'promotion_category' => $tier,
                        'month' => now()->month,
                        'year' => now()->year,
                    ],
                    [
                        'completed_count' => 0,
                        'quota_target' => (int) Setting::get("promotion_tier{$tier}_monthly_minimum", 3),
                        'status' => 'in_progress',
                    ]
                );

                if ($progress->is_below_quota && ! $progress->warning_sent) {
                    $progress->warning_sent = true;
                    $progress->warning_sent_at = now();
                    $progress->save();

                    // dispatch(new NotifyCelebrityBelowMinimum($celebrity, $progress));
                    // WhatsApp notification + in-dashboard red alert
                }
            }
        }
    }

    public function applyMonthEndPenalties(): void
    {
        $prevMonth = now()->subMonth();

        $progressRows = MarketerMonthlyQuotaProgress::query()
            ->where('month', $prevMonth->month)
            ->where('year', $prevMonth->year)
            ->where('penalty_applied', false)
            ->get()
            ->filter(fn (MarketerMonthlyQuotaProgress $progress) => $progress->is_below_quota);

        foreach ($progressRows as $progress) {
            $shortfall = $progress->quota_target - $progress->completed_count;

            $quota = MarketerMonthlyQuota::query()
                ->where('promotion_category', $progress->promotion_category)
                ->where('is_active', true)
                ->first();

            if (! $quota || $quota->penalty_per_missing === 0) {
                continue;
            }

            $penaltyAmount = $shortfall * $quota->penalty_per_missing; // BIGINT — no /100

            DB::transaction(function () use ($progress, $penaltyAmount) {
                $progress->penalty_applied = true;
                $progress->penalty_amount = $penaltyAmount;
                $progress->penalty_applied_at = now();
                $progress->save();

                // TODO: deduct from celebrity wallet/earnings
                // dispatch(new ApplyCelebrityPenalty($progress->marketer, $penaltyAmount));
            });
        }
    }
}
