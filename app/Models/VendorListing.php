<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorListing extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'vendor_id',
        'product_variant_id',
        'country_id',
        'price',
        'compare_at_price',
        'cost_price',
        'currency',
        'condition',
        'condition_notes',
        'fulfillment_model',
        'vendor_sku',
        'vendor_notes',
        'status',
        'rejection_reason',
        'max_order_quantity',
        'low_stock_threshold',
        'buy_box_eligible',
        'buy_box_won_at',
        'total_sold',
        'rating_avg',
        'rating_count',
        'approved_by_admin_id',
        'approved_at',
        'global_system_type',
        'primary_shipping_method_id',
        'score',
        'price_score',
        'fulfillment_score',
        'rating_score',
        'availability_score',
        'calculated_at',
        'next_recalculate_at',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function warehouseInventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function flashSaleSubmissions(): HasMany
    {
        return $this->hasMany(FlashSaleSubmission::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function primaryShippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'primary_shipping_method_id');
    }
}
