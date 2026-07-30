<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelebrityOpenMarketPromotion extends Model
{
    use HasUuids;

    protected $table = 'celebrity_open_market_promotions';

    protected $fillable = [
        'promoter_marketer_id',
        'celebrity_store_product_id',
        'owner_marketer_id',
        'month',
        'year',
        'commission_earned',
        'currency_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_earned' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'promoter_marketer_id');
    }

    public function storeProduct(): BelongsTo
    {
        return $this->belongsTo(CelebrityStoreProduct::class, 'celebrity_store_product_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'owner_marketer_id');
    }
}
