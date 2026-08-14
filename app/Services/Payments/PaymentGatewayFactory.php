<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Models\CountryPaymentMethod;

/**
 * DB-credential-aware factory for the Strategy Pattern gateways.
 * Each gateway receives its CountryPaymentMethod config (with encrypted
 * credentials) rather than pulling from .env/config files.
 */
class PaymentGatewayFactory
{
    /** @var array<string, class-string<AbstractPaymentGateway>> */
    private static array $map = [
        'thawani'       => ThawaniGateway::class,
        'stripe'        => StripeGateway::class,
        'bank_transfer' => BankTransferGateway::class,
        // 'paytabs'    => PaytabsGateway::class,
        // 'tabby'      => TabbyGateway::class,
        // 'noon_pay'   => NoonPayGateway::class,
        // 'cod'        => CodGateway::class,
        // 'paypal'     => PaypalGateway::class,  // add when implemented
    ];

    public static function make(CountryPaymentMethod $config): PaymentGatewayInterface
    {
        $code = $config->gateway_code;

        if (!isset(self::$map[$code])) {
            throw new \InvalidArgumentException(
                "No gateway implementation registered for code: {$code}"
            );
        }

        $class = self::$map[$code];

        return new $class($config);
    }

    public static function makeForCountryAndType(
        string $countryId,
        string $methodType,
    ): PaymentGatewayInterface {
        $config = CountryPaymentMethod::active()
            ->forCountry($countryId)
            ->where('method_type', $methodType)
            ->firstOrFail();

        return self::make($config);
    }

    /** @return string[] */
    public static function availableCodes(): array
    {
        return array_keys(self::$map);
    }
}
