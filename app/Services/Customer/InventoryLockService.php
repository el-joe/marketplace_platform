<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\CartInventoryLock;
use App\Models\InventoryMovement;
use App\Models\VendorListing;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\DB;

class InventoryLockService
{
    private const LOCK_TTL_MINUTES = 15;

    /**
     * Lock inventory for a single vendor listing in a cart.
     * Uses SELECT FOR UPDATE to prevent race conditions.
     * Always lock in ascending warehouse_inventory.id order when calling
     * lock() for multiple items — caller (CheckoutService) must sort.
     */
    public function lock(Cart $cart, VendorListing $listing, int $qty, string $customerId): CartInventoryLock
    {
        return DB::transaction(function () use ($cart, $listing, $qty, $customerId) {
            $inventory = WarehouseInventory::where('vendor_listing_id', $listing->id)
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if (! $inventory) {
                throw new \DomainException("No inventory record found for listing {$listing->id}.");
            }

            if ($inventory->quantity_available < $qty) {
                throw new \DomainException(
                    "Insufficient stock for \"{$listing->id}\". Only {$inventory->quantity_available} unit(s) available."
                );
            }

            $inventory->increment('quantity_reserved', $qty);

            $inventory->refresh();

            InventoryMovement::create([
                'warehouse_inventory_id' => $inventory->id,
                'movement_type'          => 'reservation',
                'quantity_delta'         => $qty,
                'quantity_after'         => $inventory->quantity_on_hand,
                'reference_type'         => 'order',
                'reference_id'           => $cart->id,
                'reason'                 => 'Checkout reservation',
                'created_by_user_id'     => $customerId,
            ]);

            return CartInventoryLock::create([
                'cart_id'                => $cart->id,
                'warehouse_inventory_id' => $inventory->id,
                'vendor_listing_id'      => $listing->id,
                'quantity'               => $qty,
                'expires_at'             => now()->addMinutes(self::LOCK_TTL_MINUTES),
            ]);
        });
    }

    /**
     * Release a single inventory lock and log the reversal.
     */
    public function release(CartInventoryLock $lock, string $actorId): void
    {
        DB::transaction(function () use ($lock, $actorId) {
            $inventory = WarehouseInventory::where('id', $lock->warehouse_inventory_id)
                ->lockForUpdate()
                ->firstOrFail();

            $inventory->decrement('quantity_reserved', $lock->quantity);
            $inventory->refresh();

            InventoryMovement::create([
                'warehouse_inventory_id' => $inventory->id,
                'movement_type'          => 'release',
                'quantity_delta'         => -$lock->quantity,
                'quantity_after'         => $inventory->quantity_on_hand,
                'reference_type'         => 'order',
                'reference_id'           => $lock->cart_id,
                'reason'                 => 'Lock released',
                'created_by_user_id'     => $actorId,
            ]);

            $lock->delete();
        });
    }

    /**
     * Release all locks for a cart (used on payment failure or cart clear).
     */
    public function releaseAllForCart(Cart $cart, string $actorId): void
    {
        $locks = CartInventoryLock::where('cart_id', $cart->id)->get();
        foreach ($locks as $lock) {
            $this->release($lock, $actorId);
        }
    }

    /**
     * Convert reservation locks to confirmed outbound movements when order is placed.
     * Does NOT delete locks — caller deletes them after successful commit.
     */
    public function confirmLocks(Cart $cart, string $orderId, string $actorId): void
    {
        $locks = CartInventoryLock::where('cart_id', $cart->id)->get();

        foreach ($locks as $lock) {
            $inventory = WarehouseInventory::where('id', $lock->warehouse_inventory_id)
                ->lockForUpdate()
                ->firstOrFail();

            $inventory->decrement('quantity_on_hand', $lock->quantity);
            $inventory->decrement('quantity_reserved', $lock->quantity);
            $inventory->refresh();

            InventoryMovement::create([
                'warehouse_inventory_id' => $inventory->id,
                'movement_type'          => 'outbound',
                'quantity_delta'         => -$lock->quantity,
                'quantity_after'         => $inventory->quantity_on_hand,
                'reference_type'         => 'order',
                'reference_id'           => $orderId,
                'reason'                 => 'Order placed',
                'created_by_user_id'     => $actorId,
            ]);
        }
    }
}
