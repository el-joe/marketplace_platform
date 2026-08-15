<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'city_ids',
        'polygon_coordinates',
        'base_delivery_fee',
        'cod_fee',
        'max_active_agents',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'city_ids' => 'array',
            'polygon_coordinates' => 'array',
            'is_active' => 'boolean',
            'base_delivery_fee' => 'integer',
            'cod_fee' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(DeliveryAgent::class, 'zone_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(DeliveryAgentShift::class, 'zone_id');
    }

}
