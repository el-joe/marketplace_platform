<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaignKeyword extends Model
{
    protected $fillable = [
        'ad_campaign_id',
        'keyword',
        'keyword_normalized',
        'match_type',
        'bid_override',
        'is_negative',
        'is_active',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }
}
