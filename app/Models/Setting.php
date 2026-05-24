<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    public $timestamps = false;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'key',
        'value',
        'category',
        'description',
        'is_encrypted',
        'is_public',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'value' => 'array',
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_admin_id');
    }
}
