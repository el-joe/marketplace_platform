<?php

namespace App\Services;

use App\Models\AdminProductListing;
use App\Models\CartItem;
use Illuminate\Support\Collection;

/**
 * Handles checkout logic for carts that may contain admin product listings
 * with varying payment_options constraints.
 *
 * Payment split rules:
 *  - If all items allow electronic payment → one sub-order, all payment methods available.
 *  - If any item is cod_only             → COD enforced for the entire sub-order containing it.
 *  - Mixed cod_only + electronic_only    → items are split into separate sub-orders,
 *    each with only its allowed payment method.
 */
class CheckoutService
{
    /**
     * Resolve which payment methods are available for a collection of cart items.
     *
     * Returns one of:
     *   'all'               – no restrictions
     *   'cod_only'          – every item allows COD but at least one requires it
     *   'electronic_only'   – every item allows electronic but at least one requires it
     *   'conflict'          – items cannot share a sub-order (needs splitting)
     *
     * @param Collection<CartItem> $items
     */
    public function resolvePaymentConstraint(Collection $items): string
    {
        $listingIds = $items
            ->filter(fn(CartItem $i) => $i->source_type === AdminProductListing::class || $i->source_type === 'admin_listing')
            ->pluck('source_id');

        if ($listingIds->isEmpty()) {
            return 'all';
        }

        $listings = AdminProductListing::whereIn('id', $listingIds)->get()->keyBy('id');

        $hasCodOnly        = false;
        $hasElectronicOnly = false;

        foreach ($listingIds as $id) {
            $listing = $listings->get($id);
            if (!$listing) {
                continue;
            }

            match ($listing->payment_options) {
                'cod_only'        => $hasCodOnly = true,
                'electronic_only' => $hasElectronicOnly = true,
                default           => null,
            };
        }

        if ($hasCodOnly && $hasElectronicOnly) {
            return 'conflict';
        }

        if ($hasCodOnly) {
            return 'cod_only';
        }

        if ($hasElectronicOnly) {
            return 'electronic_only';
        }

        return 'all';
    }

    /**
     * Split a collection of cart items into sub-order groups based on
     * payment_options compatibility.
     *
     * Returns an array of groups, each with:
     *   [
     *     'items'           => Collection<CartItem>,
     *     'payment_constraint' => 'all' | 'cod_only' | 'electronic_only',
     *   ]
     *
     * Items without an AdminProductListing source are treated as unrestricted
     * and grouped with whichever first group is compatible.
     *
     * @param Collection<CartItem> $items
     * @return array<int, array{items: Collection, payment_constraint: string}>
     */
    public function splitByPaymentConstraint(Collection $items): array
    {
        $adminListingIds = $items
            ->filter(fn(CartItem $i) => $i->source_type === AdminProductListing::class || $i->source_type === 'admin_listing')
            ->pluck('source_id');

        $listings = AdminProductListing::whereIn('id', $adminListingIds)->get()->keyBy('id');

        $codOnlyItems        = collect();
        $electronicOnlyItems = collect();
        $unrestricted        = collect();

        foreach ($items as $item) {
            $isAdminListing = $item->source_type === AdminProductListing::class
                || $item->source_type === 'admin_listing';

            if (!$isAdminListing) {
                $unrestricted->push($item);
                continue;
            }

            $listing = $listings->get($item->source_id);

            match ($listing?->payment_options ?? 'both') {
                'cod_only'        => $codOnlyItems->push($item),
                'electronic_only' => $electronicOnlyItems->push($item),
                default           => $unrestricted->push($item),
            };
        }

        $groups = [];

        // COD-only sub-order
        if ($codOnlyItems->isNotEmpty()) {
            $groups[] = [
                'items'              => $codOnlyItems,
                'payment_constraint' => 'cod_only',
            ];
        }

        // Electronic-only sub-order
        if ($electronicOnlyItems->isNotEmpty()) {
            $groups[] = [
                'items'              => $electronicOnlyItems,
                'payment_constraint' => 'electronic_only',
            ];
        }

        // Unrestricted items: attach to first existing group or create their own
        if ($unrestricted->isNotEmpty()) {
            if (!empty($groups)) {
                $groups[0]['items'] = $groups[0]['items']->merge($unrestricted);
            } else {
                $groups[] = [
                    'items'              => $unrestricted,
                    'payment_constraint' => 'all',
                ];
            }
        }

        // If no groups were created (all items were unrestricted and empty), safe fallback
        if (empty($groups)) {
            $groups[] = [
                'items'              => $items,
                'payment_constraint' => 'all',
            ];
        }

        return $groups;
    }

    /**
     * Filter available payment method codes for a given constraint.
     *
     * @param  string   $constraint  'all' | 'cod_only' | 'electronic_only'
     * @param  string[] $available   All payment method codes offered by the platform
     * @return string[]
     */
    public function filterPaymentMethods(string $constraint, array $available): array
    {
        $codCodes        = ['cod', 'cash_on_delivery'];
        $electronicCodes = array_diff($available, $codCodes);

        return match ($constraint) {
            'cod_only'        => array_values(array_intersect($available, $codCodes)),
            'electronic_only' => array_values($electronicCodes),
            default           => $available,
        };
    }
}
