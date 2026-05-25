<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\File;

class AdImageItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'page_block_id',
        'position',
        'file_id',
        'title_en',
        'title_ar',
        'link_url',
        'link_open_new_tab',
        'alt_text_en',
        'alt_text_ar',
        'show_title_overlay',
        'aspect_ratio',
        'is_active',
    ];

    protected $casts = [
        'link_open_new_tab' => 'boolean',
        'show_title_overlay' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function pageBlock(): BelongsTo
    {
        return $this->belongsTo(PageBlock::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
