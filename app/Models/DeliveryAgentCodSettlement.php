<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAgentCodSettlement extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_id',
        'period_start',
        'period_end',
        'total_cod_collected_cents',
        'total_earnings_owed_cents',
        'net_to_remit_cents',
        'status',
        'settled_at',
        'notes',
    ];

    protected $casts = [
        'period_start'               => 'date',
        'period_end'                 => 'date',
        'total_cod_collected_cents'  => 'integer',
        'total_earnings_owed_cents'  => 'integer',
        'net_to_remit_cents'         => 'integer',
        'settled_at'                 => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }
}
