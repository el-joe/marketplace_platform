<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProductListing extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_variant_id',
        'country_id',
        'price_cents',
        'cost_price_cents',
        'commission_type',
        'commission_value',
        'currency',
        'payment_options',
        'fulfillment_type',
        'featured_in_nawy',
        'nawy_category_id',
        'shipping_cost_cents',
        'is_exclusive',
        'status',
        'available_for_vendors',
        'available_for_marketers',
        'created_by_admin_id',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'price_cents'        => 'integer',
        'cost_price_cents'   => 'integer',
        'shipping_cost_cents'=> 'integer',
        'commission_value'   => 'decimal:2',
        'is_exclusive'       => 'boolean',
        'featured_in_nawy'   => 'boolean',
        'available_for_vendors'   => 'boolean',
        'available_for_marketers' => 'boolean',
        'rating_avg'          => 'decimal:2',
        'rating_count'        => 'integer',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function nawyCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'nawy_category_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /** Price in major currency unit (for display). */
    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    /** Whether COD is an allowed payment method for this listing. */
    public function allowsCod(): bool
    {
        return in_array($this->payment_options, ['cod_only', 'both']);
    }

    /** Whether electronic payment is allowed for this listing. */
    public function allowsElectronic(): bool
    {
        return in_array($this->payment_options, ['electronic_only', 'both']);
    }
}
