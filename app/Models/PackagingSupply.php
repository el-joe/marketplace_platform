<?php

namespace App\Models;

use App\Enums\PackagingSupplyType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingSupply extends Model
{
    use HasUuids;

    protected $fillable = [
        'name_en',
        'name_ar',
        'type',
        'size',
        'unit_cost_cents',
        'stock_available',
        'is_active',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost_cents'  => 'integer',
            'stock_available'  => 'integer',
            'is_active'        => 'boolean',
            'type'             => PackagingSupplyType::class,
        ];
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(PackagingSupplyRequestItem::class);
    }

    public function getUnitCostFormattedAttribute(): string
    {
        return $this->unit_cost_cents === 0
            ? 'Free'
            : number_format($this->unit_cost_cents / 100, 2);
    }

    public function isFree(): bool
    {
        return $this->unit_cost_cents === 0;
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            PackagingSupplyType::Box   => 'bg-orange-100 text-orange-800',
            PackagingSupplyType::Bag   => 'bg-blue-100 text-blue-800',
            PackagingSupplyType::Tape  => 'bg-yellow-100 text-yellow-800',
            PackagingSupplyType::Label => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
