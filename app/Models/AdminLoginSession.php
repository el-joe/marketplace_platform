<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginSession extends Model
{
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function impersonating(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'impersonating_id');
    }
}
