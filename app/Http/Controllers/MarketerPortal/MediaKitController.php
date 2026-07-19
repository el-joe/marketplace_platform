<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\InfluencerMediaKit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MediaKitController extends Controller
{
    public function show(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $mediaKit = InfluencerMediaKit::firstOrCreate(['marketer_id' => $marketer->id]);

        return view('marketer.media-kit.show', [
            'marketer' => $marketer,
            'mediaKit' => $mediaKit,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $validated = $request->validate([
            'headline' => 'nullable|string|max:200',
            'audience_age_range' => 'nullable|string|max:50',
            'audience_gender_split' => 'nullable|string|max:100',
            'primary_audience_country' => 'nullable|string|max:100',
            'avg_post_reach' => 'nullable|integer|min:0',
            'avg_story_views' => 'nullable|integer|min:0',
            'avg_video_views' => 'nullable|integer|min:0',
            'rate_per_post' => 'nullable|integer|min:0',
            'rate_per_story' => 'nullable|integer|min:0',
            'rate_per_video' => 'nullable|integer|min:0',
            'rate_currency' => 'nullable|string|size:3',
            'portfolio_urls' => 'nullable|array',
            'portfolio_urls.*' => 'url|max:2048',
            'past_brands' => 'nullable|array',
            'past_brands.*' => 'string|max:200',
            'is_visible_to_vendors' => 'nullable|boolean',
        ]);

        $mediaKit = InfluencerMediaKit::firstOrCreate(['marketer_id' => $marketer->id]);

        $mediaKit->update([
            ...$validated,
            'portfolio_urls' => array_values(array_filter($validated['portfolio_urls'] ?? [])),
            'past_brands' => array_values(array_filter($validated['past_brands'] ?? [])),
            'is_visible_to_vendors' => $request->boolean('is_visible_to_vendors'),
            'last_updated_at' => now(),
        ]);

        return back()->with('success', 'Media kit updated.');
    }
}
