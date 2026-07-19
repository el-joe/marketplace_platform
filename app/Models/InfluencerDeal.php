<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerDeal extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'vendor_id',
        'campaign_id',
        'deal_name',
        'description',
        'deal_type',
        'flat_fee_amount',
        'currency',
        'hybrid_commission_rate',
        'status',
        'proposed_by',
        'negotiation_notes',
        'contract_signed_at',
        'content_due_at',
        'payment_due_at',
        'approved_by_admin_id',
        'paid_at',
        'payment_reference',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'flat_fee_amount' => 'integer',
            'status' => 'string',
            'deal_type' => 'string',
            'contract_signed_at' => 'datetime',
            'content_due_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class, 'campaign_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(InfluencerDealDeliverable::class, 'deal_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForMarketer(Builder $query, $marketerId): Builder
    {
        return $query->where('marketer_id', $marketerId);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'content_submitted');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['accepted', 'in_progress', 'content_submitted'], true);
    }
}
