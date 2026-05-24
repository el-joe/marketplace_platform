<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    public $timestamps = false;

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

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
