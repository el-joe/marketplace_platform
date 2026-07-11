<?php

namespace App\Http\Controllers\Marketer;

use App\Enums\MarketerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketer\Auth\ForgotPasswordRequest;
use App\Http\Requests\Marketer\Auth\LoginRequest;
use App\Http\Requests\Marketer\Auth\RegisterRequest;
use App\Http\Requests\Marketer\Auth\ResetPasswordRequest;
use App\Http\Resources\Marketer\MarketerProfileResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\NotifyAdminNewMarketerJob;
use App\Models\DeviceToken;
use App\Models\Marketer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const ACCESS_TTL_MINUTES  = 60;
    private const REFRESH_TTL_MINUTES = 43200; // 30 days

    // ── Register ──────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        $links = $request->input('social_links', []);

        $marketer = Marketer::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'password'         => Hash::make($request->password),
            'phone'            => $request->phone,
            'type'             => $request->type,
            'country_id'       => $request->country_id,
            'followers_count'  => $request->followers_count,
            'niche'            => $request->niche,
            'bio'              => $request->bio,
            'social_instagram' => $links['instagram'] ?? null,
            'social_tiktok'    => $links['tiktok']    ?? null,
            'social_youtube'   => $links['youtube']   ?? null,
            'social_twitter'   => $links['twitter']   ?? null,
            'social_facebook'  => $links['facebook']  ?? null,
            'status'           => 'pending',
            // referral_code and boutiqaat_style_slug generated in model boot
        ]);

        NotifyAdminNewMarketerJob::dispatch($marketer);

        return ApiResponse::success(
            ['marketer' => ['id' => $marketer->id, 'name' => $marketer->name, 'status' => $marketer->status]],
            'Registration submitted. Your account is pending admin approval.',
            201
        );
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var Marketer|null $marketer */
        $marketer = Marketer::where('email', $request->email)->first();

        if (!$marketer || !Hash::check($request->password, $marketer->password)) {
            return ApiResponse::error('Invalid credentials.', [], 401);
        }

        if ($marketer->status !== MarketerStatus::Active) {
            $message = match ($marketer->status) {
                MarketerStatus::Pending   => 'Your account is pending admin approval.',
                MarketerStatus::Suspended => 'Your account has been suspended. Please contact support.',
                MarketerStatus::Rejected  => 'Your account application was not approved.',
                default     => 'Your account is not active.',
            };
            return ApiResponse::error($message, ['status' => $marketer->status], 403);
        }

        $marketer->update(['last_login_at' => now()]);

        if ($request->filled('fcm_token') && $request->filled('platform')) {
            DeviceToken::updateOrCreate(
                ['tokenable_type' => Marketer::class, 'tokenable_id' => $marketer->id],
                [
                    'token'        => $request->fcm_token,
                    'platform'     => $request->platform,
                    'is_active'    => 1,
                    'last_used_at' => now(),
                ]
            );
        }

        $marketer->load(['country', 'commissionTiers']);

        return ApiResponse::success(array_merge(
            ['marketer' => new MarketerProfileResource($marketer)],
            $this->issueTokenPair($marketer)
        ));
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        auth('marketer_api')->logout();
        return ApiResponse::success(null, 'Logged out successfully.');
    }

    // ── Refresh Token ─────────────────────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('marketer_api')->refresh();
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
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();
        $marketer->load(['country', 'commissionTiers']);

        return ApiResponse::success(new MarketerProfileResource($marketer));
    }

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('marketers')->sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error(__($status), [], 422);
        }

        return ApiResponse::success(null, 'Password reset link sent to your email.');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('marketers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Marketer $marketer, string $password) {
                $marketer->update(['password' => Hash::make($password)]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(__($status), [], 422);
        }

        return ApiResponse::success(null, 'Password reset successfully.');
    }

    // ── Device Token (FCM) ────────────────────────────────────────────────────

    public function registerDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string|max:255',
            'platform' => 'required|string|in:ios,android',
        ]);

        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();

        DeviceToken::updateOrCreate(
            [
                'tokenable_type' => Marketer::class,
                'tokenable_id'   => $marketer->id,
            ],
            [
                'token'        => $request->token,
                'platform'     => $request->platform,
                'is_active'    => 1,
                'last_used_at' => now(),
            ]
        );

        return ApiResponse::success(null, 'Device registered for push notifications.');
    }

    public function removeDeviceToken(Request $request): JsonResponse
    {
        /** @var Marketer $marketer */
        $marketer = auth('marketer_api')->user();

        DeviceToken::where('tokenable_type', Marketer::class)
            ->where('tokenable_id', $marketer->id)
            ->update(['is_active' => 0]);

        return ApiResponse::success(null, 'Device token deregistered.');
    }

    // ── Token helpers ─────────────────────────────────────────────────────────

    private function issueTokenPair(Marketer $marketer): array
    {

        $accessToken = auth('marketer_api')->setTTL(self::ACCESS_TTL_MINUTES)->login($marketer);

        JWTAuth::factory()->setTTL(self::REFRESH_TTL_MINUTES);
        $refreshToken = JWTAuth::customClaims([
            'type'  => 'refresh',
            'guard' => 'marketer_api',
        ])->setTTL(self::REFRESH_TTL_MINUTES)->fromUser($marketer);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => self::ACCESS_TTL_MINUTES * 60,
        ];
    }
}
