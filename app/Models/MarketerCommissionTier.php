<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerCommissionTier extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'campaign_id',
        'tier_order',
        'min_sales_count',
        'max_sales_count',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier_order' => 'integer',
            'min_sales_count' => 'integer',
            'max_sales_count' => 'integer',
            'commission_rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    public function scopeForSalesCount(Builder $query, int $count): Builder
    {
        return $query
            ->where('min_sales_count', '<=', $count)
            ->where(
                fn(Builder $q) => $q
                    ->whereNull('max_sales_count')
                    ->orWhere('max_sales_count', '>=', $count)
            )
            ->orderByDesc('tier_order');
    }
}
