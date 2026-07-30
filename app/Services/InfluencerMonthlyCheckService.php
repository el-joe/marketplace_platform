<?php

namespace App\Services;

use App\Enums\MarketerStatus;
use App\Models\InfluencerMonthlyMinimum;
use App\Models\InfluencerMonthlyStat;
use App\Models\Marketer;
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
                $stat = InfluencerMonthlyStat::query()->firstOrCreate(
                    [
                        'marketer_id' => $celebrity->id,
                        'tier' => $tier,
                        'month' => now()->month,
                        'year' => now()->year,
                    ],
                    [
                        'promotions_completed' => 0,
                        'monthly_minimum_snapshot' => (int) Setting::get("promotion_tier{$tier}_monthly_minimum", 3),
                    ]
                );

                if ($stat->is_below_minimum && ! $stat->below_minimum_alert_sent) {
                    $stat->below_minimum_alert_sent = true;
                    $stat->below_minimum_alert_sent_at = now();
                    $stat->save();

                    // dispatch(new NotifyCelebrityBelowMinimum($celebrity, $stat));
                    // WhatsApp notification + in-dashboard red alert
                }
            }
        }
    }

    public function applyMonthEndPenalties(): void
    {
        $prevMonth = now()->subMonth();

        $stats = InfluencerMonthlyStat::query()
            ->where('month', $prevMonth->month)
            ->where('year', $prevMonth->year)
            ->where('penalty_applied', false)
            ->get()
            ->filter(fn (InfluencerMonthlyStat $stat) => $stat->is_below_minimum);

        foreach ($stats as $stat) {
            $shortfall = $stat->monthly_minimum_snapshot - $stat->promotions_completed;

            $minimum = InfluencerMonthlyMinimum::query()
                ->where('tier', $stat->tier)
                ->where('is_active', true)
                ->first();

            if (! $minimum || $minimum->penalty_per_unmet_promotion === 0) {
                continue;
            }

            $penaltyAmount = $shortfall * $minimum->penalty_per_unmet_promotion; // BIGINT — no /100

            DB::transaction(function () use ($stat, $penaltyAmount) {
                $stat->penalty_applied = true;
                $stat->penalty_amount = $penaltyAmount;
                $stat->penalty_applied_at = now();
                $stat->save();

                // TODO: deduct from celebrity wallet/earnings
                // dispatch(new ApplyCelebrityPenalty($stat->marketer, $penaltyAmount));
            });
        }
    }
}
