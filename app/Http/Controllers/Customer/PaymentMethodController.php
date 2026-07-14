<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Resources\Customer\PaymentMethodResource;
use App\Http\Responses\ApiResponse;
use App\Models\Country;
use App\Models\CountryPaymentMethod;
use App\Models\Customer;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $paymentMethods = $customer->paymentMethods()
            ->orderByDesc('is_default')
            ->get();

        return ApiResponse::success(
            PaymentMethodResource::collection($paymentMethods),
            'Payment methods retrieved.'
        );
    }

    public function store(StorePaymentMethodRequest $request, $country): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $data = $request->validated();

        /** @var Country|null $resolvedCountry */
        $resolvedCountry = $request->attributes->get('country');

        $methodConfig = CountryPaymentMethod::where('country_id', $resolvedCountry?->id)
            ->where('method_type', $data['type'])
            ->where('provider', $data['gateway'])
            ->where('is_active', true)
            ->first();

        if (!$methodConfig) {
            return ApiResponse::error('This payment gateway is not available in your country.', [], 422);
        }

        $paymentMethod = DB::transaction(function () use ($customer, $data) {
            if (!empty($data['is_default'])) {
                $customer->paymentMethods()->where('is_default', true)->update(['is_default' => false]);
            }

            return $customer->paymentMethods()->create($data);
        });

        return ApiResponse::success(new PaymentMethodResource($paymentMethod), 'Payment method added.', 201);
    }

    public function setDefault($country, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('setDefault', $paymentMethod);

        DB::transaction(function () use ($paymentMethod) {
            $paymentMethod->customer->paymentMethods()
                ->where('id', '!=', $paymentMethod->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $paymentMethod->update(['is_default' => true]);
        });

        return ApiResponse::success(new PaymentMethodResource($paymentMethod->fresh()), 'Default payment method updated.');
    }

    public function destroy($country, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('delete', $paymentMethod);

        $paymentMethod->delete();

        return ApiResponse::success(null, 'Payment method deleted.');
    }
}
