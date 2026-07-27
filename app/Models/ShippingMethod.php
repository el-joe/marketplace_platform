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
        'badge_label_en',
        'badge_label_ar',
        'badge_color_hex',
        'badge_text_color_hex',
        'delivery_label_en',
        'delivery_label_ar',
        'is_express_type',
        'show_estimated_price',
        'display_priority',
        'order_cutoff_time',
        'handling_time_hours',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'is_express_type'      => 'boolean',
        'show_estimated_price' => 'boolean',
        'min_delivery_days'    => 'integer',
        'max_delivery_days'    => 'integer',
        'display_priority'     => 'integer',
        'handling_time_hours'  => 'integer',
        'order_cutoff_time'    => 'string',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function shippingRates(): HasMany
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

    public function categoryShippingMethods(): HasMany
    {
        return $this->hasMany(CategoryShippingMethod::class);
    }
}
