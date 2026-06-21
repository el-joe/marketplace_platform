<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CreateListingRequest;
use App\Http\Requests\Vendor\ListingIndexRequest;
use App\Http\Requests\Vendor\UpdateListingPriceRequest;
use App\Http\Requests\Vendor\UpdateListingStatusRequest;
use App\Http\Resources\Vendor\VendorListingResource;
use App\Http\Responses\ApiResponse;
use App\Models\VendorListing;
use App\Services\Vendor\ListingService;
use Illuminate\Support\Facades\Gate;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listingService) {}

    public function index(ListingIndexRequest $request): \Illuminate\Http\JsonResponse
    {
        $vendorId = auth('vendor')->user()->vendor_id;

        $query = VendorListing::where('vendor_id', $vendorId)
            ->with(['productVariant.product', 'country'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->whereHas('productVariant.product', function ($pq) use ($request) {
                $pq->where('name_en', 'like', "%{$request->search}%")
                   ->orWhere('name_ar', 'like', "%{$request->search}%");
            }))
            ->latest();

        $listings = $query->paginate((int) ($request->per_page ?? 20));

        return ApiResponse::paginated($listings, VendorListingResource::class);
    }

    public function show(string $id): \Illuminate\Http\JsonResponse
    {
        $listing = VendorListing::with(['productVariant.product', 'country'])->findOrFail($id);

        Gate::authorize('view', $listing);

        return ApiResponse::success(new VendorListingResource($listing));
    }

    public function store(CreateListingRequest $request): \Illuminate\Http\JsonResponse
    {
        $vendor  = auth('vendor')->user()->vendor;
        $listing = $this->listingService->create($request->validated(), $vendor);

        return ApiResponse::success(
            new VendorListingResource($listing->load(['productVariant.product', 'country'])),
            'Listing submitted for review.',
            201
        );
    }

    public function updatePrice(UpdateListingPriceRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        $listing = VendorListing::findOrFail($id);

        Gate::authorize('updatePrice', $listing);

        $updated = $this->listingService->updatePrice($listing, $request->validated());

        return ApiResponse::success(new VendorListingResource($updated), 'Price updated.');
    }

    public function updateStatus(UpdateListingStatusRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        $listing = VendorListing::findOrFail($id);

        Gate::authorize('updateStatus', $listing);

        if (! in_array($listing->status, ['active', 'paused'])) {
            return ApiResponse::error('Only active or paused listings can have their status changed by the vendor.', [], 422);
        }

        $updated = $this->listingService->updateStatus($listing, $request->status);

        return ApiResponse::success(new VendorListingResource($updated), 'Status updated.');
    }

    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $listing = VendorListing::findOrFail($id);

        Gate::authorize('delete', $listing);

        $this->listingService->delete($listing);

        return ApiResponse::success(null, 'Listing archived.');
    }
}
