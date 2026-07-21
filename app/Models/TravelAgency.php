<?php

namespace App\Models;

use App\Enums\TravelAgencyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class TravelAgency extends Authenticatable implements JWTSubject
{
    use HasUuids, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'license_number',
        'country_id',
        'logo_path',
        'status',
        'approved_by_admin_id',
        'approved_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'password'    => 'hashed',
            'status'      => TravelAgencyStatus::class,
        ];
    }

    // ── JWTSubject ────────────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['guard' => 'travel_agencies'];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(TravelPackage::class);
    }

    public function travelAgencyMembers(): HasMany
    {
        return $this->hasMany(TravelAgencyMember::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(TravelAgencyBankAccount::class);
    }

    public function sectionLocks(): HasMany
    {
        return $this->hasMany(TravelAgencySectionLock::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(TravelAgencyChangeRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === TravelAgencyStatus::Active;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }
}
