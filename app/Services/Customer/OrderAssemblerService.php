<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\Commission;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxRule;
use Illuminate\Support\Collection;

class OrderAssemblerService
{
    /**
     * Group cart items by vendor, compute per-group shipping and commission.
     * Returns an array of sub-order blueprints — no DB writes here.
     *
     * @return array<int, array{
     *   vendor_id: string,
     *   vendor: \App\Models\Vendor,
     *   items: Collection,
     *   subtotal: int,
     *   shipping: int,
     *   tax: int,
     *   commission_rate_pct: float,
     *   platform_commission: int,
     *   vendor_payout: int,
     *   fulfillment_model: string,
     *   warehouse_id: string|null,
     * }>
     */
    public function buildSubOrderBlueprints(
        Cart $cart,
        string $shippingMethodId,
        string $destinationCityId,
        bool $isCod,
        string $countryId
    ): array {
        $cart->load(['items.vendorListing.vendor', 'items.vendorListing.productVariant.product', 'items.vendorListing.warehouseInventories.warehouse']);

        $grouped = $cart->items->groupBy(fn($item) => $item->vendorListing->vendor_id);
        $destinationZoneId = $this->resolveDestinationZone($destinationCityId);
        $taxRate = $this->resolveTaxRate($countryId);

        $blueprints = [];

        foreach ($grouped as $vendorId => $items) {
            $subtotal = (int) $items->sum(fn($i) => $i->unit_price * $i->quantity);

            $shipping = $this->computeShipping(
                $subtotal,
                $shippingMethodId,
                $destinationZoneId,
                $isCod
            );

            $tax = (int) round(($subtotal + $shipping) * ($taxRate / 100));

            $commissionRate = $this->resolveCommissionRate($vendorId, $items);
            $platformCommission = (int) round($subtotal * ($commissionRate / 100));
            $vendorPayout = $subtotal - $platformCommission;

            $firstItem = $items->first();
            $vendor = $firstItem->vendorListing->vendor;
            $fulfillmentModel = $firstItem->vendorListing->fulfillment_model;
            $warehouseId = $firstItem->vendorListing->warehouseInventories->first()?->warehouse_id;

            $blueprints[] = [
                'vendor_id'          => $vendorId,
                'vendor'             => $vendor,
                'items'              => $items,
                'subtotal'           => $subtotal,
                'shipping'           => $shipping,
                'tax'                => $tax,
                'commission_rate_pct' => $commissionRate,
                'platform_commission' => $platformCommission,
                'vendor_payout'      => $vendorPayout,
                'fulfillment_model'  => $fulfillmentModel,
                'warehouse_id'       => $warehouseId,
            ];
        }

        return $blueprints;
    }

    public function resolveDestinationZone(string $cityId): ?string
    {
        return \App\Models\City::find($cityId)?->shipping_zone_id;
    }

    public function resolveTaxRate(string $countryId): float
    {
        $rule = TaxRule::where('country_id', $countryId)
            ->whereNull('category_id')
            ->where('effective_from', '<=', today())
            ->where(fn($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today()))
            ->orderByDesc('effective_from')
            ->first();

        return $rule ? (float) $rule->rate_pct : 0.0;
    }

    public function computeShipping(
        int $subtotal,
        string $shippingMethodId,
        ?string $destinationZoneId,
        bool $isCod
    ): int {
        if (! $destinationZoneId) {
            return 0;
        }

        $rate = ShippingRate::where('shipping_method_id', $shippingMethodId)
            ->where('destination_zone_id', $destinationZoneId)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            return 0;
        }

        if ($rate->free_shipping_threshold !== null && $subtotal >= $rate->free_shipping_threshold) {
            return $isCod ? (int) $rate->cod_extra_fee : 0;
        }

        $cost = (int) $rate->base_fee;

        if ($isCod) {
            $cost += (int) $rate->cod_extra_fee;
        }

        return $cost;
    }

    private function resolveCommissionRate(string $vendorId, Collection $items): float
    {
        // Prefer vendor-specific commission, fall back to category, then platform default
        $vendorCommission = Commission::where('vendor_id', $vendorId)
            ->whereNull('category_id')
            ->where('effective_from', '<=', today())
            ->where(fn($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today()))
            ->orderByDesc('priority')
            ->first();

        if ($vendorCommission) {
            return (float) $vendorCommission->rate_pct;
        }

        // Try category-level commission from the first item
        $categoryId = $items->first()?->vendorListing?->productVariant?->product?->category_id;
        if ($categoryId) {
            $categoryCommission = Commission::where('category_id', $categoryId)
                ->whereNull('vendor_id')
                ->where('effective_from', '<=', today())
                ->where(fn($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today()))
                ->orderByDesc('priority')
                ->first();

            if ($categoryCommission) {
                return (float) $categoryCommission->rate_pct;
            }
        }

        return 10.0; // Platform default 10%
    }
}
