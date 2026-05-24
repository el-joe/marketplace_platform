<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Admin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'country_id',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function loginSessions(): HasMany
    {
        return $this->hasMany(AdminLoginSession::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'causer_id');
    }
}
