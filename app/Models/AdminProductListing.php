<?php

namespace App\Models;

use App\Enums\AdminProductListingCommissionType;
use App\Enums\AdminProductListingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminProductListing extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'country_id',
        'price',
        'compare_at_price',
        'cost_price',
        'platform_sku',
        'condition',
        'condition_notes',
        'commission_type',
        'commission_value',
        'currency',
        'payment_options',
        'fulfillment_type',
        'featured_in_nawy',
        'nawy_category_id',
        'shipping_cost',
        'primary_shipping_method_id',
        'is_global_shipping',
        'is_exclusive',
        'status',
        'available_for_vendors',
        'available_for_marketers',
        'created_by_admin_id',
        'max_order_quantity',
        'low_stock_threshold',
        'total_sold',
        'buy_box_eligible',
        'buy_box_won_at',
        'rating_avg',
        'rating_count',
        'score',
        'price_score',
        'fulfillment_score',
        'rating_score',
        'availability_score',
        'calculated_at',
        'next_recalculate_at',
        'weight_class',
        'handling_class',
        'declared_weight_grams',
        'declared_length_cm',
        'declared_width_cm',
        'declared_height_cm',
        'influencer_commission_percentage',
        'affiliate_commission_percentage',
        'influencer_sample_quota',
        'affiliate_sample_quota',
    ];

    /** @var int Base currency unit (BIGINT) for money fields renamed in this model */
    protected $casts = [
        'price'        => 'integer',
        'compare_at_price' => 'integer',
        'cost_price'   => 'integer',
        'shipping_cost'=> 'integer',
        'commission_value'   => 'decimal:2',
        'is_exclusive'       => 'boolean',
        'is_global_shipping' => 'boolean',
        'featured_in_nawy'   => 'boolean',
        'available_for_vendors'   => 'boolean',
        'available_for_marketers' => 'boolean',
        'rating_avg'          => 'decimal:2',
        'rating_count'        => 'integer',
        'status'              => AdminProductListingStatus::class,
        'commission_type'     => AdminProductListingCommissionType::class,
        'buy_box_eligible'    => 'boolean',
        'buy_box_won_at'      => 'datetime',
        'score'               => 'decimal:4',
        'price_score'         => 'decimal:4',
        'fulfillment_score'   => 'decimal:4',
        'rating_score'        => 'decimal:4',
        'availability_score'  => 'decimal:4',
        'calculated_at'       => 'datetime',
        'next_recalculate_at' => 'datetime',
        'declared_length_cm'  => 'decimal:2',
        'declared_width_cm'   => 'decimal:2',
        'declared_height_cm'  => 'decimal:2',
        'influencer_commission_percentage' => 'decimal:2',
        'affiliate_commission_percentage'  => 'decimal:2',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function nawyCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'nawy_category_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function primaryShippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'primary_shipping_method_id');
    }

    public function warehouseInventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function marketplaceShippingRule(): HasOne
    {
        return $this->hasOne(MarketplaceShippingRule::class);
    }

    public function flashSaleSubmissions(): HasMany
    {
        return $this->hasMany(FlashSaleSubmission::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function marketerCampaignProducts(): HasMany
    {
        return $this->hasMany(MarketerCampaignProduct::class);
    }

    public function marketerSecretPromotions(): HasMany
    {
        return $this->hasMany(MarketerSecretPromotion::class);
    }

    public function productCostReferences(): HasMany
    {
        return $this->hasMany(ProductCostReference::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForCountry(Builder $query, string $countryId): Builder
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeFeaturedInNawy(Builder $query): Builder
    {
        return $query->where('featured_in_nawy', true);
    }

    /** Whether COD is an allowed payment method for this listing. */
    public function allowsCod(): bool
    {
        return in_array($this->payment_options, ['cod_only', 'both']);
    }

    /** Whether electronic payment is allowed for this listing. */
    public function allowsElectronic(): bool
    {
        return in_array($this->payment_options, ['electronic_only', 'both']);
    }
}
