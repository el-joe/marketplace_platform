<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CountryPaymentMethod;
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
        $cart->load([
            'items.vendorListing.vendor',
            'items.vendorListing.productVariant.product.category',
            'items.vendorListing.warehouseInventories.warehouse',
        ]);

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

            // Compute commission per item based on each listing's global_system_type
            // and its product's category rates (with parent-category inheritance).
            $itemsCommissionData = [];
            $platformCommission = 0;

            foreach ($items as $cartItem) {
                $listing = $cartItem->vendorListing;
                $systemType = $listing->global_system_type ?? 'merchant_fbp';
                $category = $listing->productVariant->product->category ?? null;

                $rates = $this->resolveCommissionRates($vendorId, $systemType, $category);

                $lineSubtotal = $cartItem->unit_price * $cartItem->quantity;
                $commissionFromPct = (int) round($lineSubtotal * $rates['pct'] / 100);
                $commissionFromFixed = $rates['fixed_cents'] * $cartItem->quantity; // per-unit
                $itemCommission = $commissionFromPct + $commissionFromFixed;

                $itemsCommissionData[$cartItem->id] = [
                    'rate_pct'    => $rates['pct'],
                    'fixed_cents' => $rates['fixed_cents'],
                    'amount'      => $itemCommission,
                    'category_id' => $rates['category_id'],
                ];

                $platformCommission += $itemCommission;
            }

            $vendorPayout = $subtotal - $platformCommission;

            $firstItem = $items->first();
            $vendor = $firstItem->vendorListing->vendor;
            $fulfillmentModel = $firstItem->vendorListing->fulfillment_model;
            $warehouseId = $firstItem->vendorListing->warehouseInventories->first()?->warehouse_id;

            $blueprints[] = [
                'vendor_id'            => $vendorId,
                'vendor'               => $vendor,
                'items'                => $items,
                'items_commission_data' => $itemsCommissionData,
                'subtotal'             => $subtotal,
                'shipping'             => $shipping,
                'tax'                  => $tax,
                'platform_commission'  => $platformCommission,
                'gateway_fee'          => 0,
                'gateway_fee_rate'     => 0,
                'vendor_payout'        => $vendorPayout,
                'fulfillment_model'    => $fulfillmentModel,
                'warehouse_id'         => $warehouseId,
            ];
        }

        return $blueprints;
    }

    /**
     * Deduct each sub-order's proportional share of the payment gateway fee from its
     * vendor_payout. Gateway fees are borne by the vendor, never the platform or customer.
     *
     * The fee is computed from the resolved CountryPaymentMethod config (fee_pct + fee_fixed_cents),
     * not from the gateway's own reported fee — gateways like Thawani don't return a fee amount
     * synchronously at checkout, so we use our own configured rate to keep this deterministic.
     *
     * COD has no gateway processing fee by definition: $methodConfig is expected to be null
     * for COD, but we also short-circuit if its fee fields are zero.
     */
    public function applyGatewayFee(array $blueprints, int $grandTotal, ?CountryPaymentMethod $methodConfig): array
    {
        if (!$methodConfig || $grandTotal <= 0) {
            return $blueprints;
        }

        $feePct = (float) $methodConfig->fee_pct;
        $feeFixed = (int) $methodConfig->fee_fixed_cents;
        $totalFee = (int) round($grandTotal * ($feePct / 100)) + $feeFixed;

        if ($totalFee <= 0) {
            return $blueprints;
        }

        $totalSubtotal = array_sum(array_column($blueprints, 'subtotal'));
        if ($totalSubtotal <= 0) {
            return $blueprints;
        }

        $feeRate = $totalFee / $grandTotal;
        $remainingFee = $totalFee;
        $count = count($blueprints);

        foreach ($blueprints as $i => &$blueprint) {
            if ($i === $count - 1) {
                $subOrderFee = $remainingFee;
            } else {
                $subOrderFee = (int) round($totalFee * ($blueprint['subtotal'] / $totalSubtotal));
            }
            $remainingFee -= $subOrderFee;

            $blueprint['gateway_fee'] = $subOrderFee;
            $blueprint['gateway_fee_rate'] = round($feeRate, 6);
            $blueprint['vendor_payout'] -= $subOrderFee;
        }
        unset($blueprint);

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

    /**
     * Resolve FBP or FBN commission rates for a single cart item.
     *
     * Priority:
     *   1. Vendor-level override from the Commission table (rate_pct only, 0 fixed)
     *   2. Category's own FBP/FBN columns, walking up the ancestor chain until
     *      a non-zero rate is found (subcategory inheritance)
     *   3. Platform default: 10% with no fixed fee
     *
     * Returns ['pct' => float, 'fixed_cents' => int, 'category_id' => string|null]
     */
    private function resolveCommissionRates(string $vendorId, string $systemType, ?Category $category): array
    {
        // 1. Vendor-level override (Commission table): percentage only, no fixed fee.
        $vendorOverride = Commission::where('vendor_id', $vendorId)
            ->whereNull('category_id')
            ->where('effective_from', '<=', today())
            ->where(fn($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', today()))
            ->orderByDesc('priority')
            ->first();

        if ($vendorOverride) {
            return [
                'pct'         => (float) $vendorOverride->rate_pct,
                'fixed_cents' => 0,
                'category_id' => null,
            ];
        }

        // 2. Category rates with parent-chain inheritance.
        if ($category) {
            // Check own category first, then ancestors from nearest to root.
            $candidates = collect([$category])
                ->concat($category->ancestors()->orderByDesc('depth')->get());

            foreach ($candidates as $node) {
                $pct = $systemType === 'express_fbn'
                    ? (float) $node->commission_fbn_pct
                    : (float) $node->commission_fbp_pct;
                $fixed = $systemType === 'express_fbn'
                    ? (int) $node->commission_fbn_fixed_cents
                    : (int) $node->commission_fbp_fixed_cents;

                if ($pct > 0 || $fixed > 0) {
                    return ['pct' => $pct, 'fixed_cents' => $fixed, 'category_id' => $node->id];
                }
            }
        }

        // 3. Platform default.
        return ['pct' => 10.0, 'fixed_cents' => 0, 'category_id' => null];
    }
}
