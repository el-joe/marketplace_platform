<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionCountrySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'admin_commission',
        'fee_per_celebrity',
    ];

    protected function casts(): array
    {
        return [
            'admin_commission' => 'integer',
            'fee_per_celebrity' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function forCountry(string $countryId): ?self
    {
        return static::where('country_id', $countryId)->first();
    }

    public static function adminCommission(string $countryId): int
    {
        return static::forCountry($countryId)?->admin_commission ?? 2;
    }

    public static function feePerCelebrity(string $countryId): int
    {
        return static::forCountry($countryId)?->fee_per_celebrity ?? 9;
    }
}
