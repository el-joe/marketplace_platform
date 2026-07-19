<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerDealDeliverable extends Model
{
    use HasUuids;

    protected $fillable = [
        'deal_id',
        'platform',
        'content_type',
        'description',
        'content_url',
        'content_notes',
        'due_at',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'approved_by_admin_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'due_at' => 'datetime',
            'status' => 'string',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function deal(): BelongsTo
    {
        return $this->belongsTo(InfluencerDeal::class, 'deal_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_at !== null && $this->due_at < now();
    }
}
