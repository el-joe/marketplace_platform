<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Models\CountryPaymentMethod;
use App\Models\PaymentGatewayWebhookLog;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected CountryPaymentMethod $config,
    ) {}

    protected function credentials(): array
    {
        return $this->config->getCredentials();
    }

    protected function isProduction(): bool
    {
        return $this->config->environment === 'production';
    }

    protected function settlementCurrency(): string
    {
        return $this->config->effective_currency;
    }

    protected function logWebhook(
        array   $payload,
        array   $headers,
        bool    $signatureValid,
        ?string $eventType = null,
        ?string $transactionId = null,
    ): PaymentGatewayWebhookLog {
        return PaymentGatewayWebhookLog::create([
            'country_payment_method_id' => $this->config->id,
            'gateway_code'              => $this->getCode(),
            'event_type'                => $eventType,
            'payload'                   => $payload,
            'headers'                   => $headers,
            'signature_valid'           => $signatureValid,
            'payment_transaction_id'    => $transactionId,
        ]);
    }
}
