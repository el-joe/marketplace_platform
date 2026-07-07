<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    // GET /api/marketer/v1/countries
    //
    // Unauthenticated — needed to populate the country picker on the
    // registration form, before a marketer has an account or token.
    public function index(): JsonResponse
    {
        $countries = Country::where('is_active', true)
            ->where('is_launched', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en'])
            ->map(fn (Country $country) => ['id' => $country->id, 'name' => $country->name_en])
            ->values();

        return ApiResponse::success($countries);
    }
}
