<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Notifications\Customer\OrderRefundProcessed;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** VAT rate applied to the gateway fee portion that is deducted from a refund. */
    private const GATEWAY_FEE_TAX_RATE = 0.05;

    public function __construct(public readonly Refund $refund)
    {
    }

    public function handle(PaymentService $paymentService): void
    {
        $this->refund->loadMissing('originalTransaction', 'order');

        $originalTransaction = $this->refund->originalTransaction;
        $isElectronicPayment = $this->refund->order->payment_method !== 'cod';

        $gatewayFeeDeductedCents = 0;
        $taxDeductedCents = 0;

        if ($isElectronicPayment) {
            $gatewayFeeDeductedCents = (int) round(
                ((int) $originalTransaction->gateway_fee) * ($this->refund->amount / $originalTransaction->amount)
            );
            $taxDeductedCents = (int) round($gatewayFeeDeductedCents * self::GATEWAY_FEE_TAX_RATE);
        }

        $this->refund->update([
            'gateway_fee_deducted_cents' => $gatewayFeeDeductedCents,
            'tax_deducted_cents' => $taxDeductedCents,
        ]);
        $this->refund->refresh();

        $netRefundCents = $this->refund->net_refund_cents;

        try {
            $result = $paymentService->refund(
                $originalTransaction,
                $netRefundCents,
                $this->refund->reason->value
            );
        } catch (\Throwable $e) {
            $this->refund->update(['status' => 'failed']);
            Log::error('RefundProcessingJob: gateway refund failed.', [
                'refund_id' => $this->refund->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $result->success) {
            $this->refund->update(['status' => 'failed']);
            Log::error('RefundProcessingJob: gateway declined refund.', [
                'refund_id' => $this->refund->id,
                'error' => $result->errorMessage,
            ]);

            return;
        }

        $refundTransaction = $originalTransaction->order
            ->transactions()
            ->where('type', 'refund')
            ->latest('created_at')
            ->first();

        $this->refund->update([
            'status' => 'completed',
            'refund_transaction_id' => $refundTransaction?->id,
        ]);

        $this->refund->order->customer->notify(new OrderRefundProcessed($this->refund));
    }
}
