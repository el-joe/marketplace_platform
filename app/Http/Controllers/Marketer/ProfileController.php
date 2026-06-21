<?php

namespace App\Http\Controllers\Marketer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Profile\UpdateBankAccountRequest;
use App\Http\Requests\Marketer\Profile\UpdatePasswordRequest;
use App\Http\Requests\Marketer\Profile\UpdatePhotoRequest;
use App\Http\Requests\Marketer\Profile\UpdateProfileRequest;
use App\Http\Resources\Marketer\MarketerProfileResource;
use App\Http\Responses\ApiResponse;
use App\Models\Marketer;
use App\Services\Marketer\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    private function marketer(): Marketer
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        return $marketer;
    }

    // GET /profile
    public function show(): JsonResponse
    {
        $marketer = $this->marketer()->load(['country', 'commissionTiers']);
        return ApiResponse::success(new MarketerProfileResource($marketer));
    }

    // PUT /profile
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $marketer = $this->service->update($this->marketer(), $request->validated());
        return ApiResponse::success(new MarketerProfileResource($marketer), 'Profile updated.');
    }

    // POST /profile/photo
    public function updatePhoto(UpdatePhotoRequest $request): JsonResponse
    {
        $marketer = $this->service->updatePhoto(
            $this->marketer(),
            $request->file('file'),
            $request->input('type')
        );

        $field = $request->input('type') === 'banner' ? 'banner_url' : 'profile_photo_url';
        $path  = $request->input('type') === 'banner'
            ? $marketer->profile_banner_path
            : $marketer->profile_photo_path;

        return ApiResponse::success([
            $field => $path ? asset('storage/' . $path) : null,
        ], 'Photo updated.');
    }

    // PUT /profile/password
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $marketer = $this->marketer();

        if (!Hash::check($request->current_password, $marketer->password)) {
            return ApiResponse::error('Current password is incorrect.', [], 422);
        }

        $marketer->update(['password' => Hash::make($request->password)]);

        return ApiResponse::success(null, 'Password updated.');
    }

    // PUT /profile/bank-account
    public function updateBankAccount(UpdateBankAccountRequest $request): JsonResponse
    {
        $this->service->updateBankAccount($this->marketer(), $request->validated());
        return ApiResponse::success(null, 'Bank account saved.');
    }
}
