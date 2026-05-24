<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSalePriceHistory extends Model
{
    protected $fillable = [
        'vendor_listing_id',
        'price',
        'currency',
        'recorded_at',
        'recorded_by',
    ];

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }
}
