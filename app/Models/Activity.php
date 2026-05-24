<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $table = 'activity_log';

    /** The entity that was acted upon. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** The entity that caused the activity. */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
