<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardTransaction extends Model
{
    use HasUuids;

    public const UPDATED_AT = null; // append-only

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new \RuntimeException('GiftCardTransaction is append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('GiftCardTransaction is append-only and cannot be deleted.');
        });
    }

    protected $fillable = [
        'gift_card_id',
        'order_id',
        'amount_cents',
        'balance_after_cents',
        'type',
        'performed_by_customer_id',
        'performed_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'balance_after_cents' => 'integer',
    ];

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function performedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'performed_by_customer_id');
    }

    public function performedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'performed_by_admin_id');
    }
}
