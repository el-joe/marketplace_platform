<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubOrder extends Model
{
    protected $keyType = 'string';
    protected $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'sub_order_number',
        'vendor_id',
        'warehouse_id',
        'status',
        'fulfillment_model',
        'subtotal',
        'shipping',
        'tax',
        'platform_commission',
        'vendor_payout',
        'shipping_method_id',
        'carrier_id',
        'tracking_number',
        'estimated_delivery_date',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'sla_ship_deadline',
        'sla_breached',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function payoutItems(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
