<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AdminProductListingCommissionType: string
{
    use EnumHelpers;

    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case Mixed = 'mixed';
}
