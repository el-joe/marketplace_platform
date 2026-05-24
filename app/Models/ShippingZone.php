<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function originRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'origin_zone_id');
    }

    public function destinationRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'destination_zone_id');
    }
}
