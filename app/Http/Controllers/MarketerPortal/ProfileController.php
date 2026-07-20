<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Services\MarketerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly MarketerService $service)
    {
    }

    /**
     * Public Boutiqaat-style profile page — no auth required.
     */
    public function boutiqaat(string $slug): View
    {
        $marketer = \App\Models\Marketer::where('boutiqaat_style_slug', $slug)
            ->orWhere('id', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        abort_unless($marketer->is_profile_public, 404);

        // Featured products from active campaigns
        $campaigns = $marketer->campaigns()
            ->where('status', 'active')
            ->with(['products.vendorListing.product'])
            ->limit(3)
            ->get();

        return view('marketer.profile.boutiqaat', [
            'marketer' => $marketer,
            'campaigns' => $campaigns,
            'activeCampaignsCount' => $marketer->campaigns()->where('status', 'active')->count(),
            'isPreview' => false,
        ]);
    }

    /**
     * My Store preview — how vendors/customers see the marketer's public profile.
     */
    public function preview(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $campaigns = $marketer->campaigns()
            ->where('status', 'active')
            ->with(['products.vendorListing.product'])
            ->limit(3)
            ->get();

        return view('marketer.profile.boutiqaat', [
            'marketer' => $marketer,
            'campaigns' => $campaigns,
            'activeCampaignsCount' => $marketer->campaigns()->where('status', 'active')->count(),
            'isPreview' => true,
        ]);
    }

    /**
     * Edit own profile page.
     */
    public function edit(): View
    {
        $marketer = Auth::guard('marketer')->user();

        return view('marketer.profile.edit', [
            'marketer'  => $marketer,
            'countries' => \App\Models\Country::orderBy('name_en')->get(['id', 'name_en']),
        ]);
    }

    /**
     * Save profile updates.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:30',
            'niche'      => 'nullable|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'bio' => 'nullable|string|max:2000',
            'bio_ar' => 'nullable|string|max:2000',
            'website_url' => 'nullable|url|max:500',
            'profile_video_url' => 'nullable|url|max:500',
            'promo_content' => 'nullable|string|max:3000',
            'boutiqaat_style_slug' => 'nullable|string|max:100|alpha_dash|unique:marketers,boutiqaat_style_slug,' . $marketer->id,
            'whatsapp_number' => 'nullable|string|max:30',
            'social_instagram' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'social_snapchat' => 'nullable|string|max:255',
            'followers_count' => 'nullable|integer|min:0',
            'engagement_rate' => 'nullable|numeric|min:0|max:100',
            'is_profile_public' => 'nullable|boolean',
            'accept_new_campaigns' => 'nullable|boolean',
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

        $validated['is_profile_public'] = $request->boolean('is_profile_public');
        $validated['accept_new_campaigns'] = $request->boolean('accept_new_campaigns');

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('marketer-photos', 'public');
            $validated['profile_photo_path'] = $path;
        }

        // Handle banner upload
        if ($request->hasFile('profile_banner')) {
            $path = $request->file('profile_banner')->store('marketer-banners', 'public');
            $validated['profile_banner_path'] = $path;
        }

        $marketer->update($validated);

        return response()->json(['success' => true, 'message' => 'Profile updated.']);
    }
}
