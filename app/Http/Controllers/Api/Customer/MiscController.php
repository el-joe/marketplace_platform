<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// TODO: implement flat GET /shipping-methods.
// Country-scoped shipping methods already exist at
// checkout/shipping-methods in App\Http\Controllers\Customer\CheckoutController.
class MiscController extends Controller
{
    public function countries(Request $request): JsonResponse
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->where('is_launched', true)
            ->orderBy('name_en')
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'iso_code_2' => $country->iso_code_2,
                'iso_code_3' => $country->iso_code_3,
                'name' => app()->getLocale() === 'ar' ? $country->name_ar : $country->name_en,
                'flag_emoji' => $country->flag_emoji,
                'phone_prefix' => $country->phone_prefix,
                'currency_code' => $country->currency_code,
                'site_code' => $country->site_code,
                'cod_available' => $country->cod_available,
            ]);

        return ApiResponse::success($countries);
    }

    public function cities(Request $request, string $country): JsonResponse
    {
        $countryModel = Country::query()
            ->where('is_active', true)
            ->where('is_launched', true)
            ->where(function ($query) use ($country) {
                $query->where('id', $country)
                    ->orWhere('site_code', $country)
                    ->orWhere('iso_code_2', $country);
            })
            ->first();

        if (! $countryModel) {
            return ApiResponse::error(__('customer_api.misc.country_not_found'), [], 404);
        }

        $cities = City::query()
            ->forCountry($countryModel->id)
            ->where('is_active', true)
            ->orderBy(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')
            ->get()
            ->map(fn (City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'cod_available' => $city->cod_available,
            ]);

        return ApiResponse::success($cities);
    }

    public function shippingMethods(Request $request): JsonResponse
    {
        return ApiResponse::error(__('customer_api.not_implemented'), [], 501);
    }
}
