<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\BankTransferGateway;
use App\Services\Payment\Gateways\CodGateway;
use App\Services\Payment\Gateways\NoonPayGateway;
use App\Services\Payment\Gateways\PaytabsGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\TabbyGateway;
use App\Services\Payment\Gateways\ThawaniGateway;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct()
    {
        $this->register(new PaytabsGateway());
        $this->register(new TabbyGateway());
        $this->register(new NoonPayGateway());
        $this->register(new CodGateway());
        $this->register(new BankTransferGateway());
        $this->register(new StripeGateway());
        $this->register(new ThawaniGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getCode()] = $gateway;
    }

    /**
     * Resolve a gateway by its provider code.
     *
     * @throws InvalidArgumentException
     */
    public function make(string $providerCode): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$providerCode])) {
            throw new InvalidArgumentException(__('common.exceptions.payment.unknown_gateway', ['code' => $providerCode]));
        }

        return $this->gateways[$providerCode];
    }

    /**
     * Find the first gateway that handles the given method_type.
     * Falls back to COD if none found.
     */
    public function makeForMethodType(string $methodType): PaymentGatewayInterface
    {
        foreach ($this->gateways as $gateway) {
            if (in_array($methodType, $gateway->getSupportedMethodTypes())) {
                return $gateway;
            }
        }

        return $this->gateways['cod'];
    }

    /** @return PaymentGatewayInterface[] */
    public function all(): array
    {
        return array_values($this->gateways);
    }

    public function allWithCodes(): array
    {
        return $this->gateways;
    }

    /** @return string[] */
    public function allCodes(): array
    {
        return array_keys($this->gateways);
    }
}
