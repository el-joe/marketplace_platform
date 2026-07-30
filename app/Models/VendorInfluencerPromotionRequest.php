<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorInfluencerPromotionRequest extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'vendor_listing_id',
        'admin_product_listing_id',
        'status',
        'num_celebrities_requested',
        'listing_fulfillment_model',
        'requires_warehouse_receipt',
        'warehouse_receipt_confirmed',
        'confirmed_by_admin_id',
        'total_promotion_fee',
        'fixed_admin_commission_snapshot',
        'admin_sample_debt',
        'admin_sample_debt_settled',
        'promotion_fee_per_celebrity_snapshot',
        'currency',
        'fee_deduction_timing',
        'fee_deducted',
        'fee_deducted_at',
        'vendor_note',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'requires_warehouse_receipt' => 'boolean',
            'warehouse_receipt_confirmed' => 'boolean',
            'num_celebrities_requested' => 'integer',
            'total_promotion_fee' => 'integer',
            'fixed_admin_commission_snapshot' => 'integer',
            'admin_sample_debt' => 'integer',
            'admin_sample_debt_settled' => 'boolean',
            'promotion_fee_per_celebrity_snapshot' => 'integer',
            'fee_deducted' => 'boolean',
            'fee_deducted_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function adminProductListing(): BelongsTo
    {
        return $this->belongsTo(AdminProductListing::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorInfluencerPromotionRequestItem::class, 'promotion_request_id');
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'confirmed_by_admin_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
