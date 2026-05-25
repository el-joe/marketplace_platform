<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    use HasUuids;

    public static function boot(): void
    {
        parent::boot();
        static::updating(static fn () => throw new \Exception('Ledger entries cannot be modified.'));
        static::deleting(static fn () => throw new \Exception('Ledger entries cannot be deleted.'));
    }

    protected $fillable = [
        'transaction_group_id',
        'account_type',
        'account_holder_type',
        'account_holder_id',
        'debit',
        'credit',
        'currency',
        'reference_type',
        'reference_id',
        'description',
    ];

    /**
     * Polymorphic owner (Vendor or Customer).
     * Uses manual columns: account_holder_type / account_holder_id.
     */
    public function accountHolder(): MorphTo
    {
        return $this->morphTo('account_holder');
    }
}
