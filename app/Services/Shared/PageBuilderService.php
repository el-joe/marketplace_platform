<?php

namespace App\Services\Shared;

use App\Models\Country;
use App\Models\Page;
use Illuminate\Http\Request;

class PageBuilderService
{
    /**
     * Resolve the active published page for the given type + reference,
     * filtered by device_target, audience, A/B variant, and country_override.
     * Returns null if no matching published page exists — callers degrade gracefully.
     */
    public function resolve(
        Country $country,
        string $pageType,
        ?string $referenceId,
        string $deviceTarget = 'all',
        string $audience = 'guest',
    ): ?array {
        $page = Page::where('country_id', $country->id)
            ->where('page_type', $pageType)
            ->when(
                $referenceId !== null,
                fn ($q) => $q->where('reference_id', $referenceId),
                fn ($q) => $q->whereNull('reference_id'),
            )
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now()))
            ->orderByDesc('is_default')
            ->orderByDesc('published_at')
            ->first();

        if (! $page) {
            return null;
        }

        $blocks = $page->blocks()
            ->where('is_visible', true)
            ->where(fn ($q) => $q->whereNull('visible_from')->orWhere('visible_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('visible_until')->orWhere('visible_until', '>', now()))
            ->whereIn('device_target', ['all', $deviceTarget])
            ->whereIn('audience', ['all', $audience])
            ->where(fn ($q) => $q->whereNull('country_override')->orWhere('country_override', $country->id))
            ->orderBy('position')
            ->get();

        return [
            'page_id' => $page->id,
            'page_type' => $page->page_type,
            'version' => $page->version,
            'seo' => [
                'title' => $page->seo_title,
                'description' => $page->seo_description,
                'og_image_url' => $page->og_image_url,
            ],
            'blocks' => $blocks->map(fn ($b) => [
                'id' => $b->id,
                'block_type' => $b->block_type,
                'position' => $b->position,
                'device_target' => $b->device_target,
                'config' => $b->config,
            ])->toArray(),
        ];
    }

    /**
     * Detect mobile vs desktop from User-Agent header.
     */
    public function detectDevice(Request $request): string
    {
        $ua = strtolower($request->header('User-Agent', ''));

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
