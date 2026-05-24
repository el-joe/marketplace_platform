<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStrike extends Model
{
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function issuedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by_admin_id');
    }
}
