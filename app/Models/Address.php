<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'label',
        'recipient_name',
        'recipient_phone',
        'country_id',
        'city_id',
        'area',
        'street_address',
        'building',
        'floor',
        'apartment',
        'postal_code',
        'landmark',
        'latitude',
        'longitude',
        'is_default',
        'address_type',
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
