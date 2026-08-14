<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\CountryPaymentMethod;
use App\Models\Order;
use App\Models\PaymentTransaction;

/**
 * Thin adapter so Thawani appears in the legacy factory (admin status bar
 * and gateway driver dropdown). Real payment processing goes through
 * App\Services\Payments\ThawaniGateway, which reads DB credentials.
 */
class ThawaniGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'thawani';
    }

    public function getName(): string
    {
        return 'Thawani';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['card'];
    }

    public function charge(
        Order $order,
        int $amountCents,
        string $currency,
        string $paymentToken,
        string $idempotencyKey
    ): PaymentResult {
        throw new \RuntimeException('Use App\Services\Payments\ThawaniGateway via PaymentService instead.');
    }

    public function refund(
        PaymentTransaction $transaction,
        int $amountCents,
        string $reason
    ): RefundResult {
        throw new \RuntimeException('Use App\Services\Payments\ThawaniGateway via PaymentService instead.');
    }

    public function void(PaymentTransaction $transaction): PaymentResult
    {
        throw new \RuntimeException('Not supported.');
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        return false;
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        return PaymentResult::failure('not_supported', 'Use App\Services\Payments\ThawaniGateway instead.', $payload);
    }

    public function testConnection(): array
    {
        try {
            $config = CountryPaymentMethod::byGateway('thawani')->active()->first();
            if (!$config) {
                return ['success' => false, 'latency_ms' => 0, 'message' => 'No active Thawani config found in country_payment_methods.'];
            }

            $result = (new \App\Services\Payments\ThawaniGateway($config))->testConnection();

            return ['success' => $result->success, 'latency_ms' => 0, 'message' => $result->message];
        } catch (\Throwable $e) {
            return ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()];
        }
    }
}
