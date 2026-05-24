<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSaleSubmission extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'vendor_id',
        'vendor_listing_id',
        'status',
        'flash_price',
        'original_price',
        'calculated_discount_pct',
        'reference_price_30d',
        'max_quantity_total',
        'max_quantity_per_customer',
        'quantity_sold',
        'quantity_remaining',
        'flash_price_currency',
        'rejection_reason',
        'rejection_code',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'approved_at',
        'sold_out_at',
        'admin_notes',
        'vendor_notes',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FlashSaleSubmissionHistory::class, 'flash_sale_submission_id');
    }

    public function flashSaleOrders(): HasMany
    {
        return $this->hasMany(FlashSaleOrder::class);
    }
}
