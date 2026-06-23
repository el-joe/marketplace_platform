<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'logo_media_id',
        'description_ar',
        'description_en',
        'website_url',
        'is_verified',
        'is_restricted',
        'is_active',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_restricted' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
