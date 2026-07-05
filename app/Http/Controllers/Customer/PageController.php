<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Services\Customer\PageRendererService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRendererService $renderer,
    ) {}

    public function show(string $type, Request $request,$country): JsonResponse
    {
        $country = $request->attributes->get('country');
        $slug      = $request->query('slug');
        $sessionId = $request->header('X-Session-Id') ?? $request->cookie('session_id') ?? session()->getId();
        $customer  = $request->user('customer');

        $result = $this->renderer->render($type, $slug ?: null, $country, $customer, (string) $sessionId);

        if (empty($result)) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }
}
