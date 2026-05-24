<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleOrder extends Model
{
    protected $fillable = [
        'flash_sale_submission_id',
        'flash_sale_id',
        'order_item_id',
        'quantity',
        'currency',
        'flash_price',
        'original_price',
        'discount_amount',
    ];

    public function flashSaleSubmission(): BelongsTo
    {
        return $this->belongsTo(FlashSaleSubmission::class);
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
