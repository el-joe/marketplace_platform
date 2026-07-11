<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum CountryPaymentMethodEnvironment: string
{
    use EnumHelpers;

    case Sandbox = 'sandbox';
    case Production = 'production';
}
