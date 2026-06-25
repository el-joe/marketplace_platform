<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Classified\CreateInquiryRequest;
use App\Http\Requests\Customer\Travel\CreateBookingRequest;
use App\Http\Resources\Customer\ClassifiedListingDetailResource;
use App\Http\Resources\Customer\TravelPackageDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Services\Customer\ClassifiedDetailService;
use App\Services\Customer\ClassifiedInquiryService;
use App\Services\Customer\TravelBookingService;
use App\Services\Customer\TravelPackageDetailService;
use App\Services\Customer\BuyBoxService;
use App\Services\Customer\ProductViewService;
use App\Models\Product;
use App\Models\Wishlist;
use App\Http\Resources\Customer\ProductDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    public function __construct(
        private readonly ClassifiedDetailService $classifiedDetail,
        private readonly TravelPackageDetailService $travelDetail,
        private readonly ClassifiedInquiryService $inquiryService,
        private readonly TravelBookingService $bookingService,
        private readonly BuyBoxService $buyBox,
        private readonly ProductViewService $viewService,
    ) {}

    public function show(Request $request, Country $country, string $type, string $slug): JsonResponse
    {
        return match ($type) {
            'product'    => $this->showProduct($request, $country, $slug),
            'classified' => $this->showClassified($request, $country, $slug),
            'travel'     => $this->showTravel($request, $country, $slug),
            default      => throw new NotFoundHttpException('Unknown listing type.'),
        };
    }

    public function createInquiry(
        CreateInquiryRequest $request,
        Country $country,
        string $listingNumber,
    ): JsonResponse {
        $listing = $this->classifiedDetail->findActive($listingNumber, $country);

        abort_if(! $listing, 404, 'Listing not found or no longer active.');

        /** @var \App\Models\Customer $customer */
        $customer = auth('customer')->user();
        $inquiry  = $this->inquiryService->create($listing, $customer, $request->validated());

        return ApiResponse::success([
            'id'             => $inquiry->id,
            'listing_number' => $listingNumber,
            'status'         => $inquiry->status,
            'created_at'     => $inquiry->created_at->toIso8601String(),
        ], 'Inquiry submitted.', 201);
    }

    public function createBooking(
        CreateBookingRequest $request,
        Country $_country,
        string $packageId,
    ): JsonResponse {
        $package = $this->travelDetail->findActive($packageId);

        if (! $package) {
            return ApiResponse::notFound('Travel package not found, expired, or no longer active.');
        }

        /** @var \App\Models\Customer $customer */
        $customer = auth('customer')->user();
        $booking  = $this->bookingService->book($package, $customer, $request->validated());

        return ApiResponse::success([
            'id'             => $booking->id,
            'booking_number' => $booking->booking_number,
            'status'         => $booking->status,
            'travelers_count' => $booking->travelers_count,
            'total_price_cents' => $booking->total_price_cents,
            'currency'       => $package->currency,
            'created_at'     => $booking->created_at->toIso8601String(),
        ], 'Booking created.', 201);
    }

    // ── Private branch methods ────────────────────────────────────────────────

    private function showProduct(Request $request, Country $country, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('countrySettings', fn ($q) => $q
                ->where('country_id', $country->id)
                ->where('is_available', true)
            )
            ->with([
                'brand',
                'category',
                'images',
                'variants.variantAttributes.attribute',
                'variants.variantAttributes.attributeValue',
                'countrySettings' => fn ($q) => $q->where('country_id', $country->id),
            ])
            ->firstOrFail();

        $listings = $this->buyBox->getListings($product, $country);
        $product->setRelation('activeListings', $listings);

        $reviews = $product->reviews()
            ->where('status', 'published')
            ->with('vendorReply', 'customer:id,name')
            ->orderByDesc('helpful_count')
            ->limit(5)
            ->get();
        $product->setRelation('topReviews', $reviews);

        $buyBoxPrice = $listings->first()?->price;
        $related     = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->whereHas('countrySettings', fn ($q) => $q
                ->where('country_id', $country->id)
                ->where('is_available', true)
            )
            ->with('images')
            ->when($buyBoxPrice, fn ($q) => $q->whereHas('variants.vendorListings', fn ($q2) => $q2
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->whereBetween('price', [(int) ($buyBoxPrice * 0.7), (int) ($buyBoxPrice * 1.3)])
            ))
            ->orderByDesc('rating_avg')
            ->limit(8)
            ->get();
        $product->setRelation('related', $related);

        $isWishlisted = false;
        if ($customerId = auth('customer')->id()) {
            $isWishlisted = Wishlist::where('customer_id', $customerId)
                ->where('product_id', $product->id)
                ->exists();
        }

        $this->viewService->logView(
            product: $product,
            country: $country,
            customerId: auth('customer')->id(),
            sessionId: $request->session()->getId() ?? '',
            source: $request->query('source', 'direct'),
            referrerUrl: $request->header('Referer'),
        );

        $resource              = new ProductDetailResource($product);
        $resource->isWishlisted = $isWishlisted;

        return ApiResponse::success($resource->toArray($request));
    }

    private function showClassified(Request $request, Country $country, string $listingNumber): JsonResponse
    {
        $listing = $this->classifiedDetail->findActive($listingNumber, $country);

        abort_if(! $listing, 404, 'Listing not found or no longer active.');

        $this->classifiedDetail->incrementViews($listing);

        $resource             = new ClassifiedListingDetailResource($listing);
        $resource->sellerInfo = $this->classifiedDetail->sellerInfo($listing);

        return ApiResponse::success($resource->toArray($request));
    }

    private function showTravel(Request $request, Country $_country, string $id): JsonResponse
    {
        $package = $this->travelDetail->findActive($id);

        abort_if(! $package, 404, 'Travel package not found, expired, or no longer active.');

        return ApiResponse::success((new TravelPackageDetailResource($package))->toArray($request));
    }
}
