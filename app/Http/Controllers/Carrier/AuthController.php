<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carrier\Auth\LoginRequest;
use App\Http\Resources\Carrier\ShippingCompanyResource;
use App\Http\Resources\Carrier\SupervisorResource;
use App\Http\Responses\ApiResponse;
use App\Models\ShippingCompanySupervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const ACCESS_TTL_MINUTES  = 60;
    private const REFRESH_TTL_MINUTES = 43200; // 30 days

    // ── Login ─────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var ShippingCompanySupervisor|null $supervisor */
        $supervisor = ShippingCompanySupervisor::where('email', $request->email)->first();

        if (! $supervisor || ! Hash::check($request->password, $supervisor->password)) {
            return ApiResponse::error('Invalid credentials.', [], 401);
        }

        // Company-level gate: check before is_active so company suspension
        // gives a clearer message than a generic "account inactive" response.
        $company = $supervisor->company;

        if ($company->status === 'pending') {
            return ApiResponse::error(
                'Your company is pending platform approval and cannot log in yet.',
                ['company_status' => 'pending'],
                403
            );
        }

        if ($company->status === 'suspended') {
            return ApiResponse::error(
                'Your company has been suspended by the platform. Please contact support.',
                ['company_status' => 'suspended'],
                403
            );
        }

        if (! $supervisor->is_active) {
            return ApiResponse::error(
                'Your account is inactive. Contact your company administrator.',
                [],
                403
            );
        }

        $supervisor->load('company.country');

        return ApiResponse::success(array_merge(
            [
                'supervisor' => new SupervisorResource($supervisor),
                'company'    => new ShippingCompanyResource($supervisor->company),
            ],
            $this->issueTokenPair($supervisor)
        ));
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        auth('shipping_supervisor_api')->logout();
        return ApiResponse::success(null, 'Logged out successfully.');
    }

    // ── Refresh Token ─────────────────────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('shipping_supervisor_api')->refresh();
        } catch (\Throwable) {
            return ApiResponse::error('Invalid or expired refresh token.', [], 401);
        }

        return ApiResponse::success([
            'access_token' => $newToken,
            'token_type'   => 'bearer',
            'expires_in'   => self::ACCESS_TTL_MINUTES * 60,
        ]);
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function me(): JsonResponse
    {
        /** @var ShippingCompanySupervisor $supervisor */
        $supervisor = auth('shipping_supervisor_api')->user();
        $supervisor->load('company.country');

        return ApiResponse::success([
            'supervisor'  => new SupervisorResource($supervisor),
            'company'     => new ShippingCompanyResource($supervisor->company),
            'permissions' => $supervisor->permissions ?? [],
        ]);
    }

    // ── Token helpers ─────────────────────────────────────────────────────────

    private function issueTokenPair(ShippingCompanySupervisor $supervisor): array
    {
        $accessToken = auth('shipping_supervisor_api')
            ->setTTL(self::ACCESS_TTL_MINUTES)
            ->login($supervisor);

        $refreshToken = JWTAuth::customClaims([
            'type'  => 'refresh',
            'guard' => 'shipping_supervisor_api',
        ])->setTTL(self::REFRESH_TTL_MINUTES)->fromUser($supervisor);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => self::ACCESS_TTL_MINUTES * 60,
        ];
    }
}
