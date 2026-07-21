<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NoonPayGateway implements PaymentGatewayInterface
{
    private ?string $appKey;
    private ?string $appSecret;
    private ?string $baseUrl;

    public function __construct()
    {
        $this->appKey = config('services.noon_pay.app_key', '');
        $this->appSecret = config('services.noon_pay.app_secret', '');
        $this->baseUrl = config('services.noon_pay.base_url', 'https://api.noonpay.com');
    }

    public function getCode(): string
    {
        return 'noon_pay';
    }
    public function getName(): string
    {
        return 'Noon Pay';
    }

    public function getSupportedMethodTypes(): array
    {
        return ['wallet'];
    }

    private function authHeader(): string
    {
        return 'Key_Live ' . base64_encode($this->appKey . ':' . $this->appSecret);
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
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->baseUrl . '/payment/v1/order', [
                    'apiOperation' => 'INITIATE',
                    'order' => [
                        'reference' => $idempotencyKey,
                        'amount' => number_format($amountCents / 100, 2, '.', ''),
                        'currency' => $currency,
                        'description' => 'Order #' . $order->order_number,
                        'category' => 'pay',
                    ],
                    'configuration' => [
                        'paymentAction' => 'SALE',
                        'returnUrl' => url('/'),
                    ],
                    'paymentToken' => $paymentToken,
                ]);

            $latency = round((microtime(true) - $start) * 1000);
            $body = $response->json();

            Log::info('NoonPay charge', [
                'order' => $order->order_number,
                'latency_ms' => $latency,
            ]);

            $orderStatus = $body['result']['order']['status'] ?? '';

            if ($response->successful() && in_array($orderStatus, ['CAPTURED', 'SALE'])) {
                return PaymentResult::success(
                    txId: (string) ($body['result']['order']['id'] ?? $idempotencyKey),
                    amount: $amountCents,
                    currency: $currency,
                    raw: $body,
                );
            }

            return PaymentResult::failure(
                code: $body['resultCode'] ?? 'noon_error',
                message: $body['message'] ?? __('common.exceptions.payment.noonpay_payment_failed'),
                raw: $body,
            );
        } catch (\Exception $e) {
            Log::error('NoonPay charge exception', ['error' => $e->getMessage()]);
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
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/payment/v1/order/' . $transaction->gateway_transaction_id . '/refund', [
                    'apiOperation' => 'REFUND',
                    'order' => [
                        'amount' => number_format($amountCents / 100, 2, '.', ''),
                        'currency' => $transaction->currency,
                    ],
                ]);

            $body = $response->json();

            if ($response->successful()) {
                return RefundResult::success(
                    refundTxId: (string) ($body['result']['order']['id'] ?? ''),
                    originalTxId: $transaction->gateway_transaction_id,
                    amount: $amountCents,
                    currency: $transaction->currency,
                    raw: $body,
                );
            }

            return RefundResult::failure(
                code: $body['resultCode'] ?? 'refund_error',
                message: $body['message'] ?? __('common.exceptions.payment.refund_failed'),
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
            $response = Http::withHeaders([
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/payment/v1/order/' . $transaction->gateway_transaction_id . '/reverse', [
                    'apiOperation' => 'VOID',
                ]);

            $body = $response->json();
            if ($response->successful()) {
                return PaymentResult::success(
                    txId: $transaction->gateway_transaction_id,
                    amount: $transaction->amount,
                    currency: $transaction->currency,
                    raw: $body,
                );
            }

            return PaymentResult::failure($body['resultCode'] ?? 'error', $body['message'] ?? __('common.exceptions.payment.void_failed'), $body);
        } catch (\Exception $e) {
            return PaymentResult::failure('exception', $e->getMessage());
        }
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha256', $payload, $this->appSecret);
        return hash_equals($computed, $signature);
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        $status = $payload['result']['order']['status'] ?? '';
        if (in_array($status, ['CAPTURED', 'SALE'])) {
            return PaymentResult::success(
                txId: (string) ($payload['result']['order']['id'] ?? ''),
                amount: (int) (($payload['result']['order']['amount'] ?? 0) * 100),
                currency: $payload['result']['order']['currency'] ?? 'AED',
                raw: $payload,
            );
        }

        return PaymentResult::failure(
            code: $status ?: 'unknown',
            message: 'NoonPay payment not captured',
            raw: $payload,
        );
    }

    public function testConnection(): array
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => $this->authHeader()])
                ->get($this->baseUrl . '/payment/v1/health');
            $latency = round((microtime(true) - $start) * 1000);

            return [
                'success' => $response->status() < 500,
                'latency_ms' => $latency,
                'message' => $response->status() < 500
                    ? 'Connected (' . $latency . 'ms)'
                    : 'Service unavailable',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'latency_ms' => 0, 'message' => $e->getMessage()];
        }
    }
}
