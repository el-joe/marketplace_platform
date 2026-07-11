<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerType: string
{
    use EnumHelpers;

    case Influencer = 'influencer';
    case Celebrity = 'celebrity';
    case Affiliate = 'affiliate';
    case BrandAmbassador = 'brand_ambassador';
}
