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

        if (! $order || ! $order->marketer_campaign_id) {
            return;
        }

        // Attribution is resolved synchronously at checkout (CheckoutController::placeOrder)
        // from the customer's session and snapshotted onto the order — this listener runs on
        // a queue worker, which has no access to the original HTTP session/cookies.
        $campaign = MarketerCampaign::find($order->marketer_campaign_id);

        if (! $campaign || $campaign->status !== 'active') {
            return;
        }

        $marketer = $campaign->marketer;

        if (! $marketer) {
            return;
        }

        // Conversion is recorded per sub-order (per vendor), so the commission base is the
        // sub-order's own subtotal, not the whole (potentially multi-vendor) order total.
        $orderValue = $subOrder->subtotal ?? 0;

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
            'click_id' => $this->resolveClickId($campaign->id, $order->customer_id),
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

    private function resolveClickId(string $campaignId, ?string $customerId): ?string
    {
        if (! $customerId) {
            return null;
        }

        // Queue workers have no access to the original HTTP session, so attribution
        // is resolved via the customer's most recent unconverted click instead.
        return MarketerClick::where('campaign_id', $campaignId)
            ->where('customer_id', $customerId)
            ->where('converted', false)
            ->latest('clicked_at')
            ->value('id');
    }
}
