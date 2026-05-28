<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DeliveryAgent extends Authenticatable
{
    use HasUuids, SoftDeletes, Notifiable, HasApiTokens;

    protected string $guard = 'delivery';

    protected $fillable = [
        'country_id',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'agent_type',
        'vehicle_type',
        'current_latitude',
        'current_longitude',
        'last_location_at',
        'is_available',
        'rating_avg',
        'total_deliveries',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_available' => 'boolean',
            'last_location_at' => 'datetime',
            'last_login_at' => 'datetime',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
            'rating_avg' => 'float',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class, 'agent_id');
    }

    public function locationHistory(): HasMany
    {
        return $this->hasMany(AgentLocationHistory::class, 'agent_id');
    }

    public function deviceTokens(): MorphMany
    {
        return $this->morphMany(DeviceToken::class, 'tokenable');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
