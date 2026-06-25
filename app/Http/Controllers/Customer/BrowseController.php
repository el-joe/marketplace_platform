<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\Customer\BrowseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrowseController extends Controller
{
    public function __construct(private readonly BrowseService $browse) {}

    /**
     * GET /api/customer/v1/{country}/browse/{type}/{slug}
     */
    public function show(Request $request, Country $country, string $type, string $slug): JsonResponse
    {
        $request->validate([
            'type' => [Rule::in(['product', 'classified', 'travel'])],
        ]);

        if (!in_array($type, ['product', 'classified', 'travel'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid browse type.'], 404);
        }

        $data = $this->browse->browse($type, $slug, $country, $request);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
