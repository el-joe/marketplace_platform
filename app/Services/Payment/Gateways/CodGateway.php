<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;

/**
 * Null-object strategy for Cash on Delivery.
 * No external API — creates a pending transaction
 * to be settled when the order is delivered.
 */
class CodGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'cod';
    }
    public function getName(): string
    {
        return 'Cash on Delivery';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['cod'];
    }

    public function charge(
        Order $order,
        int $amountCents,
        string $currency,
        string $paymentToken,
        string $idempotencyKey
    ): PaymentResult {
        // No API call — just create a pending transaction.
        // Amount will be collected on delivery.
        return PaymentResult::success(
            txId: 'COD-' . $idempotencyKey,
            amount: $amountCents,
            currency: $currency,
        );
    }

    public function refund(
        PaymentTransaction $transaction,
        int $amountCents,
        string $reason
    ): RefundResult {
        // Mark as refund pending; cash will be returned physically
        return RefundResult::success(
            refundTxId: 'COD-REFUND-' . $transaction->id,
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
        return true; // No webhooks for COD
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        return PaymentResult::failure('not_supported', 'COD does not use webhooks.');
    }

    public function testConnection(): array
    {
        return [
            'success' => true,
            'latency_ms' => 0,
            'message' => 'COD requires no external connection.',
        ];
    }
}
