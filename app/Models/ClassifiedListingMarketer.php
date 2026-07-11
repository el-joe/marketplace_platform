<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassifiedListingMarketer extends Model
{
    use HasUuids;

    protected $fillable = [
        'classified_listing_id', 'marketer_id', 'vendor_id',
        'commission_type', 'commission_value', 'status',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'status' => \App\Enums\ClassifiedListingMarketerStatus::class,
        'commission_type' => \App\Enums\ClassifiedListingMarketerCommissionType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $marketer) {
            $listing = ClassifiedListing::find($marketer->classified_listing_id);
            $marketer->vendor_id = ($listing && $listing->seller_type === Vendor::class)
                ? $listing->seller_id
                : null;
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ClassifiedListing::class, 'classified_listing_id');
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
