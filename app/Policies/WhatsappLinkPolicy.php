<?php

namespace App\Policies;

use App\Models\Marketer;
use App\Models\MarketerWhatsappLink;

class WhatsappLinkPolicy
{
    public function view(Marketer $marketer, MarketerWhatsappLink $link): bool
    {
        return $link->marketer_id === $marketer->id;
    }
}
