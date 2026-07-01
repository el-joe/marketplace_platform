<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait, SoftDeletes;

    // Override kalnoy/nestedset defaults (_lft/_rgt) to match actual DB columns
    public function getLftName(): string
    {
        return 'lft';
    }
    public function getRgtName(): string
    {
        return 'rgt';
    }
    public function getDepthName(): string
    {
        return 'depth';
    }

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'parent_id',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'commission_rate',
        'commission_fbp_pct',
        'commission_fbp_fixed_cents',
        'commission_fbn_pct',
        'commission_fbn_fixed_cents',
        'sort_order',
        'product_count',
        'is_active',
        'is_visible',
        'is_featured',
        'marketer_sample_quota',
        'admin_sample_quota',
        'seo_title_ar',
        'seo_title_en',
        'seo_description_ar',
        'seo_description_en',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_fbp_pct' => 'decimal:2',
        'commission_fbp_fixed_cents' => 'integer',
        'commission_fbn_pct' => 'decimal:2',
        'commission_fbn_fixed_cents' => 'integer',
        'sort_order' => 'integer',
        'product_count' => 'integer',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'marketer_sample_quota' => 'integer',
        'admin_sample_quota' => 'integer',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{'name_' . $locale} ?? $this->name_en ?? '';
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children')->orderBy('sort_order');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes')
            ->withPivot(['is_required', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function categoryAttributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'category_shipping_methods')
            ->withPivot(['is_default', 'is_available_for_express_fbn', 'is_available_for_merchant_fbp']);
    }

    public function defaultShippingMethod(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'category_shipping_methods')
            ->withPivot(['is_default'])
            ->wherePivot('is_default', true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function breadcrumbPath(): array
    {
        $path = [];
        foreach ($this->ancestors()->get() as $ancestor) {
            $path[] = ['id' => $ancestor->id, 'name' => $ancestor->name_en];
        }
        $path[] = ['id' => $this->id, 'name' => $this->name_en];
        return $path;
    }
}
