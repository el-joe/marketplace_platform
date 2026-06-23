<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerCampaignProduct extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_id',
        'vendor_listing_id',
        'position',
        'commission_override',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'commission_override' => 'decimal:2',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }
}
