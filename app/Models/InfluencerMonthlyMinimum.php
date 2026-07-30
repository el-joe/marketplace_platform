<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerMonthlyMinimum extends Model
{
    use HasUuids;

    protected $table = 'influencer_monthly_minimums';

    protected $fillable = [
        'tier',
        'monthly_minimum',
        'penalty_per_unmet_promotion',
        'penalty_currency',
        'is_active',
        'set_by_admin_id',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'tier' => 'integer',
            'monthly_minimum' => 'integer',
            'penalty_per_unmet_promotion' => 'integer',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function setByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'set_by_admin_id');
    }
}
