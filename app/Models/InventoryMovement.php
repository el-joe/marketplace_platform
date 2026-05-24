<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'warehouse_inventory_id',
        'movement_type',
        'quantity_delta',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'created_by_user_id',
    ];

    public function warehouseInventory(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_user_id');
    }
}
