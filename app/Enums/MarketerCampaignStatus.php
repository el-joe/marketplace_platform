<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerCampaignStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
}
