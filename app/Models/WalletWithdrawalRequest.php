<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletWithdrawalRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'amount_cents',
        'currency',
        'bank_name',
        'bank_iban',
        'status',
        'approved_by_admin_id',
        'processed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
