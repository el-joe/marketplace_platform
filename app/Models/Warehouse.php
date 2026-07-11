<?php

namespace App\Models;

use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'type',
        'owner_vendor_id',
        'latitude',
        'longitude',
        'total_capacity_m3',
        'used_capacity_m3',
        'storage_rate_per_m3_price',
        'storage_currency',
        'manager_admin_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'total_capacity_m3' => 'decimal:2',
        'used_capacity_m3' => 'decimal:2',
        'type' => WarehouseType::class,
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function ownerVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'owner_vendor_id');
    }

    public function managerAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'manager_admin_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function warehouseInventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function outboundTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'source_warehouse_id');
    }

    public function inboundTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'destination_warehouse_id');
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getStorageRateFormattedAttribute(): ?string
    {
        return $this->storage_rate_per_m3_price
            ? number_format($this->storage_rate_per_m3_price / 100, 2) . ' ' . $this->storage_currency
            : null;
    }

    public function getCapacityUsedPercentAttribute(): float
    {
        if (!$this->total_capacity_m3 || $this->total_capacity_m3 == 0) {
            return 0;
        }

        return round(($this->used_capacity_m3 / $this->total_capacity_m3) * 100, 1);
    }

    public function getTotalSkusAttribute(): int
    {
        return $this->warehouseInventories()->count();
    }

    public function getTotalUnitsAttribute(): int
    {
        return $this->warehouseInventories()->sum('quantity_on_hand');
    }

    public function getLowStockCountAttribute(): int
    {
        return $this->warehouseInventories()
            ->whereNotNull('reorder_point')
            ->whereRaw('quantity_on_hand <= reorder_point')
            ->count();
    }
}

