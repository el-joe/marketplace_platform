<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'impersonating_user_id',
        'ip_address',
        'user_agent',
        'device_info',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'device_info' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function impersonatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonating_user_id');
    }
}
