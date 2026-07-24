<?php

namespace App\Services;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\AdminProductListing;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\FlashSaleSubmission;
use App\Models\ShippingMethod;
use App\Models\VendorListing;
use App\Models\WarrantyPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-enriches cart items with delivery estimates, coupon eligibility,
 * stock status and warranty/free-shipping badges for the cart response.
 *
 * All money fields are BIGINT base currency units — never divided or
 * multiplied by 100 here. Cost/payout/commission fields are never read
 * or exposed. Everything needed for the per-item mapping loop is batch
 * loaded up front to avoid N+1 queries.
 */
class CartItemEnrichmentService
{
    private const GROUP_ORDER = [
        'express' => 0,
        'same_day' => 1,
        'standard' => 2,
    ];

    /**
     * @param Collection<int, \App\Models\CartItem> $cartItems
     * @return array<int, array<string, mixed>>
     */
    public function enrich(Collection $cartItems, ?Customer $customer, ?\App\Models\Country $country): array
    {
        $timezone = $country?->timezone ?? 'Asia/Dubai';

        $vendorItems = $cartItems->filter(fn ($item) => empty($item->admin_product_listing_id));
        $adminItems = $cartItems->filter(fn ($item) => !empty($item->admin_product_listing_id));

        $vendorListingIds = $vendorItems->pluck('vendor_listing_id')->filter()->unique()->values();
        $adminListingIds = $adminItems->pluck('admin_product_listing_id')->filter()->unique()->values();

        $vendorListings = VendorListing::with([
            'vendor',
            'productVariant.product.category',
            'productVariant.attributeValues.attribute',
            'productVariant.images',
            'primaryShippingMethod',
        ])->whereIn('id', $vendorListingIds)->get()->keyBy('id');

        $adminListings = AdminProductListing::with([
            'productVariant.product.category',
            'productVariant.attributeValues.attribute',
            'productVariant.images',
        ])->whereIn('id', $adminListingIds)->get()->keyBy('id');

        $categoryIds = $vendorListings->merge($adminListings)
            ->map(fn ($listing) => $listing->productVariant?->product?->category_id)
            ->filter()
            ->unique()
            ->values();

        $vendorIds = $vendorListings->pluck('vendor_id')->filter()->unique()->values();

        // ── Inventory sums (vendor path only). quantity_available is a MySQL
        // GENERATED VIRTUAL column — never write it; here we aggregate the
        // underlying on_hand/reserved columns ourselves rather than SUM()ing
        // the virtual column directly. ──────────────────────────────────────
        $inventoryByListing = $vendorListingIds->isEmpty() ? collect() : DB::table('warehouse_inventories')
            ->whereIn('vendor_listing_id', $vendorListingIds)
            ->selectRaw('vendor_listing_id, SUM(quantity_on_hand - quantity_reserved) as available')
            ->groupBy('vendor_listing_id')
            ->pluck('available', 'vendor_listing_id');

        // ── Shipping methods: full table (small, reference data) + each
        // category's default method. ────────────────────────────────────────
        $shippingMethods = ShippingMethod::where('is_active', true)->get()->keyBy('id');

        $categoryDefaultMethodId = $categoryIds->isEmpty() ? collect() : DB::table('category_shipping_methods')
            ->where('is_default', true)
            ->whereIn('category_id', $categoryIds)
            ->pluck('shipping_method_id', 'category_id');

        // ── Active flash sales for these vendor listings ────────────────────
        $flashSubmissions = $vendorListingIds->isEmpty() ? collect() : FlashSaleSubmission::with('flashSale')
            ->whereIn('vendor_listing_id', $vendorListingIds)
            ->where('status', 'live')
            ->whereHas('flashSale', function ($q) {
                $q->where('status', 'live')
                    ->where('sale_starts_at', '<=', now())
                    ->where('sale_ends_at', '>=', now());
            })
            ->get()
            ->keyBy('vendor_listing_id');

        // ── Active coupons in scope (platform / this vendor / these
        // categories). Product-scoped coupons are not yet supported by this
        // enrichment — always excluded (coupon_products exists, but per-item
        // product matching is a separate follow-up). ───────────────────────
        $activeCoupons = Coupon::where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->where(function ($q) use ($vendorIds, $categoryIds) {
                $q->where('scope', CouponScope::Platform->value)
                    ->orWhere(function ($q2) use ($vendorIds) {
                        $q2->where('scope', CouponScope::Vendor->value)->whereIn('vendor_id', $vendorIds);
                    })
                    ->orWhere(function ($q3) use ($categoryIds) {
                        $q3->where('scope', CouponScope::Category->value)->whereIn('category_id', $categoryIds);
                    });
            })
            ->get();

        $usedCouponCounts = collect();
        if ($customer && $activeCoupons->isNotEmpty()) {
            $usedCouponCounts = CouponUsage::where('customer_id', $customer->id)
                ->whereIn('coupon_id', $activeCoupons->pluck('id'))
                ->selectRaw('coupon_id, count(*) as used_count')
                ->groupBy('coupon_id')
                ->pluck('used_count', 'coupon_id');
        }

        // ── Warranty plans, resolved per root category. Root is resolved by
        // walking parent_id in PHP against a fully preloaded id => parent_id
        // map, so no additional queries are issued per item. ────────────────
        $categoryParentMap = Category::query()->pluck('parent_id', 'id');
        $rootCategoryIdByCategoryId = [];
        foreach ($categoryIds as $categoryId) {
            $rootCategoryIdByCategoryId[$categoryId] = $this->resolveRootCategoryId($categoryId, $categoryParentMap);
        }
        $rootCategoryIds = collect($rootCategoryIdByCategoryId)->filter()->unique()->values();

        $warrantyPlansByRootCategory = $rootCategoryIds->isEmpty() ? collect() : WarrantyPlan::active()
            ->whereIn('category_id', $rootCategoryIds)
            ->when($country, fn ($q) => $q->forCountry($country->id))
            ->get()
            ->groupBy('category_id');

        return $cartItems->map(function ($item) use (
            $vendorListings,
            $adminListings,
            $inventoryByListing,
            $shippingMethods,
            $categoryDefaultMethodId,
            $flashSubmissions,
            $activeCoupons,
            $usedCouponCounts,
            $rootCategoryIdByCategoryId,
            $warrantyPlansByRootCategory,
            $timezone,
        ) {
            $isAdminListing = !empty($item->admin_product_listing_id);
            $listing = $isAdminListing
                ? $adminListings->get($item->admin_product_listing_id)
                : $vendorListings->get($item->vendor_listing_id);

            if (!$listing) {
                return $this->emptyItemShape($item);
            }

            return $this->buildEnrichedItem(
                $item,
                $listing,
                $isAdminListing,
                $inventoryByListing,
                $shippingMethods,
                $categoryDefaultMethodId,
                $flashSubmissions,
                $activeCoupons,
                $usedCouponCounts,
                $rootCategoryIdByCategoryId,
                $warrantyPlansByRootCategory,
                $timezone,
            );
        })->values()->all();
    }

    /**
     * Groups enriched items by their resolved primary shipping method.
     * Order: express, same_day, standard, then other methods by
     * display_priority ASC, then a fallback group (unresolvable method)
     * last. This grouped shape is the only cart items shape returned.
     *
     * @param array<int, array<string, mixed>> $enrichedItems
     * @return array<int, array<string, mixed>>
     */
    public function groupByShippingMethod(array $enrichedItems): array
    {
        $groups = [];

        foreach ($enrichedItems as $item) {
            $method = $item['delivery']['primary_method'] ?? null;
            $key = $method['id'] ?? 'fallback';

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'shipping_method' => $method,
                    'group_estimate' => $item['delivery']['estimate'] ?? null,
                    'display_priority' => $item['_display_priority'] ?? null,
                    'items' => [],
                ];
            }

            unset($item['_display_priority']);
            $groups[$key]['items'][] = $item;
        }

        $groupList = array_values($groups);

        usort($groupList, function ($a, $b) {
            $rankA = $this->groupRank($a);
            $rankB = $this->groupRank($b);

            return $rankA <=> $rankB;
        });

        foreach ($groupList as &$group) {
            unset($group['display_priority']);
        }

        return $groupList;
    }

    private function groupRank(array $group): float
    {
        $method = $group['shipping_method'];

        if (!$method) {
            return 999999;
        }

        $code = $method['code'] ?? null;

        if (isset(self::GROUP_ORDER[$code])) {
            return self::GROUP_ORDER[$code];
        }

        return 100 + (float) ($group['display_priority'] ?? 0);
    }

    private function buildEnrichedItem(
        $item,
        $listing,
        bool $isAdminListing,
        Collection $inventoryByListing,
        Collection $shippingMethods,
        Collection $categoryDefaultMethodId,
        Collection $flashSubmissions,
        Collection $activeCoupons,
        Collection $usedCouponCounts,
        array $rootCategoryIdByCategoryId,
        Collection $warrantyPlansByRootCategory,
        string $timezone,
    ): array {
        $variant = $listing->productVariant;
        $product = $variant?->product;
        $categoryId = $product?->category_id;

        $shippingMethod = $this->resolvePrimaryShippingMethod(
            $listing,
            $isAdminListing,
            $categoryId,
            $shippingMethods,
            $categoryDefaultMethodId,
        );

        $estimate = $shippingMethod ? $this->computeDeliveryEstimate($shippingMethod, $timezone, $shippingMethods, $categoryId, $categoryDefaultMethodId) : null;

        $flashSale = null;
        if (!$isAdminListing) {
            $flashSale = $this->buildFlashSaleShape($flashSubmissions->get($listing->id));
        }

        $inventoryAvailable = $isAdminListing ? null : $inventoryByListing->get($listing->id);
        $stock = $this->stockStatus($listing, $isAdminListing, $inventoryAvailable, (int) $item->quantity);

        $coupons = $isAdminListing ? [] : $this->eligibleCouponsForItem(
            $listing,
            $activeCoupons,
            $usedCouponCounts,
            $categoryId,
        );

        $rootCategoryId = $categoryId ? ($rootCategoryIdByCategoryId[$categoryId] ?? null) : null;
        $warrantyPlans = $rootCategoryId ? ($warrantyPlansByRootCategory->get($rootCategoryId) ?? collect()) : collect();
        $warranty = $this->buildWarrantyBadge($warrantyPlans);

        $isFreeShipping = $isAdminListing
            ? ((int) ($listing->shipping_cost ?? 0)) === 0
            : (bool) $listing->vendor_covers_delivery;
        // TODO: no zone/rate is resolved in the cart context, so free shipping
        // here reflects vendor_covers_delivery / a flat admin shipping_cost of
        // zero only. A full ShippingCalculationService::calculate() call needs
        // a destination ShippingZone which isn't available at this layer.

        $vendorName = $isAdminListing ? null : ($listing->vendor?->store_name ?? $listing->vendor?->name);

        return [
            'id' => $item->id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (int) $item->unit_price,
            'line_total' => (int) $item->unit_price * (int) $item->quantity,
            'currency' => $isAdminListing ? $listing->currency : $listing->currency,
            'added_at' => optional($item->added_at)->toIso8601String(),
            'listing' => [
                'id' => $listing->id,
                'price' => (int) $listing->getRawOriginal('price'),
                'compare_at_price' => $isAdminListing ? null : ($listing->compare_at_price !== null ? (int) $listing->compare_at_price : null),
                'currency' => $listing->currency,
                'condition' => $isAdminListing ? null : $listing->condition,
                'global_system_type' => $isAdminListing ? null : $listing->global_system_type?->value,
                'vendor_covers_delivery' => $isAdminListing ? null : (bool) $listing->vendor_covers_delivery,
                'status' => $isAdminListing ? $listing->status?->value : $listing->status?->value,
                'flash_sale' => $flashSale,
                'vendor' => $isAdminListing ? null : ($listing->vendor ? [
                    'id' => $listing->vendor->id,
                    'name' => $vendorName,
                ] : null),
                'product_variant' => $variant ? [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name_en' => $product?->name_en,
                    'name_ar' => $product?->name_ar,
                    'primary_image_url' => $this->primaryImageUrl($variant),
                    'attributes' => $variant->attributeValues->map(fn ($value) => [
                        'attribute_en' => $value->attribute?->name_en,
                        'value_en' => $value->value_en,
                    ])->values()->all(),
                ] : null,
                'product' => $product ? [
                    'id' => $product->id,
                    'name_en' => $product->name_en,
                    'name_ar' => $product->name_ar,
                    'slug' => $product->slug,
                ] : null,
            ],
            'delivery' => [
                'primary_method' => $shippingMethod ? [
                    'id' => $shippingMethod->id,
                    'code' => $shippingMethod->code,
                    'badge_label_en' => $shippingMethod->badge_label_en,
                    'badge_label_ar' => $shippingMethod->badge_label_ar,
                    'badge_color_hex' => $shippingMethod->badge_color_hex,
                    'badge_text_color_hex' => $shippingMethod->badge_text_color_hex,
                    'is_express_type' => (bool) $shippingMethod->is_express_type,
                ] : null,
                'estimate' => $estimate,
                'is_free_shipping' => $isFreeShipping,
                'free_shipping_label_en' => $isFreeShipping ? 'Free Shipping' : null,
            ],
            'badges' => [
                'coupons' => $coupons,
                'low_stock' => [
                    'is_low_stock' => $stock['is_low_stock'],
                    'label_en' => $stock['low_stock_label_en'],
                    'label_ar' => $stock['low_stock_label_ar'],
                ],
                'warranty' => $warranty,
                'free_shipping' => $isFreeShipping,
                'sold_by' => $isAdminListing ? 'Official Store' : ($vendorName ? "Sold by {$vendorName}" : null),
            ],
            'stock' => [
                'available_quantity' => $stock['available_quantity'],
                'is_low_stock' => $stock['is_low_stock'],
                'is_out_of_stock' => $stock['is_out_of_stock'],
                'exceeds_stock' => $stock['exceeds_stock'],
            ],
            '_display_priority' => $shippingMethod->display_priority ?? null,
        ];
    }

    private function emptyItemShape($item): array
    {
        return [
            'id' => $item->id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (int) $item->unit_price,
            'line_total' => (int) $item->unit_price * (int) $item->quantity,
            'currency' => null,
            'added_at' => optional($item->added_at)->toIso8601String(),
            'listing' => null,
            'delivery' => [
                'primary_method' => null,
                'estimate' => null,
                'is_free_shipping' => false,
                'free_shipping_label_en' => null,
            ],
            'badges' => [
                'coupons' => [],
                'low_stock' => ['is_low_stock' => false, 'label_en' => null, 'label_ar' => null],
                'warranty' => ['has_warranty_plan' => false, 'label_en' => null, 'label_ar' => null, 'plans' => []],
                'free_shipping' => false,
                'sold_by' => null,
            ],
            'stock' => [
                'available_quantity' => null,
                'is_low_stock' => false,
                'is_out_of_stock' => false,
                'exceeds_stock' => false,
            ],
            '_display_priority' => null,
        ];
    }

    private function resolvePrimaryShippingMethod(
        $listing,
        bool $isAdminListing,
        ?string $categoryId,
        Collection $shippingMethods,
        Collection $categoryDefaultMethodId,
    ): ?ShippingMethod {
        if (!$isAdminListing && $listing->primary_shipping_method_id && $shippingMethods->has($listing->primary_shipping_method_id)) {
            return $shippingMethods->get($listing->primary_shipping_method_id);
        }

        if ($categoryId && $categoryDefaultMethodId->has($categoryId)) {
            return $shippingMethods->get($categoryDefaultMethodId->get($categoryId));
        }

        return null;
    }

    /**
     * @return array{day_label_en:string,day_label_ar:?string,earliest_date:string,latest_date:string,countdown_label:?string,cutoff_passed:bool,express_upsell:?array}
     */
    public function computeDeliveryEstimate(
        ShippingMethod $method,
        string $timezone,
        ?Collection $shippingMethods = null,
        ?string $categoryId = null,
        ?Collection $categoryDefaultMethodId = null,
    ): array {
        $now = Carbon::now($timezone);
        $cutoffPassed = false;

        if ($method->order_cutoff_time) {
            $cutoff = Carbon::parse($now->toDateString() . ' ' . $method->order_cutoff_time, $timezone);
            $cutoffPassed = $now->greaterThan($cutoff);
        } else {
            $cutoff = null;
        }

        $minDays = (int) ($method->min_delivery_days ?? 0) + ($cutoffPassed ? 1 : 0);
        $maxDays = (int) ($method->max_delivery_days ?? $minDays) + ($cutoffPassed ? 1 : 0);

        $readyAt = $now->copy()->addHours((int) ($method->handling_time_hours ?? 0));
        $earliestAt = $readyAt->copy()->addDays($minDays);
        $latestAt = $readyAt->copy()->addDays($maxDays);

        $dayLabelEn = 'Today';
        $dayLabelAr = 'اليوم';
        if ($earliestAt->isTomorrow()) {
            $dayLabelEn = 'Tomorrow';
            $dayLabelAr = 'غداً';
        } elseif (!$earliestAt->isToday()) {
            $dayLabelEn = $earliestAt->format('D, M j');
            $dayLabelAr = null;
        }

        $countdownLabel = null;
        if (!$cutoffPassed && $cutoff) {
            $minutesLeft = max(0, $now->diffInMinutes($cutoff, false));
            $hrs = intdiv($minutesLeft, 60);
            $mins = $minutesLeft % 60;
            $countdownLabel = "Order in {$hrs} hrs {$mins} mins";
        }

        $expressUpsell = null;
        if (!$method->is_express_type && !$cutoffPassed && $shippingMethods && $categoryId && $categoryDefaultMethodId) {
            $expressAlternative = $shippingMethods->first(fn ($candidate) => $candidate->is_express_type && $candidate->id !== $method->id);
            if ($expressAlternative) {
                $expressUpsell = [
                    'message_en' => "Order in the next {$countdownLabel} for faster delivery with {$expressAlternative->badge_label_en}",
                    'message_ar' => null,
                ];
            }
        }

        return [
            'day_label_en' => $dayLabelEn,
            'day_label_ar' => $dayLabelAr,
            'earliest_date' => $earliestAt->toDateString(),
            'latest_date' => $latestAt->toDateString(),
            'countdown_label' => $countdownLabel,
            'cutoff_passed' => $cutoffPassed,
            'express_upsell' => $expressUpsell,
        ];
    }

    private function buildFlashSaleShape(?FlashSaleSubmission $submission): ?array
    {
        if (!$submission) {
            return null;
        }

        return [
            'active' => true,
            'flash_price' => (int) $submission->flash_price,
            'original_price' => (int) $submission->original_price,
            'discount_pct' => (float) $submission->calculated_discount_pct,
            'ends_at' => optional($submission->flashSale?->sale_ends_at)->toIso8601String(),
            'max_quantity_per_customer' => $submission->max_quantity_per_customer,
            'quantity_remaining' => (int) $submission->quantity_remaining,
        ];
    }

    /**
     * @return array{id:string,code:string,title_en:?string,title_ar:?string,type:string,value:mixed,label_en:string,label_ar:?string}
     */
    private function eligibleCouponsForItem($listing, Collection $activeCoupons, Collection $usedCouponCounts, ?string $categoryId): array
    {
        return $activeCoupons->filter(function (Coupon $coupon) use ($listing, $usedCouponCounts, $categoryId) {
            $usedCount = $usedCouponCounts->get($coupon->id, 0);
            if ($usedCount >= $coupon->usage_limit_per_customer) {
                return false;
            }

            return match ($coupon->scope) {
                CouponScope::Platform => true,
                CouponScope::Vendor => $coupon->vendor_id === $listing->vendor_id,
                CouponScope::Category => $categoryId !== null && $coupon->category_id === $categoryId,
                CouponScope::Product => false,
                default => false,
            };
        })->map(fn (Coupon $coupon) => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'title_en' => $coupon->title_en,
            'title_ar' => $coupon->title_ar,
            'type' => $coupon->type->value,
            'value' => $coupon->value,
            'label_en' => $this->couponLabel($coupon),
            'label_ar' => null,
        ])->values()->all();
    }

    private function couponLabel(Coupon $coupon): string
    {
        $value = rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.');

        return match ($coupon->type) {
            CouponType::Percentage => "Extra {$value}% off",
            CouponType::FixedAmount => "Extra {$value} " . ($coupon->currency ?? '') . ' off',
            CouponType::FreeShipping => 'Free Shipping',
            CouponType::Bogo => 'Buy One Get One',
            default => $coupon->title_en ?? $coupon->name,
        };
    }

    /**
     * @return array{available_quantity:?int,is_low_stock:bool,is_out_of_stock:bool,exceeds_stock:bool,low_stock_label_en:?string,low_stock_label_ar:?string}
     */
    private function stockStatus($listing, bool $isAdminListing, ?int $available, int $cartQty): array
    {
        if ($isAdminListing || $available === null) {
            return [
                'available_quantity' => null,
                'is_low_stock' => false,
                'is_out_of_stock' => false,
                'exceeds_stock' => false,
                'low_stock_label_en' => null,
                'low_stock_label_ar' => null,
            ];
        }

        $available = max(0, $available);
        $threshold = (int) ($listing->low_stock_threshold ?? 0);
        $isLowStock = $available > 0 && $available <= $threshold;
        $isOutOfStock = $available === 0;
        $exceedsStock = $cartQty > $available;

        return [
            'available_quantity' => $available,
            'is_low_stock' => $isLowStock,
            'is_out_of_stock' => $isOutOfStock,
            'exceeds_stock' => $exceedsStock,
            'low_stock_label_en' => $isLowStock ? "Only {$available} left in stock" : null,
            'low_stock_label_ar' => null,
        ];
    }

    /**
     * @return array{has_warranty_plan:bool,label_en:?string,label_ar:?string,plans:array}
     */
    private function buildWarrantyBadge(Collection $plans): array
    {
        if ($plans->isEmpty()) {
            return [
                'has_warranty_plan' => false,
                'label_en' => null,
                'label_ar' => null,
                'plans' => [],
            ];
        }

        $shortestDuration = $plans->min('duration_months');
        $label = $shortestDuration >= 12
            ? intdiv($shortestDuration, 12) . ' year warranty'
            : $shortestDuration . ' month warranty';

        return [
            'has_warranty_plan' => true,
            'label_en' => $label,
            'label_ar' => null,
            'plans' => $plans->map(fn (WarrantyPlan $plan) => [
                'id' => $plan->id,
                'name_en' => $plan->name_en,
                'name_ar' => $plan->name_ar,
                'duration_months' => $plan->duration_months,
                'price' => (int) $plan->price,
                'currency' => $plan->currency,
            ])->values()->all(),
        ];
    }

    private function resolveRootCategoryId(?string $categoryId, Collection $parentMap): ?string
    {
        if (!$categoryId) {
            return null;
        }

        $current = $categoryId;
        $visited = [];

        while (true) {
            if (isset($visited[$current])) {
                // Cyclic parent_id chain — bail out rather than loop forever.
                return $current;
            }
            $visited[$current] = true;

            $parent = $parentMap->get($current);
            if (!$parent) {
                return $current;
            }

            $current = $parent;
        }
    }

    private function primaryImageUrl($variant): ?string
    {
        $image = $variant->images->firstWhere('is_primary', true) ?? $variant->images->first();

        return $image?->url;
    }
}
