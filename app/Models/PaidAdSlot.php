<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidAdSlot extends Model
{
    protected $fillable = [
        'placement_definition_id',
        'country_id',
        'name',
        'slot_code',
        'pricing_model',
        'base_rate_cents',
        'currency',
        'min_booking_days',
        'max_booking_days',
        'is_available',
        'requires_approval',
        'min_seller_tier',
        'notes_for_vendors',
        'created_by_admin_id',
    ];

    public function placementDefinition(): BelongsTo
    {
        return $this->belongsTo(BannerPlacementDefinition::class, 'placement_definition_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PaidAdBooking::class, 'paid_ad_slot_id');
    }
}
