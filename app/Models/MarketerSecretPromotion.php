<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerSecretPromotion extends Model
{
    use HasUuids;

    protected $fillable = [
        'vendor_id',
        'vendor_listing_id',
        'marketer_id',
        'product_value_cents',
        'total_commission_pct',
        'marketer_share_pct',
        'admin_share_pct',
        'min_commission_pct',
        'is_hidden_from_public',
        'status',
        'valid_until',
        'created_by_vendor',
        'approved_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'product_value_cents' => 'integer',
            'total_commission_pct' => 'decimal:2',
            'marketer_share_pct' => 'decimal:2',
            'admin_share_pct' => 'decimal:2',
            'min_commission_pct' => 'decimal:2',
            'is_hidden_from_public' => 'boolean',
            'created_by_vendor' => 'boolean',
            'valid_until' => 'date',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class, 'vendor_listing_id');
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until !== null && now()->toDateString() > $this->valid_until->toDateString();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
