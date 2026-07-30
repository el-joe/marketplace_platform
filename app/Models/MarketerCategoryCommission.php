<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerCategoryCommission extends Model
{
    use HasUuids;

    protected $table = 'marketer_category_commissions';

    protected $fillable = [
        'marketer_id',
        'category_id',
        'commission_amount',
        'currency_code',
        'is_active',
        'set_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function setByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'set_by_admin_id');
    }
}
