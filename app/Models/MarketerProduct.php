<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketerProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'marketer_id',
        'category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'currency',
        'platform_commission_rate',
        'stock_quantity',
        'sku',
        'status',
        'rejection_reason',
        'image_path',
        'weight_grams',
        'is_digital',
    ];

    protected $casts = [
        'price' => 'integer',
        'platform_commission_rate' => 'integer',
        'stock_quantity' => 'integer',
        'weight_grams' => 'integer',
        'is_digital' => 'boolean',
    ];

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Splits a sale of this product between the platform and the marketer.
     * Inverted from vendor commission: the marketer declared this rate themselves
     * (must be >= admin minimum at creation time), so the platform's cut comes
     * OUT of the marketer's revenue rather than being added on top.
     */
    public function calculateCommission(): array
    {
        $platformCut = intdiv($this->price * $this->platform_commission_rate, 10000);

        return [
            'platform_cut' => $platformCut,
            'marketer_receives' => $this->price - $platformCut,
        ];
    }
}
