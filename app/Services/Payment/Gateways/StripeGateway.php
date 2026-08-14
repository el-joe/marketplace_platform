<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    private ?string $apiKey;
    private ?string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret', '');
    }

    public function getCode(): string
    {
        return 'stripe';
    }
    public function getName(): string
    {
        return 'Stripe';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['card'];
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, '')
            ->asForm()
            ->timeout(30);
    }

    public function charge(
        Order $order,
        int $amountCents,
        string $currency,
        string $paymentToken,
        string $idempotencyKey
    ): PaymentResult {
        try {
            $start = microtime(true);
            $response = $this->http()
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post($this->baseUrl . '/payment_intents', [
                    'amount' => $amountCents,
                    'currency' => strtolower($currency),
                    'payment_method' => $paymentToken,
                    'confirm' => 'true',
                    'description' => 'Order #' . $order->order_number,
                    'metadata[order_id]' => $order->id,
                ]);

            $latency = round((microtime(true) - $start) * 1000);
            $body = $response->json();

            Log::info('Stripe charge', [
                'order' => $order->order_number,
                'status' => $body['status'] ?? 'unknown',
                'latency_ms' => $latency,
            ]);

            if ($response->successful() && ($body['status'] ?? '') === 'succeeded') {
                return PaymentResult::success(
                    txId: $body['id'],
                    amount: $body['amount'],
                    currency: strtoupper($body['currency']),
                    fee: 0,
                    raw: $body,
                );
            }

            return PaymentResult::failure(
                code: $body['error']['code'] ?? 'stripe_error',
                message: $body['error']['message'] ?? 'Stripe payment failed',
                raw: $body,
            );
        } catch (\Exception $e) {
            Log::error('Stripe charge exception', ['error' => $e->getMessage()]);
            return PaymentResult::failure('gateway_error', $e->getMessage());
        }
    }

    public function refund(
        PaymentTransaction $transaction,
        int $amountCents,
        string $reason
    ): RefundResult {
        try {
            $response = $this->http()
                ->post($this->baseUrl . '/refunds', [
                    'payment_intent' => $transaction->gateway_transaction_id,
                    'amount' => $amountCents,
                    'reason' => 'requested_by_customer',
                    'metadata[note]' => $reason,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? '') === 'succeeded') {
                return RefundResult::success(
                    refundTxId: $body['id'],
                    originalTxId: $transaction->gateway_transaction_id,
                    amount: $amountCents,
                    currency: strtoupper($body['currency'] ?? $transaction->currency),
                    raw: $body,
                );
            }

            return RefundResult::failure(
                code: $body['error']['code'] ?? 'refund_error',
                message: $body['error']['message'] ?? 'Refund failed',
                currency: $transaction->currency,
                raw: $body,
            );
        } catch (\Exception $e) {
            return RefundResult::failure('exception', $e->getMessage(), $transaction->currency ?? '');
        }
    }

    public function void(PaymentTransaction $transaction): PaymentResult
    {
        try {
            $response = $this->http()
                ->post($this->baseUrl . '/payment_intents/' . $transaction->gateway_transaction_id . '/cancel');

            $body = $response->json();

            if ($response->successful()) {
                return PaymentResult::success(
                    txId: $body['id'],
                    amount: $transaction->amount,
                    currency: $transaction->currency,
                    raw: $body,
                );
            }

            return PaymentResult::failure(
                code: $body['error']['code'] ?? 'void_error',
                message: $body['error']['message'] ?? 'Void failed',
                raw: $body,
            );
        } catch (\Exception $e) {
            return PaymentResult::failure('exception', $e->getMessage());
        }
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $webhookSecret = config('services.stripe.webhook_secret', '');
        if (empty($webhookSecret)) {
            return false;
        }

        // Parse Stripe signature header: t=timestamp,v1=hash
        $parts = explode(',', $signature);
        $timestamp = '';
        $hash = '';
        foreach ($parts as $part) {
            [$k, $v] = explode('=', $part, 2);
            if ($k === 't') {
                $timestamp = $v;
            }
            if ($k === 'v1') {
                $hash = $v;
            }
        }

        if (empty($timestamp) || empty($hash)) {
            return false;
        }

        $computed = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);
        return hash_equals($computed, $hash);
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        $type = $payload['type'] ?? '';
        $intent = $payload['data']['object'] ?? [];

        if ($type === 'payment_intent.succeeded') {
            return PaymentResult::success(
                txId: $intent['id'] ?? '',
                amount: $intent['amount'] ?? 0,
                currency: strtoupper($intent['currency'] ?? ''),
                raw: $payload,
            );
        }

        if ($type === 'payment_intent.payment_failed') {
            return PaymentResult::failure(
                code: $intent['last_payment_error']['code'] ?? 'failed',
                message: $intent['last_payment_error']['message'] ?? 'Payment failed',
                raw: $payload,
            );
        }

        return PaymentResult::failure('unknown_event', 'Unhandled webhook event: ' . $type, $payload);
    }

    public function testConnection(): array
    {
        try {
            $config = \App\Models\CountryPaymentMethod::byGateway('stripe')->active()->first();
            if (!$config) {
                return ['success' => false, 'latency_ms' => 0, 'message' => 'No active Stripe config in country_payment_methods. Add one first.'];
            }

            $result = (new \App\Services\Payments\StripeGateway($config))->testConnection();

            return ['success' => $result->success, 'latency_ms' => 0, 'message' => $result->message];
        } catch (\Throwable $e) {
            return ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()];
        }
    }
}
