<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    protected $incrementing = false;

    protected $fillable = [
        'id',
        'country_id',
        'name_ar',
        'name_en',
        'latitude',
        'longitude',
        'shipping_zone_id',
        'is_active',
        'cod_available',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cod_available' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];


    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
