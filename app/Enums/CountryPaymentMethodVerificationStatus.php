<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum CountryPaymentMethodVerificationStatus: string
{
    use EnumHelpers;

    case Success = 'success';
    case Failed = 'failed';
}
