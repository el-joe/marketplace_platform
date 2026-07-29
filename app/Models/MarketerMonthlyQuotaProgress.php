<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerMonthlyQuotaProgress extends Model
{
    use HasUuids;

    protected $table = 'marketer_monthly_quota_progress';

    protected $fillable = [
        'marketer_id',
        'promotion_category',
        'year',
        'month',
        'quota_target',
        'completed_count',
        'status',
        'warning_sent',
        'warning_sent_at',
        'penalty_applied',
        'penalty_amount',
        'penalty_applied_at',
    ];

    protected function casts(): array
    {
        return [
            'promotion_category' => 'integer',
            'year' => 'integer',
            'month' => 'integer',
            'quota_target' => 'integer',
            'completed_count' => 'integer',
            'warning_sent' => 'boolean',
            'warning_sent_at' => 'datetime',
            'penalty_applied' => 'boolean',
            'penalty_amount' => 'integer',
            'penalty_applied_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForCategory(Builder $query, int $category): Builder
    {
        return $query->where('promotion_category', $category);
    }
}
