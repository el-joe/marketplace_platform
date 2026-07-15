<?php

namespace App\Services\Customer;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Country;
use App\Models\CountryPaymentMethod;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBestsellerRanking;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\VendorListing;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductDetailEnrichmentService
{
    // ── Section 1: best_seller_badge ────────────────────────────────────────

    public function getBestSellerBadge(Product $product, Country $country): ?array
    {
        $badge = $this->cacheRememberTagged(
            "product_bestseller_badge:{$product->id}:{$country->id}",
            1800,
            ['bestseller'],
            function () use ($product, $country) {
                $ranking = ProductBestsellerRanking::query()
                    ->join('categories', 'categories.id', '=', 'product_bestseller_rankings.category_id')
                    ->where('product_bestseller_rankings.product_id', $product->id)
                    ->where('product_bestseller_rankings.country_id', $country->id)
                    ->where('product_bestseller_rankings.period', 'monthly')
                    ->where('product_bestseller_rankings.rank', '<=', 3)
                    ->orderBy('product_bestseller_rankings.rank')
                    ->select([
                        'product_bestseller_rankings.rank',
                        'categories.name_en',
                        'categories.name_ar',
                        'categories.slug',
                    ])
                    ->first();

                if (!$ranking) {
                    return null;
                }

                return [
                    'rank' => (int) $ranking->rank,
                    'category_name_en' => $ranking->name_en,
                    'category_name_ar' => $ranking->name_ar,
                    'category_slug' => $ranking->slug,
                    'link_url' => "/browse/product/{$ranking->slug}",
                ];
            },
        );

        return $badge;
    }

    // ── Section 2: delivery_options ─────────────────────────────────────────

    public function getDeliveryOptions(
        Product $product,
        Country $country,
        ?string $customerAddressId = null,
        ?VendorListing $buyBoxListing = null,
    ): array {
        $buyBoxListing ??= $product->relationLoaded('activeListings')
            ? $product->activeListings->first()
            : null;

        if (!$buyBoxListing) {
            return [];
        }

        $zoneId = $this->resolveDestinationZoneId($country, $customerAddressId);

        if (!$zoneId) {
            return [];
        }

        $cacheKey = "product_delivery_options:{$product->id}:{$country->id}:{$zoneId}:{$buyBoxListing->id}";

        return Cache::remember($cacheKey, 300, function () use ($buyBoxListing, $zoneId) {
            $variant = $buyBoxListing->productVariant;
            $weightGrams = (int) ceil((($variant?->weight_grams ?? 0) / 100)) * 100;

            $rates = ShippingRate::query()
                ->where('destination_zone_id', $zoneId)
                ->where('is_active', true)
                ->with(['shippingMethod' => fn($q) => $q->where('is_active', true)])
                ->get()
                ->filter(fn(ShippingRate $rate) => $rate->shippingMethod !== null)
                ->sortBy(fn(ShippingRate $rate) => $rate->shippingMethod->display_priority);

            $options = [];

            foreach ($rates as $rate) {
                $method = $rate->shippingMethod;

                $feeCents = $rate->base_fee + (int) round(($weightGrams / 1000) * $rate->rate_per_kg);
                $isFree = $rate->free_shipping_threshold !== null && $feeCents <= $rate->free_shipping_threshold;
                if ($isFree) {
                    $feeCents = 0;
                }

                $options[] = [
                    'shipping_method_id' => $method->id,
                    'code' => $method->code,
                    'name' => ['ar' => $method->name, 'en' => $method->name],
                    'badge_label' => ['ar' => $method->badge_label_ar, 'en' => $method->badge_label_en],
                    'is_express' => (bool) $method->is_express_type,
                    'is_primary' => $method->id === $buyBoxListing->primary_shipping_method_id,
                    'shipping_fee_cents' => $feeCents,
                    'is_free' => $isFree,
                    'estimated_delivery_date' => $this->estimatedDeliveryDate($method)->toDateString(),
                    'order_before_seconds' => $this->orderBeforeSeconds($method),
                ];
            }

            return $options;
        });
    }

    private function resolveDestinationZoneId(Country $country, ?string $customerAddressId): ?string
    {
        if ($customerAddressId) {
            $address = Address::with('city')->find($customerAddressId);
            if ($address?->city?->shipping_zone_id) {
                return $address->city->shipping_zone_id;
            }
        }

        // No explicit "default zone" flag exists in the schema; fall back to the
        // country's first active shipping zone for guests/addressless requests.
        return ShippingZone::where('country_id', $country->id)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->value('id');
    }

    private function estimatedDeliveryDate(\App\Models\ShippingMethod $method): \Carbon\Carbon
    {
        $days = (int) $method->min_delivery_days;

        if ($method->order_cutoff_time && now()->format('H:i:s') > $method->order_cutoff_time) {
            $days++;
        }

        return now()->addDays($days)->startOfDay();
    }

    private function orderBeforeSeconds(\App\Models\ShippingMethod $method): ?int
    {
        if (!$method->order_cutoff_time) {
            return null;
        }

        $cutoffToday = now()->copy()->setTimeFromTimeString($method->order_cutoff_time);

        if (now()->greaterThan($cutoffToday)) {
            $cutoffToday->addDay();
        }

        return max(0, (int) now()->diffInSeconds($cutoffToday, false));
    }

    // ── Section 3: coupons ───────────────────────────────────────────────────

    public function getApplicableCoupons(
        Product $product,
        Country $country,
        ?Customer $customer,
        ?VendorListing $buyBoxListing = null,
    ): array {
        $buyBoxListing ??= $product->relationLoaded('activeListings')
            ? $product->activeListings->first()
            : null;

        // dd($buyBoxListing);

        $couponsCacheVersion = Cache::get('product_coupons:version', 1);
        $cacheKey = "product_coupons:v{$couponsCacheVersion}:{$product->id}:{$country->id}:" . ($customer?->id ?? 'guest');

        $coupons = Cache::remember($cacheKey, 120, function () use ($product, $customer, $buyBoxListing) {
            $productCategory = $product->category;

            $query = Coupon::query()
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->where(function ($q) {
                    $q->whereNull('usage_limit_total')
                        ->orWhereColumn('times_used', '<', 'usage_limit_total');
                })
                ->where(function ($q) use ($product, $productCategory, $buyBoxListing) {
                    $q->where('scope', 'platform');

                    if ($productCategory && $productCategory->lft !== null && $productCategory->rgt !== null) {
                        $q->orWhere(function ($qq) use ($productCategory) {
                            $qq->where('scope', 'category')
                                ->whereHas('category', fn($cq) => $cq
                                    ->where('lft', '<=', $productCategory->lft)
                                    ->where('rgt', '>=', $productCategory->rgt));
                        });
                    }

                    if ($buyBoxListing) {
                        $q->orWhere(function ($qq) use ($buyBoxListing) {
                            $qq->where('scope', 'vendor')
                                ->where('vendor_id', $buyBoxListing->vendor_id);
                        });
                    }

                    $q->orWhere(function ($qq) use ($product) {
                        $qq->where('scope', 'product')
                            ->whereHas('products', fn($pq) => $pq->where('products.id', $product->id));
                    });
                });

            if ($customer) {
                $query->where(function ($q) use ($customer) {
                    $q->whereNull('usage_limit_per_customer')
                        ->orWhereRaw('(
                            SELECT COUNT(*) FROM coupon_usages
                            WHERE coupon_usages.coupon_id = coupons.id
                            AND coupon_usages.customer_id = ?
                        ) < coupons.usage_limit_per_customer', [$customer->id]);
                });
            }

            return $query->limit(20)->pluck('id')->toArray();
        });

        $coupons = Coupon::whereIn('id', $coupons)->get();

        $price = $buyBoxListing->price ?? 0;
        $cheapestShippingFee = $this->cheapestShippingFeeEstimate($buyBoxListing);

        $withSavings = $coupons->map(function (Coupon $coupon) use ($price, $cheapestShippingFee) {
            $saving = $this->estimatedSavingCents($coupon, $price, $cheapestShippingFee);

            return [
                'coupon' => $coupon,
                'saving' => $saving,
            ];
        })
            ->sortBy(fn($row) => [-$row['saving'], $row['coupon']->valid_until->timestamp])
            ->take(3);

        return $withSavings->map(function ($row) {
            $coupon = $row['coupon'];

            return [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'type' => $coupon->type->value,
                'estimated_saving_cents' => $row['saving'],
                'valid_until' => $coupon->valid_until->toIso8601String(),
                'learn_more_url' => "/coupons/{$coupon->code}",
                'is_bank_offer' => $coupon->isBankOffer(),
                'bank_name' => $coupon->bank_name,
                'title' => [
                    'ar' => $coupon->title_ar,
                    'en' => $coupon->title_en,
                ],
                'terms' => [
                    'ar' => $coupon->terms_ar,
                    'en' => $coupon->terms_en,
                ],
                'max_orders_per_customer_per_month' => $coupon->max_orders_per_customer_per_month,
            ];
        })->values()->all();
    }

    private function estimatedSavingCents(Coupon $coupon, int $priceCents, int $cheapestShippingFeeCents): int
    {
        return match ($coupon->type->value) {
            'percentage' => min(
                (int) floor($priceCents * (float) $coupon->value / 100),
                $coupon->max_discount ?? PHP_INT_MAX,
            ),
            'fixed_amount' => (int) $coupon->value,
            'free_shipping' => $cheapestShippingFeeCents,
            'bogo' => (int) floor($priceCents / 2),
            default => 0,
        };
    }

    private function cheapestShippingFeeEstimate(?VendorListing $buyBoxListing): int
    {
        if (!$buyBoxListing || !$buyBoxListing->country_id) {
            return 0;
        }

        $rate = ShippingRate::where('is_active', true)
            ->whereHas('destinationZone', fn($q) => $q->where('country_id', $buyBoxListing->country_id))
            ->orderBy('base_fee')
            ->first();

        return $rate?->base_fee ?? 0;
    }

    // ── Section 4: payment_options ──────────────────────────────────────────

    public function getPaymentOptions(Country $country, int $productPriceCents, ?Customer $customer): array
    {
        $methods = $this->cacheRememberTagged(
            "country_payment_methods_pdp:{$country->id}",
            600,
            ['payment_methods'],
            fn() => CountryPaymentMethod::where('country_id', $country->id)
                ->where('is_active', true)
                ->whereIn('method_type', ['bnpl', 'wallet'])
                ->orderBy('sort_order')
                ->pluck('id')->toArray(),
        );

        $options = [];
        $methods = CountryPaymentMethod::whereIn('id', $methods)->get();

        foreach ($methods as $method) {
            if ($method->min_order_cents > 0 && $method->min_order_cents > $productPriceCents) {
                continue;
            }
            if ($method->max_order_cents !== null && $method->max_order_cents < $productPriceCents) {
                continue;
            }

            if ($method->method_type === 'bnpl') {
                $installmentAmountCents = (int) floor($productPriceCents / max(1, $method->installments_count));
                $formattedAmount = number_format($installmentAmountCents / 100, 2);

                $label = $method->installment_label_en ?? 'Pay in {n} interest-free installments of {amount}';
                $label = str_replace(
                    ['{n}', '{amount}'],
                    [$method->installments_count, $formattedAmount],
                    $label,
                );

                $options[] = [
                    'method_type' => 'bnpl',
                    'provider' => $method->provider,
                    'display_name' => ['ar' => $method->display_name_ar, 'en' => $method->display_name_en],
                    'installments_count' => $method->installments_count,
                    'installment_amount_cents' => $installmentAmountCents,
                    'label' => $label,
                    'provider_logo_path' => $method->provider_logo_path,
                    'learn_more_url' => $method->learn_more_url,
                ];
            } elseif ($method->method_type === 'wallet') {
                if (!$customer) {
                    continue;
                }

                $wallet = Wallet::where('owner_type', 'customer')
                    ->where('owner_id', $customer->id)
                    ->first();

                $options[] = [
                    'method_type' => 'wallet',
                    'provider' => $method->provider,
                    'display_name' => ['ar' => $method->display_name_ar, 'en' => $method->display_name_en],
                    'balance_cents' => $wallet?->balance_cents,
                ];
            }
        }

        return $options;
    }

    // ── Shared cache helper ──────────────────────────────────────────────────

    /**
     * Cache::tags() is not supported by the 'database' cache driver configured in
     * this project (CACHE_STORE=database) — only by taggable stores like
     * redis/memcached. Fall back to a plain (untagged) remember() when tags aren't
     * supported; tags will kick in for free once the cache store is switched.
     */
    private function cacheRememberTagged(string $key, int $ttl, array $tags, \Closure $callback): mixed
    {
        $store = Cache::getStore();

        if ($store instanceof \Illuminate\Cache\TaggableStore) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }
}
