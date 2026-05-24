<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'billing_address_snapshot' => 'array',
    ];

    protected $fillable = [
        'id',
        'order_number',
        'customer_id',
        'status',
        'currency',
        'subtotal',
        'discount',
        'shipping',
        'tax',
        'cod_fee',
        'total',
        'coupon_id',
        'coupon_code_used',
        'payment_method',
        'payment_status',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'customer_notes',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'risk_score',
        'placed_at',
        'completed_at',
        'cancelled_at',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
