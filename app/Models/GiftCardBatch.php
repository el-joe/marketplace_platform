<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardBatch extends Model
{
    use HasUuids;

    protected $table = 'gift_card_batches';

    protected $fillable = [
        'name',
        'description',
        'amount',
        'currency_code',
        'quantity',
        'expires_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function scopeForCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency_code', $currency);
    }
}
