<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerCampaignStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Paused = 'paused';
    case Rejected = 'rejected';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
}
