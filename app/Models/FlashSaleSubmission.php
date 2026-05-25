<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSaleSubmission extends Model
{
    use HasUuids, HasStateMachine;

    // ── State machine ─────────────────────────────────────────────────────────

    public const STATUS_TRANSITIONS = [
        'submitted'    => ['under_review', 'approved', 'rejected'],
        'under_review' => ['approved', 'rejected'],
        'approved'     => ['active', 'rejected'],
        'active'       => ['sold_out', 'ended'],
        'sold_out'     => [],
        'rejected'     => [],
        'ended'        => [],
    ];

    public const STATUS_LABELS = [
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'approved'     => 'Approved',
        'active'       => 'Active',
        'sold_out'     => 'Sold Out',
        'rejected'     => 'Rejected',
        'ended'        => 'Ended',
    ];

    // ── Fillable ──────────────────────────────────────────────────────────────

    protected $fillable = [
        'flash_sale_id',
        'vendor_listing_id',
        'vendor_id',
        'product_variant_id',
        'status',
        'flash_price',
        'original_price',
        'calculated_discount_pct',
        'reference_price_30d',
        'max_quantity_total',
        'max_quantity_per_customer',
        'quantity_sold',
        'rejection_reason',
        'rejection_code',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'approved_at',
        'sold_out_at',
        'vendor_notes',
    ];

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'flash_price'              => 'integer',
            'original_price'           => 'integer',
            'reference_price_30d'      => 'integer',
            'calculated_discount_pct'  => 'decimal:2',
            'submitted_at'             => 'datetime',
            'reviewed_at'              => 'datetime',
            'approved_at'              => 'datetime',
            'sold_out_at'              => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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
        return $this->belongsTo(VendorListing::class, 'vendor_listing_id');
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
