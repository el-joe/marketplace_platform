<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAgentDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_id',
        'document_type',
        'file_path',
        'status',
        'verified_by_admin_id',
        'verified_at',
        'expires_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getLabelAttribute(): string
    {
        return match ($this->document_type) {
            'national_id' => 'National ID',
            'driving_license' => 'Driving License',
            'vehicle_registration' => 'Vehicle Registration',
            'insurance' => 'Insurance',
            'profile_photo' => 'Profile Photo',
            default => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }
}
