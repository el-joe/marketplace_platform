<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaidAdSlot extends Model
{
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
