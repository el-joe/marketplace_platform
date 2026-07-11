<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerStatus: string
{
    use EnumHelpers;

    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
