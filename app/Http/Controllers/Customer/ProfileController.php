<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Profile\UpdatePasswordRequest;
use App\Http\Requests\Customer\Profile\UpdateProfileRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::success(
            new CustomerResource(auth('customer')->user()),
            'Profile retrieved.'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $data = $request->only(['name', 'date_of_birth']);

        // Phone change: if new phone differs from stored, clear verification
        if ($request->filled('phone') && $request->phone !== $customer->phone) {
            $data['phone'] = $request->phone;
            $data['phone_verified_at'] = null;
        }

        $customer->update($data);

        return ApiResponse::success(new CustomerResource($customer->fresh()), 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $customer->update(['password' => $request->password]);

        // Invalidate all existing JWT tokens for this customer
        auth('customer')->logout(true);

        return ApiResponse::success(null, 'Password updated. Please log in again.');
    }

    public function destroy(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        // customers has no deleted_at column — use status transition
        $customer->update(['status' => 'deleted']);

        auth('customer')->logout(true);

        return ApiResponse::success(null, 'Account deleted.');
    }
}
