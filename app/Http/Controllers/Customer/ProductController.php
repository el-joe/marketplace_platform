<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProductListRequest;
use App\Http\Resources\Customer\ProductDetailResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\Customer\BuyBoxService;
use App\Services\Customer\ProductQueryService;
use App\Services\Customer\ProductViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $products,
        private readonly BuyBoxService $buyBox,
        private readonly ProductViewService $viewService,
    ) {}

    public function index(ProductListRequest $request, Country $country): JsonResponse
    {
        $filters   = $request->validated();
        $perPage   = (int) ($filters['per_page'] ?? 20);
        $page      = (int) ($filters['page'] ?? 1);

        $paginator = $this->products->paginate($country, $filters, $perPage);
        $facets    = $this->products->facets($country, $filters);
        $payload   = $this->products->buildProductsPayload($paginator, $country, $page, 'category_top');

        return response()->json([
            'success' => true,
            'data'    => array_merge($payload, ['facets' => $facets]),
        ]);
    }

    public function show(Request $request, Country $country, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('countrySettings', fn($q) => $q
                ->where('country_id', $country->id)
                ->where('is_available', true)
            )
            ->with([
                'brand',
                'category',
                'images',
                'variants.variantAttributes.attribute',
                'variants.variantAttributes.attributeValue',
                'countrySettings' => fn($q) => $q->where('country_id', $country->id),
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
        $product->setRelation('related', $this->relatedProducts($product, $country, $buyBoxPrice));

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

        $resource = new ProductDetailResource($product);
        $resource->isWishlisted = $isWishlisted;

        return ApiResponse::success($resource->toArray($request));
    }

    private function relatedProducts(Product $product, Country $country, ?int $buyBoxPrice): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->whereHas('countrySettings', fn($q) => $q
                ->where('country_id', $country->id)
                ->where('is_available', true)
            )
            ->with('images');

        if ($buyBoxPrice) {
            $low  = (int) ($buyBoxPrice * 0.7);
            $high = (int) ($buyBoxPrice * 1.3);
            $query->whereHas('variants.vendorListings', fn($q) => $q
                ->where('country_id', $country->id)
                ->where('status', 'active')
                ->whereBetween('price', [$low, $high])
            );
        }

        return $query->orderByDesc('rating_avg')->limit(8)->get();
    }
}
