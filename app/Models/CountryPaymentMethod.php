<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryPaymentMethod extends Model
{
    protected $table = 'country_payment_methods';

    protected $keyType = 'string';
    protected $incrementing = false;

    protected $fillable = [
        'id',
        'country_id',
        'method_type',
        'provider',
        'display_name_en',
        'display_name_ar',
        'is_active',
        'fee_pct',
        'fee_fixed_cents',
        'min_order_cents',
        'max_order_cents',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_pct' => 'decimal:2',
        'fee_fixed_cents' => 'integer',
        'min_order_cents' => 'integer',
        'max_order_cents' => 'integer',
        'sort_order' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
