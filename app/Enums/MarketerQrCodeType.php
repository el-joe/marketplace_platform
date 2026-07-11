<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum MarketerQrCodeType: string
{
    use EnumHelpers;

    case MarketerProfile = 'marketer_profile';
    case Product = 'product';
    case VendorStore = 'vendor_store';
    case Campaign = 'campaign';
    case WhatsappLink = 'whatsapp_link';
}
