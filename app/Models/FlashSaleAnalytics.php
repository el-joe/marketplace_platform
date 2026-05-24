<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleAnalytics extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'flash_sale_submission_id',
        'vendor_id',
        'date',
        'units_sold',
        'currency',
        'gross_revenue',
        'revenue_at_normal_price',
        'discount_given',
        'platform_commission',
        'vendor_payout',
        'views',
        'add_to_cart_count',
        'conversion_rate',
    ];

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function flashSaleSubmission(): BelongsTo
    {
        return $this->belongsTo(FlashSaleSubmission::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
