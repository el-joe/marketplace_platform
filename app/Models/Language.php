<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    protected $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'native_name',
        'english_name',
        'direction',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
