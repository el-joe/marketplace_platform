<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasUuids;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance_cents',
        'pending_balance_cents',
        'currency',
        'is_frozen',
        'frozen_reason',
    ];

    protected $casts = [
        'balance_cents'         => 'integer',
        'pending_balance_cents' => 'integer',
        'is_frozen'             => 'boolean',
    ];

    public function getOwnerAttribute(): Model|null
    {
        return match ($this->owner_type) {
            'customer'       => Customer::find($this->owner_id),
            'vendor'         => Vendor::find($this->owner_id),
            'marketer'       => Marketer::find($this->owner_id),
            'delivery_agent' => DeliveryAgent::find($this->owner_id),
            default          => null,
        };
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderByDesc('created_at');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WalletWithdrawalRequest::class)->orderByDesc('created_at');
    }

    public function getBalanceAttribute(): float
    {
        return $this->balance_cents / 100;
    }

    public function getPendingBalanceAttribute(): float
    {
        return $this->pending_balance_cents / 100;
    }
}
