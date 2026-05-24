<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'origin_zone_id',
        'destination_zone_id',
        'shipping_method_id',
        'carrier_id',
        'base_fee',
        'rate_per_kg',
        'min_weight_grams',
        'volumetric_divisor',
        'free_shipping_threshold',
        'cod_extra_fee',
        'is_active',
    ];

    public function originZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'origin_zone_id');
    }

    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'destination_zone_id');
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }
}
