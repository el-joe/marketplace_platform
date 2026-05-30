<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'code',
        'description',
        'min_delivery_days',
        'max_delivery_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_delivery_days' => 'integer',
        'max_delivery_days' => 'integer',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function countrySettings(): HasMany
    {
        return $this->hasMany(CountryShippingSetting::class);
    }

    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }
}
