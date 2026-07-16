<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class VendorFbpSubsidySetting extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'admin_subsidy',
        'full_coverage_threshold',
        'exceptional_zone_shipping_zone_ids',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exceptional_zone_shipping_zone_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return Cache::remember('vendor_fbp_subsidy_settings:active', 3600, function () {
            return static::where('is_active', true)->latest('created_at')->first()
                ?? new self([
                    'admin_subsidy' => 0,
                    'full_coverage_threshold' => 0,
                    'exceptional_zone_shipping_zone_ids' => [],
                    'is_active' => true,
                ]);
        });
    }

    public static function flush(): void
    {
        Cache::forget('vendor_fbp_subsidy_settings:active');
    }
}
