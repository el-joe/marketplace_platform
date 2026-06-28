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

        // Featured products from active campaigns
        $campaigns = $marketer->campaigns()
            ->where('status', 'active')
            ->with(['products.vendorListing.product'])
            ->limit(3)
            ->get();

        return view('marketer.profile.boutiqaat', [
            'marketer' => $marketer,
            'campaigns' => $campaigns,
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
            'phone'      => 'nullable|string|max:30',
            'niche'      => 'nullable|string|max:100',
            'country_id' => 'required|exists:countries,id',
            'bio' => 'nullable|string|max:2000',
            'profile_video_url' => 'nullable|url|max:500',
            'promo_content' => 'nullable|string|max:3000',
            'boutiqaat_style_slug' => 'nullable|string|max:100|alpha_dash|unique:marketers,boutiqaat_style_slug,' . $marketer->id,
            'whatsapp_number' => 'nullable|string|max:30',
            'social_instagram' => 'nullable|string|max:255',
            'social_tiktok' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_facebook' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
        ]);

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
