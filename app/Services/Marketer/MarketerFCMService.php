<?php

namespace App\Services\Marketer;

use App\Models\DeviceToken;
use App\Models\Marketer;
use App\Services\Vendor\VendorFCMService;

/**
 * Sends push notifications to marketer mobile devices via FCM.
 *
 * Marketer is single-guard (no admin-layer indirection like vendors have),
 * so this only needs to resolve tokens by marketer id. The actual FCM HTTP v1
 * delivery (including the Google OAuth2 token exchange) is delegated to
 * VendorFCMService::sendToToken(), which is already generic over any token.
 */
class MarketerFCMService
{
    public function __construct(private readonly VendorFCMService $fcmService) {}

    public function sendToMarketer(string $marketerId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('tokenable_type', Marketer::class)
            ->where('tokenable_id', $marketerId)
            ->where('is_active', 1)
            ->pluck('token');

        foreach ($tokens as $token) {
            $this->fcmService->sendToToken($token, $title, $body, $data);
        }
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $this->fcmService->sendToToken($token, $title, $body, $data);
    }
}
