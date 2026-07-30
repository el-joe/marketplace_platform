<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerMonthlyStat extends Model
{
    use HasUuids;

    protected $table = 'influencer_monthly_stats';

    protected $fillable = [
        'marketer_id',
        'tier',
        'month',
        'year',
        'promotions_completed',
        'monthly_minimum_snapshot',
        'below_minimum_alert_sent',
        'below_minimum_alert_sent_at',
        'penalty_applied',
        'penalty_amount',
        'penalty_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'penalty_amount' => 'integer',
            'below_minimum_alert_sent' => 'boolean',
            'penalty_applied' => 'boolean',
            'below_minimum_alert_sent_at' => 'datetime',
            'penalty_applied_at' => 'datetime',
        ];
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getProgressPercentageAttribute(): float
    {
        return $this->monthly_minimum_snapshot > 0
            ? round(($this->promotions_completed / $this->monthly_minimum_snapshot) * 100, 1)
            : 0.0;
    }

    public function getIsBelowMinimumAttribute(): bool
    {
        return $this->promotions_completed < $this->monthly_minimum_snapshot;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }
}
