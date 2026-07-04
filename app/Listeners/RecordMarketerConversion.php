<?php

namespace App\Listeners;

use App\Events\SubOrderPlaced;
use App\Models\MarketerCampaign;
use App\Models\MarketerClick;
use App\Models\MarketerConversion;
use App\Models\MarketerSecretPromotion;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMarketerConversion implements ShouldQueue
{
    public function handle(SubOrderPlaced $event): void
    {
        $subOrder = $event->subOrder;
        $order = $subOrder->order;

        if (!$order) {
            return;
        }

        // Resolve attribution: session > cookie
        $campaignId = session('marketer_ref') ?? ($_COOKIE['marketer_ref'] ?? null);

        if (!$campaignId) {
            return;
        }

        $campaign = MarketerCampaign::find($campaignId);

        if (!$campaign || $campaign->status !== 'active') {
            return;
        }

        $marketer = $campaign->marketer;

        // Calculate commission — prefer secret promotion's marketer_share_pct when active
        $orderValue = $order->total ?? $subOrder->total ?? 0;

        $secretPromotion = $campaign->secret_promotion_id
            ? MarketerSecretPromotion::find($campaign->secret_promotion_id)
            : null;

        $effectiveRate = ($secretPromotion && $secretPromotion->isActive())
            ? (float) $secretPromotion->marketer_share_pct
            : (float) $campaign->commission_rate;

        $commissionAmount = match ($campaign->commission_type) {
            'percentage' => (int) round($orderValue * ($effectiveRate / 100)),
            'flat_per_order' => (int) ($campaign->commission_rate * 100),
            'flat_per_click' => 0,
            default => (int) round($orderValue * ($effectiveRate / 100)),
        };

        // Create conversion record
        $conversion = MarketerConversion::create([
            'campaign_id' => $campaign->id,
            'marketer_id' => $marketer->id,
            'click_id' => $this->resolveClickId($campaign->id, $order->id),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'vendor_id' => $subOrder->vendor_id,
            'order_value_cents' => $orderValue,
            'commission_rate' => $effectiveRate,
            'commission_amount_cents' => $commissionAmount,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        // Update campaign totals
        $campaign->increment('total_conversions');
        $campaign->increment('total_revenue_cents', $orderValue);
        $campaign->increment('budget_spent_cents', $commissionAmount);

        // Update marketer totals
        $marketer->increment('total_conversions');

        // Mark originating click as converted
        if ($conversion->click_id) {
            MarketerClick::where('id', $conversion->click_id)
                ->update([
                    'converted' => true,
                    'conversion_order_id' => $order->id,
                ]);
        }
    }

    private function resolveClickId(string $campaignId, string $orderId): ?string
    {
        // Find the most recent click for this campaign in the current session
        return MarketerClick::where('campaign_id', $campaignId)
            ->where('session_id', session()->getId())
            ->where('converted', false)
            ->latest('clicked_at')
            ->value('id');
    }
}
