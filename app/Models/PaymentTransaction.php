<?php

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    // UUID is supplied by the payment gateway — do NOT auto-generate.
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'raw_request' => 'array',
            'raw_response' => 'array',
            'processed_at' => 'datetime',
            'status' => PaymentTransactionStatus::class,
            'type' => PaymentTransactionType::class,
        ];
    }

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'type',
        'gateway',
        'gateway_transaction_id',
        'idempotency_key',
        'amount',
        'currency',
        'gateway_fee',
        'status',
        'failure_code',
        'failure_message',
        'payment_method_id',
        'raw_request',
        'raw_response',
        'processed_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'original_transaction_id');
    }
}
