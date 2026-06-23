<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_name',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'is_default',
        'is_active',
        'position',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attributes',
            'product_variant_id',
            'attribute_value_id'
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->orderBy('position');
    }

    public function vendorListings(): HasMany
    {
        return $this->hasMany(VendorListing::class);
    }
}
