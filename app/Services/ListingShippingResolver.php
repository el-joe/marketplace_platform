<?php

namespace App\Services;

use App\Enums\GlobalSystemType;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\ShippingMethod;
use App\Models\VendorListing;
use Illuminate\Support\Collection;

class ListingShippingResolver
{
    public function resolveForListing(VendorListing|AdminProductListing $listing): Collection
    {
        $category = $this->resolveCategory(
            $listing->productVariant->product->category
        );

        if (!$category) return collect();

        // $isFBN = $listing->global_system_type === 'express_fbn';
        // $fbpField = $isFBN ? 'is_available_for_express_fbn' : 'is_available_for_merchant_fbp';

        return $category->shippingMethods()
            // ->where("category_shipping_methods.{$fbpField}", true)
            ->orderBy('shipping_methods.display_priority')
            ->get();
    }

    public function resolvePrimary(VendorListing $listing): ?ShippingMethod
    {
        $category = $this->resolveCategory(
            $listing->productVariant->product->category
        );

        if (!$category) return null;

        $isFBN = $listing->global_system_type === GlobalSystemType::ExpressFbn;
        $fbpField = $isFBN ? 'is_available_for_express_fbn' : 'is_available_for_merchant_fbp';

        return $category->shippingMethods()
            ->where('category_shipping_methods.is_default', true)
            ->where("category_shipping_methods.{$fbpField}", true)
            ->first();
    }

    public function cacheOnListing(VendorListing $listing): void
    {
        $primary = $this->resolvePrimary($listing);
        $listing->updateQuietly([
            'primary_shipping_method_id' => $primary?->id,
        ]);
    }

    private function resolveCategory(Category $category): ?Category
    {
        $node = $category;
        while ($node) {
            if ($node->shippingMethods()->exists()) return $node;
            $node = $node->parent;
        }
        return null;
    }
}
