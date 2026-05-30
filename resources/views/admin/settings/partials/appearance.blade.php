{{-- Appearance Settings Partial --}}

{{-- Brand Colors --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Brand Colors</h2>
        <p class="text-sm text-gray-500">Primary and secondary brand colors used across the storefront.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', ['primary_color', 'secondary_color']) as $setting)
            @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>

{{-- Footer Text --}}
<x-card class="mb-6">
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Footer</h2>
        <p class="text-sm text-gray-500">Footer copyright text in English and Arabic.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', ['footer_text_en', 'footer_text_ar']) as $setting)
            @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>

{{-- Announcement Bar --}}
<x-card>
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Announcement Bar</h2>
        <p class="text-sm text-gray-500">Top-of-page banner shown across the storefront.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
                'announcement_bar_enabled',
                'announcement_bar_text_en',
                'announcement_bar_text_ar',
                'announcement_bar_color',
            ]) as $setting)
                @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
    {{-- Live preview --}}
    <div class="mt-5 pt-5 border-t border-gray-100">
<p class="text-xs font-medium text-gray-500 mb-2">Live Preview</p>
        <div
            id="announcement-preview"
            class="rounded-lg px-4 py-2 text-center text-sm text-white font-medium transition-colors"
            style="background-color: {{ $settings->firstWhere('key', 'announcement_bar_color')?->value ?? '#0284c7' }}"
        >
            {{ $settings->firstWhere('key', 'announcement_bar_text_en')?->value ?? 'Announcement text' }}
        </div>
    </div>
</x-card>
