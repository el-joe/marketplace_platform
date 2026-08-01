<?php

namespace App\Services;

use App\Models\MarketerCampaignConversion;
use App\Models\MarketerCampaignInvitation;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LastClickAttributionService
{
    /**
     * Record a referral click from a referral code.
     * Called when a customer visits the referral link so it can be matched at checkout.
     */
    public function recordClick(string $referralCode, string $sessionId): void
    {
        Cache::put("referral_click:{$sessionId}", [
            'referral_code' => $referralCode,
            'clicked_at'    => now()->toISOString(),
        ], now()->addDays(30));
    }

    /**
     * On order placement: resolve last-click attribution and create a conversion record.
     * Called from OrderService after the order is created.
     */
    public function resolveAndRecordConversion(Order $order, string $sessionId): void
    {
        $click = Cache::get("referral_click:{$sessionId}");
        if (!$click) {
            return;
        }

        $invitation = MarketerCampaignInvitation::where('referral_code', $click['referral_code'])
            ->where('status', 'accepted')
            ->with('campaign')
            ->first();

        if (!$invitation) {
            return;
        }
        if (!in_array($invitation->campaign->status, ['active', 'auto_approved'], true)) {
            return;
        }
        if ($invitation->campaign->commission_type !== 'last_click') {
            return;
        }

        DB::transaction(function () use ($invitation, $order, $click) {
            $commissionAmount = $invitation->campaign->marketer_commission_amount;
            if ($commissionAmount === 0) {
                // VERIFY: fallback to MarketerCommissionCountrySetting for auto-approved campaigns
                // that never had an admin-set commission split.
            }

            MarketerCampaignConversion::create([
                'campaign_id'             => $invitation->campaign_id,
                'invitation_id'           => $invitation->id,
                'order_id'                => $order->id,
                'referral_clicked_at'     => $click['clicked_at'],
                'commission_amount'       => $commissionAmount,
                'currency'                => $invitation->campaign->currency,
                'commissioned'            => false,
                'sale_number_in_campaign' => $invitation->total_conversions + 1,
            ]);

            $invitation->increment('total_conversions');
            $invitation->increment('total_commission_earned', $commissionAmount);
        });

        Cache::forget("referral_click:{$sessionId}");
    }
}
