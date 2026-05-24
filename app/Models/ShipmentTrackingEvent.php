<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTrackingEvent extends Model
{
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
