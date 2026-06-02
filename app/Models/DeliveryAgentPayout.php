<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAgentPayout extends Model
{
    use HasUuids;

    protected $fillable = [
        'payout_number',
        'agent_id',
        'period_start',
        'period_end',
        'total_deliveries',
        'gross_earnings_cents',
        'deductions_cents',
        'net_amount_cents',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'approved_by_admin_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_earnings_cents' => 'integer',
            'deductions_cents' => 'integer',
            'net_amount_cents' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getNetAmountAttribute(): float
    {
        return $this->net_amount_cents / 100;
    }

    public function getGrossEarningsAttribute(): float
    {
        return $this->gross_earnings_cents / 100;
    }
}
