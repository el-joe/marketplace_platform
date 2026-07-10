<?php

use App\Models\PortalContent;

if (!function_exists('portal_content')) {
    /**
     * Get a localized plain-text/richtext value from the admin-editable
     * "Portal Content" CMS, falling back to a hardcoded string if the row
     * is missing, inactive, or empty. Use for text/richtext fields only —
     * for anchors/buttons use portal_link() instead.
     *
     * Usage in Blade:
     *   {{ portal_content('faq', 'faq_item_1', 'question', 'What is...?', 'ما هو...؟') }}
     */
    function portal_content(
        string $pageKey,
        string $blockKey,
        string $fieldKey,
        ?string $fallbackEn = null,
        ?string $fallbackAr = null
    ): string {
        $isAr = session('locale', 'ar') === 'ar';
        $fallback = ($isAr ? $fallbackAr : $fallbackEn) ?? $fallbackAr ?? $fallbackEn ?? '';

        $row = PortalContent::forPage($pageKey)->get($blockKey . '.' . $fieldKey);

        if (!$row || !$row->is_active) {
            return $fallback;
        }

        $value = $isAr ? $row->value_ar : $row->value_en;

        return ($value === null || $value === '') ? $fallback : $value;
    }
}

if (!function_exists('portal_link')) {
    /**
     * Get a localized link (label + url) from the admin-editable "Portal
     * Content" CMS. Always returns ['label' => string, 'url' => string],
     * falling back to the given fallback args if the row is missing,
     * inactive, or of the wrong type.
     *
     * Usage in Blade:
     *   @php($cta = portal_link('home', 'hero', 'cta_button', 'Get Started', 'ابدأ الآن', '/register'))
     *   <a href="{{ $cta['url'] }}">{{ $cta['label'] }}</a>
     */
    function portal_link(
        string $pageKey,
        string $blockKey,
        string $fieldKey,
        ?string $fallbackLabelEn = null,
        ?string $fallbackLabelAr = null,
        ?string $fallbackUrl = null
    ): array {
        $isAr = session('locale', 'ar') === 'ar';
        $fallbackLabel = ($isAr ? $fallbackLabelAr : $fallbackLabelEn) ?? $fallbackLabelAr ?? $fallbackLabelEn ?? '';
        $fallback = ['label' => $fallbackLabel, 'url' => $fallbackUrl ?? '#'];

        $row = PortalContent::forPage($pageKey)->get($blockKey . '.' . $fieldKey);

        if (!$row || !$row->is_active || $row->type !== 'link') {
            return $fallback;
        }

        $label = $isAr ? $row->value_ar : $row->value_en;
        $label = ($label === null || $label === '') ? $fallbackLabel : $label;
        $url = ($row->value_url === null || $row->value_url === '') ? ($fallbackUrl ?? '#') : $row->value_url;

        return ['label' => $label, 'url' => $url];
    }
}
