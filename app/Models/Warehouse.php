<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Warehouse extends Model
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function ownerVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'owner_vendor_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function managerAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'manager_admin_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function inboundShipments(): HasMany
    {
        return $this->hasMany(InboundShipment::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }
}
