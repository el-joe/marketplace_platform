<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ClassifiedListingMarketerStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Paused = 'paused';
}
