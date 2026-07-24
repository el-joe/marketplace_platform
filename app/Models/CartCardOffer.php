<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartCardOffer extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id', 'card_name_en', 'card_name_ar',
        'bank_name_en', 'bank_name_ar', 'card_image_path',
        'cashback_type', 'cashback_pct', 'cashback_fixed_amount',
        'label_template_en', 'label_template_ar',
        'apply_url', 'apply_label_en', 'apply_label_ar',
        'min_order_amount', 'max_cashback_amount',
        'valid_from', 'valid_until', 'is_active', 'sort_order',
        'created_by_admin_id',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'cashback_pct'          => 'decimal:2',
        'cashback_fixed_amount' => 'integer',
        'min_order_amount'      => 'integer',
        'max_cashback_amount'   => 'integer',
        'sort_order'            => 'integer',
        'valid_from'            => 'datetime',
        'valid_until'           => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
