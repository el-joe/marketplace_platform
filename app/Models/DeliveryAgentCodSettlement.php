<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'has_collection_discrepancy',
        'discrepancy_notes',
        'discrepancy_amount_cents',
        'discrepancy_resolution',
    ];

    protected $casts = [
        'period_start'               => 'date',
        'period_end'                 => 'date',
        'total_cod_collected_cents'  => 'integer',
        'total_earnings_owed_cents'  => 'integer',
        'net_to_remit_cents'         => 'integer',
        'discrepancy_amount_cents'   => 'integer',
        'has_collection_discrepancy' => 'boolean',
        'settled_at'                 => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class, 'cod_settlement_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(DeliveryAgentEarning::class, 'agent_id', 'agent_id')
            ->whereBetween('created_at', [$this->period_start, $this->period_end]);
    }
}
