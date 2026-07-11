<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ClassifiedListingMarketerCommissionType: string
{
    use EnumHelpers;

    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
