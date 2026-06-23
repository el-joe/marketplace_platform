<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidBannerBooking extends Model
{
    use HasUuids;

    protected $fillable = [
        'page_block_id',
        'seller_id',
        'brand_name',
        'booking_reference',
        'image_url',
        'link_url',
        'alt_text',
        'pricing_model',
        'rate_cents',
        'currency',
        'total_charged_cents',
        'booked_from',
        'booked_until',
        'status',
        'impressions_delivered',
        'clicks_delivered',
        'booked_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'rate_cents' => 'integer',
        'total_charged_cents' => 'integer',
        'impressions_delivered' => 'integer',
        'clicks_delivered' => 'integer',
        'booked_from' => 'date',
        'booked_until' => 'date',
    ];

    public function pageBlock(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function bookedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'booked_by_admin_id');
    }
}
