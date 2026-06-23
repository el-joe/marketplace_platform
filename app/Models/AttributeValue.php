<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'attribute_id',
        'value_ar',
        'value_en',
        'color_hex',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function productVariantAttributes(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class, 'attribute_value_id');
    }
}
