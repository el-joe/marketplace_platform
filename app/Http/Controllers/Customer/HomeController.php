<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\Customer\HomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeService $home,
    ) {}

    public function index(Request $request, Country $country): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->cookie('session_id') ?? session()->getId();
        $customer  = $request->user('customer');

        $data = $this->home->getHomeData($country, $customer, (string) $sessionId);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
