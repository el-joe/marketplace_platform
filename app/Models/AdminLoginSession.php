<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginSession extends Model
{
    protected $fillable = [
        'admin_id',
        'impersonating_id',
        'ip_address',
        'user_agent',
        'device_info',
        'started_at',
        'ended_at',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function impersonating(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'impersonating_id');
    }
}
