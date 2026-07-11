<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum PaymentMethodType: string
{
    use EnumHelpers;

    case Card = 'card';
    case Wallet = 'wallet';
    case Bank = 'bank';
}
