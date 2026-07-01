<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCampaignOfferProduct extends Model
{
    use HasUuids;

    protected $fillable = [
        'vendor_campaign_offer_id',
        'vendor_listing_id',
        'position',
        'commission_override',
    ];

    protected $casts = [
        'position' => 'integer',
        'commission_override' => 'decimal:2',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(VendorCampaignOffer::class, 'vendor_campaign_offer_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class, 'vendor_listing_id');
    }
}
