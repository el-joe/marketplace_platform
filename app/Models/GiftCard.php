<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use HasUuids;

    protected $table = 'gift_cards';

    protected $fillable = [
        'gift_card_batch_id',
        'code',
        'pin_hash',
        'amount',
        'currency_code',
        'status',
        'redeemed_by_customer_id',
        'redeemed_at',
        'expires_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'amount' => 'integer',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GiftCardBatch::class, 'gift_card_batch_id');
    }

    public function redeemedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'redeemed_by_customer_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getIsRedeemableAttribute(): bool
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now())->where('status', '!=', 'expired');
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(16));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
