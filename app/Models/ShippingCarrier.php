<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ShippingCarrier extends Model
{
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'carrier_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'carrier_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
