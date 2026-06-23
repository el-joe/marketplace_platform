<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyGateway implements PaymentGatewayInterface
{
    private ?string $publicKey;
    private ?string $secretKey;
    private ?string $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.tabby.public_key', '');
        $this->secretKey = config('services.tabby.secret_key', '');
        $this->baseUrl = config('services.tabby.base_url', 'https://api.tabby.ai');
    }

    public function getCode(): string
    {
        return 'tabby';
    }
    public function getName(): string
    {
        return 'Tabby (BNPL)';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['bnpl'];
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->baseUrl . '/api/v2/checkout', [
                    'payment' => [
                        'amount' => number_format($amountCents / 100, 2, '.', ''),
                        'currency' => $currency,
                        'description' => 'Order #' . $order->order_number,
                        'buyer' => [
                            'email' => $order->customer->email,
                            'name' => $order->customer->name,
                            'phone' => $order->customer->phone ?? '',
                        ],
                        'order' => [
                            'reference_id' => $idempotencyKey,
                            'items' => [],
                        ],
                    ],
                    'lang' => 'en',
                    'merchant_code' => $this->publicKey,
                    'merchant_urls' => [
                        'success' => url('/'),
                        'cancel' => url('/'),
                        'failure' => url('/'),
                    ],
                ]);

            $latency = round((microtime(true) - $start) * 1000);
            $body = $response->json();

            Log::info('Tabby charge', [
                'order' => $order->order_number,
                'status' => $body['status'] ?? 'unknown',
                'latency_ms' => $latency,
            ]);

            if ($response->successful() && ($body['status'] ?? '') === 'created') {
                return PaymentResult::success(
                    txId: $body['payment']['id'] ?? $idempotencyKey,
                    amount: $amountCents,
                    currency: $currency,
                    raw: $body,
                );
            }

            return PaymentResult::failure(
                code: $body['error'] ?? 'tabby_error',
                message: $body['error_description'] ?? 'Tabby payment failed',
                raw: $body,
            );
        } catch (\Exception $e) {
            Log::error('Tabby charge exception', ['error' => $e->getMessage()]);
            return PaymentResult::failure('gateway_error', $e->getMessage());
        }
    }

    public function refund(
        PaymentTransaction $transaction,
        int $amountCents,
        string $reason
    ): RefundResult {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/api/v2/payments/' . $transaction->gateway_transaction_id . '/refunds', [
                    'amount' => number_format($amountCents / 100, 2, '.', ''),
                ]);

            $body = $response->json();

            if ($response->successful()) {
                return RefundResult::success(
                    refundTxId: $body['id'] ?? '',
                    originalTxId: $transaction->gateway_transaction_id,
                    amount: $amountCents,
                    currency: $transaction->currency,
                    raw: $body,
                );
            }

            return RefundResult::failure('tabby_refund_error', $body['message'] ?? 'Refund failed', $transaction->currency, $body);
        } catch (\Exception $e) {
            return RefundResult::failure('exception', $e->getMessage(), $transaction->currency ?? '');
        }
    }

    public function void(PaymentTransaction $transaction): PaymentResult
    {
        // Tabby does not support void — cancel via full refund
        return PaymentResult::failure('not_supported', 'Tabby does not support void. Use refund instead.');
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($computed, $signature);
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        $status = $payload['status'] ?? '';
        if (in_array($status, ['authorized', 'closed'])) {
            return PaymentResult::success(
                txId: $payload['payment']['id'] ?? '',
                amount: (int) (($payload['payment']['amount'] ?? 0) * 100),
                currency: $payload['payment']['currency'] ?? 'AED',
                raw: $payload,
            );
        }

        return PaymentResult::failure(
            code: $status ?: 'unknown',
            message: 'Tabby payment not authorized',
            raw: $payload,
        );
    }

    public function testConnection(): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->secretKey])
                ->get($this->baseUrl . '/api/v2/merchants');
            $latency = round((microtime(true) - $start) * 1000);

            return [
                'success' => $response->successful(),
                'latency_ms' => $latency,
                'message' => $response->successful()
                    ? 'Connected (' . $latency . 'ms)'
                    : 'Authentication failed (HTTP ' . $response->status() . ')',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()];
        }
    }
}
