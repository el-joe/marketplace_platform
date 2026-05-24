<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerPlacementDefinition extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'width_px',
        'height_px',
        'max_file_size_kb',
        'allowed_formats',
        'device_restriction',
        'max_simultaneous',
        'supports_vendor_ads',
        'base_rate_weekly_cents',
        'is_active',
        'sort_order',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(PaidAdSlot::class, 'placement_definition_id');
    }
}
