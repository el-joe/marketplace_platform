<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'event_type',
        'payload',
        'received_from',
        'signature',
        'status',
        'attempts',
        'last_error',
        'processed_at',
    ];

    //
}
