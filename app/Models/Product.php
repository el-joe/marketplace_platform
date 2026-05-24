<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Product extends Model
{
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
        return $this->hasMany(ProductVariant::class);
    }

    public function countrySettings(): HasMany
    {
        return $this->hasMany(ProductCountry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_products');
    }

    /** Primary image (is_primary = true, lowest position) */
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(File::class, 'model')
            ->where('is_primary', true)
            ->orderBy('position');
    }

    public function vendorListings(): HasManyThrough
    {
        return $this->hasManyThrough(VendorListing::class, ProductVariant::class, 'product_id', 'product_variant_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
