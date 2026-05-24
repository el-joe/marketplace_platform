<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FlashSale extends Model
{
    protected $fillable = [
        'country_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'banner_media_id',
        'mobile_banner_media_id',
        'status',
        'submission_opens_at',
        'submission_closes_at',
        'review_deadline_at',
        'sale_starts_at',
        'sale_ends_at',
        'min_discount_pct',
        'max_products_per_seller',
        'eligible_categories',
        'eligible_seller_tiers',
        'min_seller_rating',
        'commission_override_pct',
        'is_featured',
        'is_exclusive',
        'price_drop_required',
        'max_total_slots',
        'approved_slots_count',
        'created_by_admin_id',
        'updated_by_admin_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function vendorInvitations(): HasMany
    {
        return $this->hasMany(FlashSaleVendorInvitition::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FlashSaleSubmission::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(FlashSaleAnalytics::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
