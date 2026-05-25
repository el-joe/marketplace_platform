<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\File;

class SliderSlide extends Model
{
    use HasUuids;

    protected $fillable = [
        'page_block_id',
        'position',
        'desktop_file_id',
        'mobile_file_id',
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'cta_label_en',
        'cta_label_ar',
        'cta_url',
        'cta_open_new_tab',
        'text_color',
        'text_position',
        'overlay_opacity',
        'link_type',
        'link_reference_id',
        'is_active',
        'visible_from',
        'visible_until',
    ];

    protected $casts = [
        'cta_open_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'overlay_opacity' => 'decimal:2',
        'position' => 'integer',
        'visible_from' => 'datetime',
        'visible_until' => 'datetime',
    ];

    public function pageBlock(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class);
    }

    public function desktopFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'desktop_file_id');
    }

    public function mobileFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'mobile_file_id');
    }
}
