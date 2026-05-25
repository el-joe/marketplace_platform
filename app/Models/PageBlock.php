<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageBlock extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'page_id',
        'section_id',
        'block_type',
        'position',
        'config',
        'is_visible',
        'visible_from',
        'visible_until',
        'device_target',
        'audience',
        'country_override',
        'ab_test_id',
        'ab_variant',
        'cache_ttl_seconds',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'config' => 'array',
        'is_visible' => 'boolean',
        'visible_from' => 'datetime',
        'visible_until' => 'datetime',
        'cache_ttl_seconds' => 'integer',
        'position' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'section_id');
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }

    public function countryOverride(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_override');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageBlockRevision::class)->orderByDesc('revision_number');
    }

    public function slides(): HasMany
    {
        return $this->hasMany(SliderSlide::class)->orderBy('position');
    }

    public function adImageItems(): HasMany
    {
        return $this->hasMany(AdImageItem::class)->orderBy('position');
    }

    public function blockProducts(): HasMany
    {
        return $this->hasMany(PageBlockProduct::class)->orderBy('position');
    }

    public function blockSellers(): HasMany
    {
        return $this->hasMany(PageBlockSeller::class)->orderBy('position');
    }

    public function blockCategories(): HasMany
    {
        return $this->hasMany(PageBlockCategory::class)->orderBy('position');
    }

    public function paidBannerBookings(): HasMany
    {
        return $this->hasMany(PaidBannerBooking::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(BlockAnalytic::class)->orderByDesc('date');
    }

    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class, 'block_type', 'code');
    }
}
