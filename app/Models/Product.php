<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name_en',
        'name_ar',
        'slug',
        'model_number',
        'gtin',
        'description_en',
        'description_ar',
        'short_desc_en',
        'short_desc_ar',
        'status',
        'is_featured',
        'requires_brand_auth',
        'is_age_restricted',
        'min_age',
        'is_hazardous',
        'has_variants',
        'ai_quality_score',
        'seller_count',
        'rating_avg',
        'rating_count',
        'total_sold',
        'view_count',
        'seo_title_en',
        'seo_title_ar',
        'seo_description_en',
        'seo_description_ar',
        'published_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'requires_brand_auth' => 'boolean',
        'is_age_restricted' => 'boolean',
        'is_hazardous' => 'boolean',
        'has_variants' => 'boolean',
        'min_age' => 'integer',
        'seller_count' => 'integer',
        'rating_count' => 'integer',
        'total_sold' => 'integer',
        'view_count' => 'integer',
        'rating_avg' => 'decimal:2',
        'ai_quality_score' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('is_primary', true)
            ->orderBy('position');
    }

    public function countrySettings(): HasMany
    {
        return $this->hasMany(ProductCountrySetting::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_products');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
