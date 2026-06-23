<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SearchRequest;
use App\Http\Resources\Customer\ProductListResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\Wishlist;
use App\Services\Customer\SearchService;
use App\Services\Customer\SponsoredProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $search,
        private readonly SponsoredProductService $sponsored,
    ) {}

    public function search(SearchRequest $request, Country $country): JsonResponse
    {
        $data    = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 20);
        $page    = (int) ($data['page'] ?? 1);

        $paginator = $this->search->search(
            country: $country,
            query: $data['q'],
            filters: $data,
            perPage: $perPage,
            customerId: auth('customer')->id(),
            sessionId: $request->session()->getId() ?? '',
        );

        $wishlistIds = $this->wishlistIds();

        $items = ProductListResource::collection($paginator->load('images'))
            ->map(function (ProductListResource $r) use ($wishlistIds) {
                $r->resource->is_sponsored  = false;
                $r->resource->is_wishlisted = in_array($r->resource->id, $wishlistIds);
                return $r->toArray(request());
            })
            ->toArray();

        $items = $this->sponsored->inject($items, $country, $page, 'search_results', $data['q']);

        $facets = $this->search->facets($country, $data);

        return response()->json([
            'success' => true,
            'data'    => [
                'items'  => $items,
                'facets' => $facets,
                'meta'   => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
        ]);
    }

    public function suggestions(Request $request, Country $country): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:1', 'max:255']]);

        $results = $this->search->suggestions($country, $request->query('q'));

        return ApiResponse::success($results);
    }

    private function wishlistIds(): array
    {
        $customerId = auth('customer')->id();
        if (!$customerId) {
            return [];
        }

        return Wishlist::where('customer_id', $customerId)->pluck('product_id')->toArray();
    }
}
