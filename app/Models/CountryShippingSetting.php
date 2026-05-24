<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryShippingSetting extends Model
{
    protected $table = 'country_shipping_settings';

    protected $keyType = 'string';
    protected $incrementing = false;

    protected $fillable = [
        'id',
        'country_id',
        'shipping_method_id',
        'is_active',
        'free_shipping_threshold_cents',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'free_shipping_threshold_cents' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
