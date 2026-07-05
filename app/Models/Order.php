<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot' => 'array',
            'placed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'id',
        'order_number',
        'customer_id',
        'country_id',
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
        'marketer_id',
        'marketer_campaign_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function marketerCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
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
