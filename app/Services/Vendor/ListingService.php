<?php

namespace App\Services\Vendor;

use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SubOrder;
use App\Models\VendorListing;
use App\Models\Vendor;
use Illuminate\Validation\ValidationException;

class ListingService
{
    public function create(array $data, Vendor $vendor): VendorListing
    {
        $variant = ProductVariant::findOrFail($data['product_variant_id']);

        if (! $variant->is_active || $variant->trashed()) {
            throw ValidationException::withMessages([
                'product_variant_id' => ['This product variant is not available for listing.'],
            ]);
        }

        return VendorListing::create([
            'vendor_id'              => $vendor->id,
            'product_variant_id'     => $data['product_variant_id'],
            'country_id'             => $data['country_id'],
            'price'                  => $data['price'],
            'compare_at_price'       => $data['compare_at_price'] ?? null,
            'condition'              => $data['condition'],
            'fulfillment_model'      => $data['fulfillment_model'],
            'status'                 => 'pending_review',
        ]);
    }

    public function updatePrice(VendorListing $listing, array $data): VendorListing
    {
        $listing->update([
            'price'            => $data['price'],
            'compare_at_price' => $data['compare_at_price'] ?? null,
        ]);

        return $listing->fresh();
    }

    public function updateStatus(VendorListing $listing, string $status): VendorListing
    {
        $listing->update(['status' => $status]);

        return $listing->fresh();
    }

    public function delete(VendorListing $listing): void
    {
        $hasOpenOrders = OrderItem::where('vendor_listing_id', $listing->id)
            ->whereHas('subOrder', fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled', 'refunded']))
            ->exists();

        if ($hasOpenOrders) {
            throw ValidationException::withMessages([
                'listing' => ['Cannot delete a listing with open orders.'],
            ]);
        }

        $listing->update(['status' => 'archived']);
    }
}
