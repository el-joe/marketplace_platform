<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\NavNodeResource;
use App\Models\Country;
use App\Services\Customer\NavigationService;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    public function __construct(
        private readonly NavigationService $navigation,
    ) {}

    /**
     * GET /api/customer/v1/{country}/nav
     * Unified category nav tree: products → classifieds → travel.
     */
    public function index(Country $country): JsonResponse
    {
        $tree = $this->navigation->getTree($country);

        return response()->json([
            'success' => true,
            'data'    => [
                'nav' => NavNodeResource::collection($tree)->resolve(),
            ],
        ]);
    }
}
