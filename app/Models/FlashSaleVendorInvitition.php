<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleVendorInvitition extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'vendor_id',
        'invitation_type',
        'status',
        'invited_at',
        'notified_at',
        'responded_at',
        'decline_reason',
        'slots_allocated',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
