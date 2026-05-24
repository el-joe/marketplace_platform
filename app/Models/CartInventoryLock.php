<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartInventoryLock extends Model
{
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function warehouseInventory(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventory::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }
}
