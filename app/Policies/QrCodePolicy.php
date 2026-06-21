<?php

namespace App\Policies;

use App\Models\Marketer;
use App\Models\MarketerQrCode;

class QrCodePolicy
{
    public function view(Marketer $marketer, MarketerQrCode $qrCode): bool
    {
        return $qrCode->marketer_id === $marketer->id;
    }

    public function delete(Marketer $marketer, MarketerQrCode $qrCode): bool
    {
        return $qrCode->marketer_id === $marketer->id;
    }
}
