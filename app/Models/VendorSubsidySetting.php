<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSubsidySetting extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'vendor_id',
        'country_id',
        'admin_support',
        'vendor_share',
        'currency',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getStatusAttribute(): string
    {
        $today = today()->toDateString();
        $from = $this->effective_from?->toDateString();
        $until = $this->effective_until?->toDateString();

        if ($from > $today) {
            return 'future';
        }

        if ($until !== null && $until < $today) {
            return 'expired';
        }

        return 'active';
    }
}
