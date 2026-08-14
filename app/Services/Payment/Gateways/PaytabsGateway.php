<?php

namespace App\Services\Payment\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use App\DTOs\RefundResult;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaytabsGateway implements PaymentGatewayInterface
{
    private ?string $profileId;
    private ?string $serverKey;
    private ?string $baseUrl;

    public function __construct()
    {
        $this->profileId = config('services.paytabs.profile_id', '');
        $this->serverKey = config('services.paytabs.server_key', '');
        $this->baseUrl = config('services.paytabs.base_url', 'https://secure.paytabs.com');
    }

    public function getCode(): string
    {
        return 'paytabs';
    }
    public function getName(): string
    {
        return 'PayTabs';
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
        try {
            $start = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->baseUrl . '/payment/request', [
                    'profile_id' => $this->profileId,
                    'tran_type' => 'sale',
                    'tran_class' => 'ecom',
                    'cart_id' => $idempotencyKey,
                    'cart_currency' => $currency,
                    'cart_amount' => $amountCents / 100,
                    'cart_description' => 'Order #' . $order->order_number,
                    'paypage_lang' => 'en',
                    'customer_details' => [
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                    ],
                    'payment_token' => $paymentToken,
                ]);

            $latency = round((microtime(true) - $start) * 1000);
            $body = $response->json();

            Log::info('Paytabs charge', [
                'order' => $order->order_number,
                'status' => $body['payment_result']['response_status'] ?? 'unknown',
                'latency_ms' => $latency,
            ]);

            if (
                $response->successful()
                && ($body['payment_result']['response_status'] ?? '') === 'A'
            ) {
                return PaymentResult::success(
                    txId: $body['tran_ref'],
                    amount: $amountCents,
                    currency: $currency,
                    fee: 0,
                    raw: $body,
                );
            }

            return PaymentResult::failure(
                code: $body['payment_result']['response_code'] ?? 'unknown',
                message: $body['payment_result']['response_message'] ?? 'Payment failed',
                raw: $body,
            );
        } catch (\Exception $e) {
            Log::error('Paytabs charge exception', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
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
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/payment/request', [
                    'profile_id' => $this->profileId,
                    'tran_type' => 'refund',
                    'tran_class' => 'ecom',
                    'cart_id' => 'REFUND-' . $transaction->id,
                    'cart_currency' => $transaction->currency,
                    'cart_amount' => $amountCents / 100,
                    'cart_description' => 'Refund: ' . $reason,
                    'tran_ref' => $transaction->gateway_transaction_id,
                ]);

            $body = $response->json();

            if (
                $response->successful()
                && ($body['payment_result']['response_status'] ?? '') === 'A'
            ) {
                return RefundResult::success(
                    refundTxId: $body['tran_ref'],
                    originalTxId: $transaction->gateway_transaction_id,
                    amount: $amountCents,
                    currency: $transaction->currency,
                    raw: $body,
                );
            }

            return RefundResult::failure(
                code: $body['payment_result']['response_code'] ?? 'error',
                message: $body['payment_result']['response_message'] ?? 'Refund failed',
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
                'Authorization' => $this->serverKey,
                'Content-Type' => 'application/json',
            ])
                ->post($this->baseUrl . '/payment/request', [
                    'profile_id' => $this->profileId,
                    'tran_type' => 'void',
                    'tran_class' => 'ecom',
                    'cart_id' => 'VOID-' . $transaction->id,
                    'cart_currency' => $transaction->currency,
                    'cart_amount' => $transaction->amount / 100,
                    'cart_description' => 'Void transaction',
                    'tran_ref' => $transaction->gateway_transaction_id,
                ]);

            $body = $response->json();

            if (
                $response->successful()
                && ($body['payment_result']['response_status'] ?? '') === 'A'
            ) {
                return PaymentResult::success(
                    $body['tran_ref'],
                    $transaction->amount,
                    $transaction->currency,
                    0,
                    $body
                );
            }

            return PaymentResult::failure(
                $body['payment_result']['response_code'] ?? 'error',
                $body['payment_result']['response_message'] ?? 'Void failed',
                $body
            );
        } catch (\Exception $e) {
            return PaymentResult::failure('exception', $e->getMessage());
        }
    }

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $computed = hash('sha256', $this->serverKey . $payload);
        return hash_equals($computed, $signature);
    }

    public function parseWebhook(array $payload): PaymentResult
    {
        $status = $payload['payment_result']['response_status'] ?? '';
        if ($status === 'A') {
            return PaymentResult::success(
                txId: $payload['tran_ref'],
                amount: (int) (($payload['cart_amount'] ?? 0) * 100),
                currency: $payload['cart_currency'] ?? 'USD',
                raw: $payload,
            );
        }

        return PaymentResult::failure(
            code: $payload['payment_result']['response_code'] ?? 'unknown',
            message: $payload['payment_result']['response_message'] ?? 'Failed',
            raw: $payload,
        );
    }

    public function testConnection(): array
    {
        return [
            'success' => false,
            'latency_ms' => 0,
            'message' => 'Paytabs DB-backed gateway not yet implemented. Configure credentials in country_payment_methods when ready.',
        ];
    }
}
