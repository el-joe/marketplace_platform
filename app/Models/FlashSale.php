<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    use HasUuids, HasStateMachine;

    // ── Status constants ──────────────────────────────────────────────────────

    public const STATUS_TRANSITIONS = [
        'draft' => ['submission_open', 'cancelled'],
        'submission_open' => ['submission_closed', 'cancelled'],
        'submission_closed' => ['under_review', 'cancelled'],
        'under_review' => ['approved', 'cancelled'],
        'approved' => ['live', 'cancelled'],
        'live' => ['ended', 'cancelled'],
        'ended' => [],
        'cancelled' => [],
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'submission_open' => 'Accepting Submissions',
        'submission_closed' => 'Submissions Closed',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'live' => 'Live',
        'ended' => 'Ended',
        'cancelled' => 'Cancelled',
    ];

    // ── Fillable ──────────────────────────────────────────────────────────────
    // approved_slots_count intentionally omitted — managed by service only

    protected $fillable = [
        'country_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'status',
        'submission_opens_at',
        'submission_closes_at',
        'review_deadline_at',
        'sale_starts_at',
        'sale_ends_at',
        'min_discount_pct',
        'max_products_per_seller',
        'eligible_categories',
        'eligible_seller_tiers',
        'min_seller_rating',
        'commission_override_pct',
        'is_featured',
        'is_exclusive',
        'price_drop_required',
        'max_total_slots',
        'created_by_admin_id',
        'updated_by_admin_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'eligible_categories' => 'array',
            'eligible_seller_tiers' => 'array',
            'is_featured' => 'boolean',
            'is_exclusive' => 'boolean',
            'price_drop_required' => 'boolean',
            'submission_opens_at' => 'datetime',
            'submission_closes_at' => 'datetime',
            'review_deadline_at' => 'datetime',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'min_discount_pct' => 'decimal:2',
            'commission_override_pct' => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function vendorInvitations(): HasMany
    {
        return $this->hasMany(FlashSaleVendorInvitition::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FlashSaleSubmission::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(FlashSaleAnalytic::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query): void
    {
        $query->whereIn('status', ['submission_open', 'live', 'under_review', 'approved']);
    }

    public function scopeLive($query): void
    {
        $query->where('status', 'live');
    }

    public function scopeUpcoming($query): void
    {
        $query->whereIn('status', ['approved', 'submission_open']);
    }

    public function scopeEnded($query): void
    {
        $query->whereIn('status', ['ended', 'cancelled']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isAcceptingSubmissions(): bool
    {
        return $this->status === 'submission_open'
            && $this->submission_opens_at
            && $this->submission_closes_at
            && now()->between($this->submission_opens_at, $this->submission_closes_at);
    }

    public function canBeApproved(): bool
    {
        return $this->canTransitionTo('approved');
    }

    public function getDurationHours(): float
    {
        if (!$this->sale_starts_at || !$this->sale_ends_at) {
            return 0.0;
        }
        return round($this->sale_ends_at->diffInMinutes($this->sale_starts_at) / 60, 1);
    }

    public function getSubmissionCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    public function getApprovedCountAttribute(): int
    {
        return $this->submissions()->where('status', 'approved')->count();
    }
}
