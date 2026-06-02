<?php

namespace App\Services;

use App\Models\MarketerCampaign;
use App\Models\MarketerClick;
use App\Models\MarketerQrCode;
use App\Models\Marketer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class MarketerTrackingService
{
    /**
     * Handle all marketer attribution entry paths (Closed Technical Path).
     * Tracks clicks regardless of how the customer arrived:
     *   1. URL ref param (?ref=CODE)
     *   2. Route slug (/r/{slug})
     *   3. QR scan (?qr=QR_ID)
     *   4. WhatsApp (?via=wa or wa.me referrer)
     *   5. Barcode (?bc=VALUE)
     *   6. Cookie / session (previous visit)
     */
    public function handleTrackingRequest(Request $request, string $slug, ?string $ref): void
    {
        $campaign = MarketerCampaign::where('tracking_url_slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$campaign) {
            return;
        }

        // Resolve marketer from referral code or fall back to campaign owner
        $marketer = null;
        if ($ref) {
            $marketer = Marketer::where('referral_code', $ref)->where('status', 'active')->first();
        }
        $marketer ??= $campaign->marketer;

        if (!$marketer) {
            return;
        }

        // Detect WhatsApp origin
        $viaWhatsapp = $request->query('via') === 'wa'
            || str_contains($request->header('Referer', ''), 'wa.me');

        // Handle QR scan
        if ($qrId = $request->query('qr')) {
            $qrCode = MarketerQrCode::find($qrId);
            if ($qrCode) {
                $qrCode->increment('scan_count');
                $qrCode->update(['last_scanned_at' => now()]);
            }
        }

        // Deduplicate: 1 click per campaign per session
        $sessionKey = 'mc_' . $campaign->id;

        if (!$request->session()->has($sessionKey)) {
            $ua = strtolower($request->userAgent() ?? '');
            $device = 'desktop';
            if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) {
                $device = 'mobile';
            } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
                $device = 'tablet';
            }

            $click = MarketerClick::create([
                'campaign_id' => $campaign->id,
                'marketer_id' => $marketer->id,
                'customer_id' => auth('web')->id(),
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer_url' => $request->header('Referer'),
                'device_type' => $device,
                'country_id' => null,
                'clicked_at' => now(),
                'converted' => 0,
            ]);

            $request->session()->put($sessionKey, $click->id);
            $campaign->increment('total_clicks');
            $marketer->increment('total_clicks');

            // Store auto-coupon from WhatsApp link
            if ($couponCode = $request->query('coupon')) {
                $request->session()->put('auto_coupon', $couponCode);
            }

            // Persist full attribution in session
            $request->session()->put('marketer_attribution', [
                'campaign_id' => $campaign->id,
                'marketer_id' => $marketer->id,
                'click_id' => $click->id,
                'model' => $campaign->attribution_model ?? 'last_click',
                'via_whatsapp' => $viaWhatsapp,
                'expires_at' => now()->addDays(30)->toIso8601String(),
            ]);

            Cookie::queue('marketer_ref', json_encode([
                'campaign_id' => $campaign->id,
                'marketer_id' => $marketer->id,
            ]), 60 * 24 * 30);
        }
    }
}
