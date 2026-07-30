<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminInfluencerOpenMarketProduct extends Model
{
    use HasUuids;

    public const CATEGORY_ADMIN_INTERMEDIARY = 3;
    public const CATEGORY_NAWY_NOW = 4;

    protected $fillable = [
        'vendor_listing_id',
        'admin_product_listing_id',
        'open_market_category',
        'promotion_tier',
        'celebrity_commission_amount',
        'commission_currency',
        'is_active',
        'added_by_admin_id',
    ];

    protected $casts = [
        'open_market_category' => 'integer',
        'promotion_tier' => 'integer',
        'celebrity_commission_amount' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function adminProductListing(): BelongsTo
    {
        return $this->belongsTo(AdminProductListing::class);
    }

    public function addedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'added_by_admin_id');
    }

    public function scopeCategory(Builder $query, int $category): Builder
    {
        return $query->where('open_market_category', $category);
    }

    public function scopeTier(Builder $query, int $tier): Builder
    {
        return $query->where('promotion_tier', $tier);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
