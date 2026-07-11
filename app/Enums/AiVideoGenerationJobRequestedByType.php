<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

/**
 * Requester type for ai_video_generation_jobs.requested_by_type.
 *
 * NOTE: distinct from AiJobRequestedByType (used by ai_image_enhancement_jobs)
 * because this table allows 'marketer' instead of 'admin'.
 */
enum AiVideoGenerationJobRequestedByType: string
{
    use EnumHelpers;

    case Vendor = 'vendor';
    case Marketer = 'marketer';
}
