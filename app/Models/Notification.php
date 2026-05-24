<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'channel',
        'read_at',
        'sent_at',
        'created_at',
    ];

    /** The notifiable entity (Admin, Customer, Vendor). */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
