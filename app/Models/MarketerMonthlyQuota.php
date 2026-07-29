<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerMonthlyQuota extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'promotion_category',
        'min_promotions_per_month',
        'penalty_per_missing',
        'penalty_currency',
        'is_active',
        'set_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'promotion_category' => 'integer',
            'min_promotions_per_month' => 'integer',
            'penalty_per_missing' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function setByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'set_by_admin_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    public function scopeForCategory(Builder $query, int $category): Builder
    {
        return $query->where('promotion_category', $category);
    }

    public function scopeGlobalDefault(Builder $query): Builder
    {
        return $query->whereNull('marketer_id');
    }
}
