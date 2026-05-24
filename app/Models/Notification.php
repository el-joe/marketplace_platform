<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    /** The notifiable entity (Admin, Customer, Vendor). */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
