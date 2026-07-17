<?php

namespace App\Services;

use App\Models\VendorListing;
use App\Models\WarehouseInventory;
use App\Models\WarehouseVendorLimit;
use Illuminate\Validation\ValidationException;

class WarehouseVendorLimitService
{
    public function limitFor(string $warehouseId, string $vendorId): ?WarehouseVendorLimit
    {
        return WarehouseVendorLimit::where('warehouse_id', $warehouseId)
            ->where('vendor_id', $vendorId)
            ->first();
    }

    /**
     * Sum of on-hand + already-inbound units this vendor holds in the warehouse.
     */
    public function currentQuantityUsage(string $warehouseId, string $vendorId): int
    {
        return (int) WarehouseInventory::query()
            ->join('vendor_listings', 'vendor_listings.id', '=', 'warehouse_inventories.vendor_listing_id')
            ->where('warehouse_inventories.warehouse_id', $warehouseId)
            ->where('vendor_listings.vendor_id', $vendorId)
            ->selectRaw('COALESCE(SUM(warehouse_inventories.quantity_on_hand + warehouse_inventories.quantity_inbound), 0) as total')
            ->value('total');
    }

    /**
     * Sum of volume (m3) occupied by on-hand + inbound units this vendor holds in the warehouse.
     */
    public function currentCapacityUsage(string $warehouseId, string $vendorId): float
    {
        $rows = WarehouseInventory::query()
            ->join('vendor_listings', 'vendor_listings.id', '=', 'warehouse_inventories.vendor_listing_id')
            ->join('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
            ->where('warehouse_inventories.warehouse_id', $warehouseId)
            ->where('vendor_listings.vendor_id', $vendorId)
            ->selectRaw('(warehouse_inventories.quantity_on_hand + warehouse_inventories.quantity_inbound) as units, product_variants.length_cm, product_variants.width_cm, product_variants.height_cm')
            ->get();

        return (float) $rows->sum(fn ($row) => $row->units * $this->unitVolumeM3($row->length_cm, $row->width_cm, $row->height_cm));
    }

    public function unitVolumeM3(?float $lengthCm, ?float $widthCm, ?float $heightCm): float
    {
        if (!$lengthCm || !$widthCm || !$heightCm) {
            return 0.0;
        }

        return ($lengthCm * $widthCm * $heightCm) / 1_000_000;
    }

    /**
     * Throws a ValidationException if adding $additionalQuantity units of $vendorListing
     * would push the vendor over their configured limit for this warehouse.
     */
    public function assertWithinLimit(string $warehouseId, VendorListing $vendorListing, int $additionalQuantity): void
    {
        $limit = $this->limitFor($warehouseId, $vendorListing->vendor_id);

        if (!$limit) {
            return;
        }

        if ($limit->isQuantityBased()) {
            $projected = $this->currentQuantityUsage($warehouseId, $vendorListing->vendor_id) + $additionalQuantity;

            if ($limit->max_quantity !== null && $projected > $limit->max_quantity) {
                throw ValidationException::withMessages([
                    'quantity_requested' => ["This would exceed your storage limit of {$limit->max_quantity} units in this warehouse (currently using " . ($projected - $additionalQuantity) . ')'],
                ]);
            }

            return;
        }

        $variant = $vendorListing->productVariant;
        $unitVolume = $this->unitVolumeM3($variant?->length_cm, $variant?->width_cm, $variant?->height_cm);
        $projectedVolume = $this->currentCapacityUsage($warehouseId, $vendorListing->vendor_id) + ($unitVolume * $additionalQuantity);

        if ($limit->max_capacity_m3 !== null && $projectedVolume > (float) $limit->max_capacity_m3) {
            throw ValidationException::withMessages([
                'quantity_requested' => ['This would exceed your storage capacity limit of ' . $limit->max_capacity_m3 . ' m³ in this warehouse.'],
            ]);
        }
    }
}
