@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}"
    x-data="{ placementCode: @js($config['placement_code'] ?? '') }">
    @csrf

    <div class="space-y-1">
        <label for="placement_code" class="block text-sm font-medium text-gray-700">
            {{ __('admin.page_builder.config_forms.sponsored_products.placement_code') }}
            <span class="text-danger-500 ml-0.5" aria-hidden="true">*</span>
        </label>
        <select name="placement_code" id="placement_code" x-model="placementCode" required
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500"
            data-placements-select data-placements-url="{{ route('admin.page-builder.banner-placements') }}">
            <option value="">{{ __('admin.page_builder.config_forms.deal_of_day.select_placeholder') }}</option>
        </select>
        <p class="text-xs text-gray-500">{{ __('admin.page_builder.config_forms.sponsored_products.placement_help') }}</p>
    </div>

    <x-form.input name="max_items" type="number" label="{{ __('admin.page_builder.config_forms.sponsored_products.max_products') }}" :value="$config['max_items'] ?? 8" min="1"
        max="30" class="mt-3" />

    <div class="mt-3 rounded-lg border border-gray-200 p-3" data-placement-bookings-preview
        data-bookings-url="{{ route('admin.page-builder.banner-placements.bookings') }}">
        <p class="text-xs font-medium text-gray-500 mb-2">
            {{ __('admin.page_builder.config_forms.sponsored_products.active_bookings') }}
        </p>
        <div data-bookings-list class="text-sm text-gray-400">
            {{ __('admin.page_builder.config_forms.sponsored_products.select_placement_first') }}
        </div>
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
