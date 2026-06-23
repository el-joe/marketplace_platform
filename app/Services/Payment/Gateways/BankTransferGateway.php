<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;

/**
 * Bank Transfer gateway — no external API.
 * Creates a pending transaction and customer is notified
 * with bank account details via the notification system.
 */
class BankTransferGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'bank_transfer';
    }
    public function getName(): string
    {
        return 'Bank Transfer';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['bank_transfer'];
    }

    public function charge(
        Order $order,
        int $amountCents,
        string $currency,
        string $paymentToken,
        string $idempotencyKey
    ): PaymentResult {
        // Creates a pending transaction.
        // Customer receives bank account details via notification.
        return new PaymentResult(
            success: true,
            status: 'pending',  // pending until admin confirms receipt
            gatewayTransactionId: 'BT-' . $idempotencyKey,
            amountCents: $amountCents,
            currency: $currency,
            gatewayFeeCents: 0,
            failureCode: null,
            failureMessage: null,
            rawResponse: [],
        );
    }

    public function refund(
        PaymentTransaction $transaction,
        int $amountCents,
        string $reason
    ): RefundResult {
        // Admin manually issues refund via bank transfer
        return RefundResult::success(
            refundTxId: 'BT-REFUND-' . $transaction->id,
            originalTxId: $transaction->gateway_transaction_id,
            amount: $amountCents,
            currency: $transaction->currency,
        );
    }

    public function void(PaymentTransaction $transaction): PaymentResult
    {
        return PaymentResult::success(
            txId: $transaction->gateway_transaction_id,
            amount: $transaction->amount,
            currency: $transaction->currency,
        );
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        return true;
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        return PaymentResult::failure('not_supported', 'Bank Transfer does not use webhooks.');
    }

    public function testConnection(): array
    {
        return [
            'success' => true,
            'latency_ms' => 0,
            'message' => 'Bank Transfer requires no external connection.',
        ];
    }
}
