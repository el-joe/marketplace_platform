<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\ClassifiedListing;
use App\Models\MarketerCampaign;
use App\Models\TravelPackage;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    /**
     * Generate / return the full tracking URL for a campaign.
     */
    public function generateLink(MarketerCampaign $campaign): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($campaign->marketer_id !== $marketer->id, 403);

        $domain = env('APP_DOMAIN', 'localhost');
        $country = env('DEFAULT_COUNTRY_SLUG', 'sa');
        $url = 'https://' . $country . '.' . $domain . '/r/' . $campaign->tracking_url_slug
            . '?ref=' . $marketer->referral_code;

        return response()->json([
            'url' => $url,
            'slug' => $campaign->tracking_url_slug,
            'ref' => $marketer->referral_code,
        ]);
    }

    /**
     * Daily stats JSON for Chart.js.
     */
    public function stats(MarketerCampaign $campaign): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($campaign->marketer_id !== $marketer->id, 403);

        $clicks = \DB::table('marketer_clicks')
            ->selectRaw('DATE(clicked_at) as date, COUNT(*) as cnt')
            ->where('campaign_id', $campaign->id)
            ->where('clicked_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date');

        $conversions = \DB::table('marketer_conversions')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->where('campaign_id', $campaign->id)
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('cnt', 'date');

        $labels = [];
        $cData = [];
        $kvData = [];

        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels[] = $d;
            $cData[] = $clicks[$d] ?? 0;
            $kvData[] = $conversions[$d] ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'clicks' => $cData,
            'conversions' => $kvData,
        ]);
    }

    /**
     * Storefront tracking redirect: /r/{slug}
     * Records the click and redirects to the campaign destination.
     */
    public function redirect(string $country, string $slug): RedirectResponse
    {
        $campaign = MarketerCampaign::with('campaignable')->where('tracking_url_slug', $slug)->first();

        if (!$campaign || $campaign->status !== 'active') {
            return redirect('/');
        }

        $domain = env('APP_DOMAIN', 'localhost');
        $base = 'https://' . $country . '.' . $domain . '/';

        $destination = match ($campaign->campaignable_type) {
            Vendor::class => $base . 'store/' . $campaign->campaignable_id,
            ClassifiedListing::class => $base . 'classifieds/' . $campaign->campaignable?->listing_number,
            // VERIFY: travel storefront route uses package id — confirmed via TravelController::show model binding.
            // If a slug-based route is added later, update this line.
            TravelPackage::class => $base . 'travel/' . $campaign->campaignable_id,
            default => $base,
        };

        return redirect($destination);
    }
}
