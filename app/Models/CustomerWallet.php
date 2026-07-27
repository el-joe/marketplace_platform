<?php

namespace App\Models;

use App\Exceptions\InsufficientWalletBalanceException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerWallet extends Model
{
    use HasUuids;

    protected $table = 'customer_wallets';

    public const CREATED_AT = null;

    protected $fillable = [
        'customer_id',
        'balance',
        'currency_code',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'customer_id', 'customer_id');
    }

    public function credit(int $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function debit(int $amount): void
    {
        if ($this->balance < $amount) {
            throw new InsufficientWalletBalanceException();
        }

        $this->decrement('balance', $amount);
    }
}
