<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MarketerCampaign extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'vendor_id',
        'name',
        'description',
        'campaign_type',
        'status',
        'commission_rate',
        'commission_type',
        'budget_cents',
        'budget_spent_cents',
        'starts_at',
        'ends_at',
        'tracking_url_slug',
        'total_clicks',
        'total_conversions',
        'total_revenue_cents',
        'auto_approve_at',
        'auto_approved',
        'attribution_model',
        'whatsapp_sharing_enabled',
        'samples_required',
        'secret_promotion_id',
        'approved_by_admin_id',
        'approved_at',
        'campaignable_type',
        'campaignable_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'auto_approve_at' => 'datetime',
            'commission_rate' => 'decimal:2',
            'budget_cents' => 'integer',
            'budget_spent_cents' => 'integer',
            'total_clicks' => 'integer',
            'total_conversions' => 'integer',
            'total_revenue_cents' => 'integer',
            'auto_approved' => 'boolean',
            'whatsapp_sharing_enabled' => 'boolean',
            'samples_required' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $campaign): void {
            if (empty($campaign->tracking_url_slug)) {
                do {
                    $slug = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10));
                } while (self::where('tracking_url_slug', $slug)->exists());
                $campaign->tracking_url_slug = $slug;
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function campaignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketerCampaignProduct::class, 'campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(MarketerClick::class, 'campaign_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(MarketerConversion::class, 'campaign_id');
    }

    public function whatsappLinks(): HasMany
    {
        return $this->hasMany(MarketerWhatsappLink::class, 'campaign_id');
    }

    public function secretPromotion(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MarketerSecretPromotion::class, 'secret_promotion_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForVendors(Builder $query): Builder
    {
        return $query->where('campaignable_type', Vendor::class);
    }

    public function scopeForClassifieds(Builder $query): Builder
    {
        return $query->where('campaignable_type', ClassifiedListing::class);
    }

    public function scopeForTravelPackages(Builder $query): Builder
    {
        return $query->where('campaignable_type', TravelPackage::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->ends_at !== null && now() > $this->ends_at;
    }

    public function getBudgetRemainingCents(): ?int
    {
        if ($this->budget_cents === null) {
            return null;
        }
        return max(0, $this->budget_cents - ($this->budget_spent_cents ?? 0));
    }

    public function getConversionRate(): float
    {
        return $this->total_clicks > 0
            ? round(($this->total_conversions / $this->total_clicks) * 100, 2)
            : 0.0;
    }

    public function shouldAutoApprove(): bool
    {
        return $this->auto_approve_at !== null
            && now() >= $this->auto_approve_at
            && $this->status === 'draft';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'draft' => 'secondary',
            'paused' => 'warning',
            'ended' => 'primary',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
