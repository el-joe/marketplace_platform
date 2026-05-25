<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleAnalytics extends Model
{
    use HasUuids;

    protected $fillable = [
        'flash_sale_id',
        'flash_sale_submission_id',
        'vendor_id',
        'date',
        'units_sold',
        'gross_revenue',
        'discount_given',
        'platform_commission',
        'views',
        'conversion_rate',
    ];

    protected function casts(): array
    {
        return [
            'date'                => 'date',
            'gross_revenue'       => 'integer',
            'discount_given'      => 'integer',
            'platform_commission' => 'integer',
            'conversion_rate'     => 'decimal:4',
        ];
    }

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
