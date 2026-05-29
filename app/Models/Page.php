<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'country_id',
        'page_type',
        'reference_id',
        'name',
        'slug',
        'status',
        'publish_at',
        'published_at',
        'unpublish_at',
        'published_by_admin_id',
        'last_edited_by_admin_id',
        'version',
        'is_default',
        'seo_title',
        'seo_description',
        'og_image_url',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'version' => 'integer',
        'publish_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublish_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function publishedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }

    public function lastEditedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'last_edited_by_admin_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('position');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('position');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('version');
    }

    public function abTests(): HasMany
    {
        return $this->hasMany(AbTest::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'published' => 'success',
            'scheduled' => 'warning',
            'archived' => 'danger',
            default => 'gray',
        };
    }
}
