<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePromoCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'campaign_id',
        'code',
        'discount_type',
        'discount_value',
        'currency',
        'max_uses',
        'min_order_amount',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'discount_value' => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class, 'campaign_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(MarketerConversion::class, 'affiliate_promo_code_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('valid_until', '>=', now());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until !== null && now() > $this->valid_until;
    }

    public function hasReachedLimit(): bool
    {
        return $this->max_uses !== null && $this->times_used >= $this->max_uses;
    }
}
