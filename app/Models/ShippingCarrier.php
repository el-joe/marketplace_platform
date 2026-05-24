<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ShippingCarrier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'api_endpoint',
        'credentials_encrypted',
        'tracking_url_pattern',
        'supports_cod',
        'supports_returns',
        'is_active',
    ];

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
