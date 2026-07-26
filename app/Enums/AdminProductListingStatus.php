<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum AdminProductListingStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Paused = 'paused';
    case OutOfStock = 'out_of_stock';
    case Archived = 'archived';
}
