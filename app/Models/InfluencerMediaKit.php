<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerMediaKit extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'headline',
        'audience_age_range',
        'audience_gender_split',
        'primary_audience_country',
        'avg_post_reach',
        'avg_story_views',
        'avg_video_views',
        'rate_per_post',
        'rate_per_story',
        'rate_per_video',
        'rate_currency',
        'portfolio_urls',
        'past_brands',
        'is_visible_to_vendors',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'portfolio_urls' => 'array',
            'past_brands' => 'array',
            'is_visible_to_vendors' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }
}
