<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\AdminProductListingStatus;
use App\Enums\VendorListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\AdminListingResource;
use App\Http\Resources\Api\Customer\VendorListingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Address;
use App\Models\AdminProductListing;
use App\Models\Country;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\VendorListing;
use App\Services\Customer\ListingIdentifierService;
use App\Services\ListingModeResolver;
use App\Services\ShippingCalculationService;
use App\Services\WarrantyPlanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(
        private readonly ListingIdentifierService $identifiers,
        private readonly ShippingCalculationService $shipping,
        private readonly WarrantyPlanService $warrantyPlanService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $isNawyNow = ListingModeResolver::isNawyNow($request);
        $country = $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        $perPage = (int) $request->query('per_page', 20);

        $paginator = $isNawyNow
            ? $this->buildAdminQuery($request, $country)->paginate($perPage)
            : $this->buildVendorQuery($request, $country)->paginate($perPage);

        $items = $isNawyNow
            ? $paginator->getCollection()->map(fn (AdminProductListing $listing) => $this->adminListingShape($listing, $country))->values()->all()
            : $paginator->getCollection()->map(fn (VendorListing $listing) => $this->vendorListingShape($listing, $country))->values()->all();

        return ApiResponse::success([
            'items' => $items,
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $isNawyNow = ListingModeResolver::isNawyNow($request);
        $country = $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        if ($isNawyNow) {
            $listing = $this->resolveAdminListing($identifier, $country);

            if (!$listing) {
                return ApiResponse::error('Listing not found or not available in this country.', [], 404);
            }

            return ApiResponse::success([
                'listing' => $this->adminListingShape($listing, $country),
                'delivery_info' => $this->adminDeliveryInfo($listing),
            ]);
        }

        $type = $this->identifiers->detectType($identifier);
        $listing = $this->identifiers->resolve($identifier, $type, $country);

        if (!$listing) {
            return ApiResponse::error('Listing not found or not available in this country.', [], 404);
        }

        $address = $this->resolveCustomerDefaultAddress($request);

        return ApiResponse::success([
            'listing' => $this->vendorListingShape($listing, $country),
            'delivery_info' => $this->vendorDeliveryInfo($listing, $country, $address),
            'warranty_plans' => $this->warrantyPlansFor($listing, $country),
        ]);
    }

    public function shippingEstimate(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'address_id' => ['required', 'uuid'],
            'shipping_method_id' => ['required', 'uuid'],
        ]);

        $country = $this->resolveCountry($request);

        if (!$country) {
            return ApiResponse::error('Country not found or not active.', [], 404);
        }

        $listing = VendorListing::where('id', $id)
            ->where('country_id', $country->id)
            ->where('status', VendorListingStatus::Active->value)
            ->whereNull('deleted_at')
            ->with(['vendor', 'productVariant'])
            ->first();

        if (!$listing) {
            return ApiResponse::error('Listing not found or not available in this country.', [], 404);
        }

        $address = Address::with('city')->find($request->input('address_id'));
        $zone = $address?->city?->shipping_zone_id
            ? ShippingZone::find($address->city->shipping_zone_id)
            : null;

        if (!$zone) {
            return ApiResponse::error('Unable to resolve a shipping zone for this address.', [], 422);
        }

        $method = ShippingMethod::find($request->input('shipping_method_id'));

        if (!$method) {
            return ApiResponse::error('Shipping method not found.', [], 404);
        }

        $weightGrams = $this->effectiveWeightGrams($listing);

        $breakdown = $this->shipping->calculate(
            vendor: $listing->vendor,
            listing: $listing,
            quantity: 1,
            destinationZone: $zone,
            method: $method,
            billableWeightGrams: $weightGrams,
        );

        return ApiResponse::success([
            'fee' => $breakdown->customerPays,
            'cod_fee' => null,
            'is_exceptional' => $breakdown->isExceptional,
            'is_free_shipping' => $breakdown->customerPays === 0,
            'currency' => $country->currency_code,
            'method_details' => [
                'id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'delivery_days_min' => $method->min_delivery_days,
                'delivery_days_max' => $method->max_delivery_days,
            ],
        ]);
    }

    // ── Query builders ───────────────────────────────────────────────────────

    private function buildAdminQuery(Request $request, Country $country): Builder
    {
        $query = AdminProductListing::query()
            ->where('country_id', $country->id)
            ->where('status', AdminProductListingStatus::Active->value)
            ->where('featured_in_nawy', 1)
            ->with([
                'productVariant.product.images',
                'productVariant.product.category',
            ]);

        if ($categoryId = $request->query('category_id')) {
            $query->where('nawy_category_id', $categoryId);
        }

        if ($minPrice = $request->query('min_price')) {
            $query->where('price', '>=', (int) $minPrice);
        }

        if ($maxPrice = $request->query('max_price')) {
            $query->where('price', '<=', (int) $maxPrice);
        }

        $this->applyAdminSort($query, $request->query('sort'));

        return $query;
    }

    private function applyAdminSort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderByDesc('id'),
            'rating' => $query->orderByDesc('rating_avg'),
            default => $query->orderByDesc('id'),
        };
    }

    private function buildVendorQuery(Request $request, Country $country): Builder
    {
        $query = VendorListing::query()
            ->where('country_id', $country->id)
            ->where('status', VendorListingStatus::Active->value)
            ->whereNull('deleted_at')
            ->with([
                'productVariant.product.images',
                'productVariant.product.category',
            ]);

        if ($categoryId = $request->query('category_id')) {
            $query->whereHas('productVariant.product', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($condition = $request->query('condition')) {
            $query->where('condition', $condition);
        }

        if ($minPrice = $request->query('min_price')) {
            $query->where('price', '>=', (int) $minPrice);
        }

        if ($maxPrice = $request->query('max_price')) {
            $query->where('price', '<=', (int) $maxPrice);
        }

        $this->applyVendorSort($query, $request->query('sort'));

        return $query;
    }

    private function applyVendorSort(Builder $query, ?string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderByDesc('created_at'),
            'rating' => $query->orderByDesc('rating_avg'),
            default => $query->orderByDesc('score')->orderByDesc('buy_box_won_at'),
        };
    }

    // ── Detail resolution ────────────────────────────────────────────────────

    private function resolveAdminListing(string $identifier, Country $country): ?AdminProductListing
    {
        $query = AdminProductListing::where('country_id', $country->id)
            ->where('status', AdminProductListingStatus::Active->value)
            ->where('featured_in_nawy', 1)
            ->with(['productVariant.product.images', 'productVariant.product.category']);

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            return $query->where('id', $identifier)->first();
        }

        $variant = ProductVariant::where('sku', $identifier)->first();

        if ($variant) {
            return $query->where('product_variant_id', $variant->id)->first();
        }

        return $query->whereHas('productVariant.product', fn ($q) => $q->where('slug', $identifier))->first();
    }

    // ── Delivery info ────────────────────────────────────────────────────────

    private function vendorDeliveryInfo(VendorListing $listing, Country $country, ?Address $address): array
    {
        $category = $listing->productVariant->product->category;

        if (!$category) {
            return ['methods' => []];
        }

        $zone = null;

        if ($address?->city?->shipping_zone_id) {
            $zone = ShippingZone::find($address->city->shipping_zone_id);
        }

        $weightGrams = $this->effectiveWeightGrams($listing);

        // Availability column depends on which system fulfills this listing.
        $availabilityColumn = $listing->global_system_type?->value === 'express_fbn'
            ? 'category_shipping_methods.is_available_for_express_fbn'
            : 'category_shipping_methods.is_available_for_merchant_fbp';

        $methods = $category->shippingMethods()
            ->where('shipping_methods.is_active', 1)
            ->where($availabilityColumn, 1)
            ->orderBy('shipping_methods.display_priority')
            ->get();

        $shaped = $methods->map(function (ShippingMethod $method) use ($listing, $zone, $weightGrams) {
            $estimatedFee = null;
            $isExceptional = false;

            if ($zone) {
                $breakdown = $this->shipping->calculate(
                    vendor: $listing->vendor,
                    listing: $listing,
                    quantity: 1,
                    destinationZone: $zone,
                    method: $method,
                    billableWeightGrams: $weightGrams,
                );

                $estimatedFee = $breakdown->customerPays;
                $isExceptional = $breakdown->isExceptional;
            }

            return [
                'method_id' => $method->id,
                'code' => $method->code,
                'name' => $method->name,
                'badge_label' => ['ar' => $method->badge_label_ar, 'en' => $method->badge_label_en],
                'badge_color_hex' => $method->badge_color_hex,
                'badge_text_color_hex' => $method->badge_text_color_hex,
                'delivery_label' => ['ar' => $method->delivery_label_ar, 'en' => $method->delivery_label_en],
                'delivery_days_min' => $method->min_delivery_days,
                'delivery_days_max' => $method->max_delivery_days,
                'handling_time_hours' => $method->handling_time_hours,
                'is_express_type' => $method->is_express_type,
                'is_default' => (bool) $method->pivot->is_default,
                'show_estimated_price' => (bool) $method->show_estimated_price,
                'estimated_fee' => $estimatedFee,
                'is_exceptional' => $isExceptional,
            ];
        })->values()->all();

        return [
            'currency' => $country->currency_code,
            'methods' => $shaped,
        ];
    }

    private function adminDeliveryInfo(AdminProductListing $listing): array
    {
        return [
            'shipping_cost' => $listing->shipping_cost,
            'currency' => $listing->currency,
            'fulfillment_type' => $listing->fulfillment_type,
            'payment_options' => $listing->payment_options,
        ];
    }

    private function warrantyPlansFor(VendorListing $listing, Country $country): array
    {
        $product = $listing->productVariant->product;

        if (!$product->category_id) {
            return [];
        }

        $plans = $this->warrantyPlanService->getPlansForProduct($product, $country->id, $country->currency_code);

        return array_values(array_filter($plans, fn (array $plan) => $plan['currency'] === $country->currency_code));
    }

    private function effectiveWeightGrams(VendorListing $listing): int
    {
        $length = $listing->declared_length_cm ?? $listing->productVariant->length_cm ?? 0;
        $width = $listing->declared_width_cm ?? $listing->productVariant->width_cm ?? 0;
        $height = $listing->declared_height_cm ?? $listing->productVariant->height_cm ?? 0;
        $weight = $listing->declared_weight_grams ?? $listing->productVariant->weight_grams ?? 0;

        return $this->shipping->resolveEffectiveWeightGrams(
            (int) $length,
            (int) $width,
            (int) $height,
            (int) $weight,
        );
    }

    // ── Shapes ───────────────────────────────────────────────────────────────

    private function vendorListingShape(VendorListing $listing, Country $country): array
    {
        return (new VendorListingResource($listing, $country))->toArray(request());
    }

    private function adminListingShape(AdminProductListing $listing, Country $country): array
    {
        return (new AdminListingResource($listing, $country))->toArray(request());
    }

    // ── Shared helpers ───────────────────────────────────────────────────────

    private function resolveCountry(Request $request): ?Country
    {
        $country = $request->attributes->get('country');

        if ($country instanceof Country) {
            return $country;
        }

        $customerId = auth('customer')->id();

        if ($customerId) {
            return auth('customer')->user()?->country;
        }

        return null;
    }

    private function resolveCustomerDefaultAddress(Request $request): ?Address
    {
        $customer = auth('customer')->user();

        if (!$customer) {
            return null;
        }

        return Address::with('city')
            ->where('addressable_type', get_class($customer))
            ->where('addressable_id', $customer->id)
            ->where('is_default', true)
            ->first();
    }
}
