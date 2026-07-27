<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $table = 'wallet_transactions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'customer_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'currency_code',
        'reference_type',
        'reference_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new RuntimeException('WalletTransaction records are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('WalletTransaction records are append-only and cannot be deleted.');
        });
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('WalletTransaction records are append-only and cannot be updated.');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCredits($query)
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('direction', 'debit');
    }

    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}
