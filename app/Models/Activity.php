<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'batch_uuid',
        'ip_address',
        'user_agent',
        'created_at',
    ];

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
