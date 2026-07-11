<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\ForgotPasswordRequest;
use App\Http\Requests\Customer\Auth\LoginRequest;
use App\Http\Requests\Customer\Auth\RefreshTokenRequest;
use App\Http\Requests\Customer\Auth\RegisterRequest;
use App\Http\Requests\Customer\Auth\ResetPasswordRequest;
use App\Http\Requests\Customer\Auth\VerifyEmailRequest;
use App\Enums\CustomerStatus;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\SendVerificationEmailJob;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerOtpToken;
use App\Services\Customer\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const ACCESS_TTL_MINUTES = 60;
    private const REFRESH_TTL_MINUTES = 43200; // 30 days

    public function __construct(private readonly ReferralService $referralService) {}

    // ── Register ──────────────────────────────────────────────────────────────

    public function register(RegisterRequest $request): JsonResponse
    {
        /** @var Country $country */
        $country = $request->attributes->get('country');

        $customer = DB::transaction(function () use ($request, $country): Customer {
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'country_id' => $country->id,
                'referral_code' => $this->referralService->generateUniqueCode(),
                'status' => CustomerStatus::Active,
            ]);

            if ($request->filled('referral_code')) {
                $this->referralService->applyReferral($customer, $request->referral_code);
            }

            return $customer;
        });

        SendVerificationEmailJob::dispatch($customer);

        $tokens = $this->issueTokenPair($customer);

        return ApiResponse::success(
            array_merge(['customer' => new CustomerResource($customer)], $tokens),
            'Registration successful. Please verify your email.',
            201
        );
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function login(LoginRequest $request): JsonResponse
    {
        $credential = $request->email_or_phone;
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        /** @var Customer|null $customer */
        $customer = Customer::where($field, $credential)->first();

        if (!$customer || !password_verify($request->password, $customer->password)) {
            return ApiResponse::error('Invalid credentials.', [], 401);
        }

        if (in_array($customer->status, [CustomerStatus::Suspended, CustomerStatus::Banned, CustomerStatus::Deleted], true)) {
            $reason = match ($customer->status) {
                CustomerStatus::Suspended => 'Your account has been suspended.',
                CustomerStatus::Banned    => 'Your account has been banned.',
                CustomerStatus::Deleted   => 'This account no longer exists.',
            };
            return ApiResponse::error($reason, [], 403);
        }

        $customer->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $tokens = $this->issueTokenPair($customer);

        return ApiResponse::success(
            array_merge(['customer' => new CustomerResource($customer)], $tokens),
            'Login successful.'
        );
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        auth('customer')->logout();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    // ── Refresh Token ─────────────────────────────────────────────────────────

    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $payload = JWTAuth::setToken($request->refresh_token)->getPayload();
        } catch (\Throwable) {
            return ApiResponse::error('Invalid or expired refresh token.', [], 401);
        }

        if (($payload->get('type') ?? '') !== 'refresh') {
            return ApiResponse::error('Invalid token type.', [], 401);
        }

        if (($payload->get('guard') ?? '') !== 'customer') {
            return ApiResponse::error('Invalid token guard.', [], 401);
        }

        $customer = Customer::find($payload->getSubject());

        if (!$customer || $customer->status !== CustomerStatus::Active) {
            return ApiResponse::error('Account not found or inactive.', [], 401);
        }

        // Invalidate the used refresh token
        JWTAuth::setToken($request->refresh_token)->invalidate();

        $tokens = $this->issueTokenPair($customer);

        return ApiResponse::success($tokens, 'Token refreshed.');
    }

    // ── Me ────────────────────────────────────────────────────────────────────

    public function me(): JsonResponse
    {
        return ApiResponse::success(
            new CustomerResource(auth('customer')->user()),
            'Profile retrieved.'
        );
    }

    // ── Email Verification ────────────────────────────────────────────────────

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $otp = CustomerOtpToken::where('token', $request->token)
            ->where('type', 'email_verification')
            ->whereNull('used_at')
            ->first();

        if (!$otp || !$otp->isValid()) {
            return ApiResponse::error('Invalid or expired verification token.', [], 422);
        }

        $otp->update(['used_at' => now()]);
        $otp->customer->update(['email_verified_at' => now()]);

        return ApiResponse::success(null, 'Email verified successfully.');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        if ($customer->email_verified_at !== null) {
            return ApiResponse::error('Email is already verified.', [], 422);
        }

        SendVerificationEmailJob::dispatch($customer);

        return ApiResponse::success(null, 'Verification email sent.');
    }

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $credential = $request->email_or_phone;
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Always return 200 to prevent user enumeration
        $customer = Customer::where($field, $credential)->where('status', CustomerStatus::Active)->first();

        if ($customer) {
            $customer->otpTokens()
                ->where('type', 'password_reset')
                ->whereNull('used_at')
                ->delete();

            CustomerOtpToken::create([
                'customer_id' => $customer->id,
                'token' => Str::random(64),
                'type' => 'password_reset',
                'expires_at' => now()->addHours(1),
            ]);

            // TODO: dispatch SendPasswordResetEmailJob / SMS job
        }

        return ApiResponse::success(null, 'If an account with those credentials exists, a reset link has been sent.');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $otp = CustomerOtpToken::where('token', $request->token)
            ->where('type', 'password_reset')
            ->whereNull('used_at')
            ->first();

        if (!$otp || !$otp->isValid()) {
            return ApiResponse::error('Invalid or expired reset token.', [], 422);
        }

        $otp->update(['used_at' => now()]);
        $otp->customer->update(['password' => $request->password]);

        return ApiResponse::success(null, 'Password reset successfully.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function issueTokenPair(Customer $customer): array
    {
        $accessToken = auth('customer')->setTTL(self::ACCESS_TTL_MINUTES)->login($customer);

        JWTAuth::factory()->setTTL(self::REFRESH_TTL_MINUTES);

        $refreshToken = JWTAuth::customClaims([
            'type' => 'refresh',
            'guard' => 'customer',
        ])->fromUser($customer);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => self::ACCESS_TTL_MINUTES * 60,
        ];
    }
}
