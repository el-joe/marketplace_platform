<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerSampleRequestStatus: string
{
    use EnumHelpers;

    case Requested = 'requested';
    case Approved = 'approved';
    case Dispatched = 'dispatched';
    case Received = 'received';
    case Rejected = 'rejected';
}
