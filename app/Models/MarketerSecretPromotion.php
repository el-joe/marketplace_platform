<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketerCampaign::class, 'secret_promotion_id');
    }

    // secret_promotion_id lives on marketer_campaigns, not marketer_conversions.
    // Conversions are reached through the campaign.
    public function conversions(): HasManyThrough
    {
        return $this->hasManyThrough(
            MarketerConversion::class,
            MarketerCampaign::class,
            'secret_promotion_id', // FK on marketer_campaigns → marketer_secret_promotions.id
            'campaign_id',          // FK on marketer_conversions → marketer_campaigns.id
            'id',                   // local key on marketer_secret_promotions
            'id'                    // local key on marketer_campaigns
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active')
            ->where(fn($q2) => $q2->whereNull('valid_until')
                ->orWhere('valid_until', '>=', today()));
    }

    public function scopeExpired(Builder $q): Builder
    {
        return $q->where(fn($q2) => $q2->where('status', 'expired')
            ->orWhere('valid_until', '<', today()));
    }

    public function scopeForVendor(Builder $q, string $vendorId): Builder
    {
        return $q->where('vendor_id', $vendorId);
    }

    public function scopeForMarketer(Builder $q, string $marketerId): Builder
    {
        return $q->where(fn($q2) => $q2->where('marketer_id', $marketerId)
            ->orWhereNull('marketer_id'));
    }

    public function scopeOpenToAll(Builder $q): Builder
    {
        return $q->whereNull('marketer_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getProductValueFormattedAttribute(): string
    {
        return number_format($this->product_value_cents / 100, 2)
            . ' ' . ($this->vendorListing?->currency ?? '');
    }

    public function getListingPriceFormattedAttribute(): string
    {
        return number_format(($this->vendorListing?->price ?? 0) / 100, 2)
            . ' ' . ($this->vendorListing?->currency ?? '');
    }

    public function getMarginPctAttribute(): float
    {
        if (!$this->product_value_cents || !$this->vendorListing) {
            return 0.0;
        }
        $listingPrice = $this->vendorListing->price;
        $cost = $this->product_value_cents;

        return round((($listingPrice - $cost) / $listingPrice) * 100, 2);
    }

    public function getAdminEarningsFormatAttribute(): string
    {
        $perSale = ($this->vendorListing?->price ?? 0) * $this->admin_share_pct / 100;

        return number_format($perSale / 100, 2) . ' ' . ($this->vendorListing?->currency ?? '');
    }

    public function getMarketerEarningsFormatAttribute(): string
    {
        $perSale = ($this->vendorListing?->price ?? 0) * $this->marketer_share_pct / 100;

        return number_format($perSale / 100, 2);
    }

    public function getConversionCountAttribute(): int
    {
        return $this->conversions()->count();
    }

    public function getTotalRevenueGeneratedAttribute(): int
    {
        return (int) $this->conversions()
            ->where('status', '!=', 'reversed')
            ->sum('order_value_cents');
    }

    public function getTotalAdminEarnedAttribute(): int
    {
        return (int) $this->conversions()
            ->where('status', '!=', 'reversed')
            ->get()
            ->sum(fn($c) => (int) round($c->order_value_cents * $this->admin_share_pct / 100));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => $this->isExpired() ? 'gray' : 'success',
            'paused' => 'warning',
            'expired' => 'gray',
            default => 'gray',
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until < today();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
