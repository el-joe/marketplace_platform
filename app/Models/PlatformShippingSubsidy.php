<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformShippingSubsidy extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'shipping_zone_id',
        'shipping_method_id',
        'currency',
        'subsidy_cap',
        'max_subsidy_weight_grams',
        'is_active',
        'created_by_admin_id',
    ];

    protected $casts = [
        'subsidy_cap' => 'integer',
        'max_subsidy_weight_grams' => 'integer',
        'is_active' => 'boolean',
    ];

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
