<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerWhatsappLinkType: string
{
    use EnumHelpers;

    case Discount = 'discount';
    case FreeShipping = 'free_shipping';
    case Both = 'both';
    case Custom = 'custom';
}
