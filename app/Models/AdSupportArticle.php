<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class AdSupportArticle extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'ad_support_collection_id',
        'author_admin_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'published_at',
        'is_featured',
        'related_article_ids',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'related_article_ids' => 'array',
        'views_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(AdSupportCollection::class, 'ad_support_collection_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_admin_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    /**
     * Table of contents parsed from <h1 id="..."> / <h2 id="..."> headings in the rendered body.
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function getTableOfContentsAttribute(): array
    {
        if (!preg_match_all('/<h[12][^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h[12]>/is', (string) $this->body, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(fn (array $m) => [
            'id' => $m[1],
            'label' => trim(strip_tags($m[2])),
        ], $matches);
    }

    public function resolvedRelatedArticles(): Collection
    {
        if (empty($this->related_article_ids)) {
            return collect();
        }

        return static::whereIn('id', $this->related_article_ids)
            ->published()
            ->get();
    }

    public function updatedLabel(): string
    {
        return ($this->published_at ?? $this->updated_at)->format('F j, Y');
    }
}
