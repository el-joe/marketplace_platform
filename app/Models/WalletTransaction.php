<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount_cents',
        'balance_after_cents',
        'source_type',
        'source_id',
        'description',
        'performed_by_admin_id',
        'created_at',
    ];

    protected $casts = [
        'type'                 => WalletTransactionType::class,
        'amount_cents'         => 'integer',
        'balance_after_cents'  => 'integer',
        'created_at'           => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Append-only: prevent updates and deletes
        static::updating(fn() => false);
        static::deleting(fn() => false);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'performed_by_admin_id');
    }
}
