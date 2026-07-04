{{-- Shared visibility section used inside every block config form --}}
@php
    /** @var \App\Models\PageBlock|null $block */
    $vis = [
        'is_visible'    => $block?->is_visible ?? true,
        'visible_from'  => optional($block?->visible_from)->format('Y-m-d H:i'),
        'visible_until' => optional($block?->visible_until)->format('Y-m-d H:i'),
        'device_target' => $block?->device_target ?? 'all',
        'audience'      => $block?->audience ?? 'all',
    ];
@endphp

<section class="pt-4 mt-4 border-t border-gray-200 space-y-4" data-visibility-section>
    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('admin.page_builder.config_forms.visibility.section_title') }}</h4>

    <x-form.toggle name="__vis_is_visible" label="{{ __('admin.page_builder.config_forms.visibility.visible') }}" :value="$vis['is_visible']" />

    <div class="grid grid-cols-2 gap-3">
        <x-form.date-picker name="__vis_visible_from"  label="{{ __('admin.page_builder.config_forms.visibility.visible_from') }}"  :value="$vis['visible_from']"  enableTime />
        <x-form.date-picker name="__vis_visible_until" label="{{ __('admin.page_builder.config_forms.visibility.visible_until') }}" :value="$vis['visible_until']" enableTime />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <x-form.select name="__vis_device_target" label="{{ __('admin.page_builder.config_forms.visibility.device_target') }}" :value="$vis['device_target']">
            <option value="all">{{ __('admin.page_builder.config_forms.visibility.all_devices') }}</option>
            <option value="desktop">{{ __('admin.page_builder.config_forms.visibility.desktop_only') }}</option>
            <option value="mobile">{{ __('admin.page_builder.config_forms.visibility.mobile_only') }}</option>
            <option value="app">{{ __('admin.page_builder.config_forms.visibility.app_only') }}</option>
        </x-form.select>

        <x-form.select name="__vis_audience" label="{{ __('admin.page_builder.config_forms.visibility.audience') }}" :value="$vis['audience']">
            <option value="all">{{ __('admin.page_builder.config_forms.visibility.all_visitors') }}</option>
            <option value="guest">{{ __('admin.page_builder.config_forms.visibility.guests_only') }}</option>
            <option value="logged_in">{{ __('admin.page_builder.config_forms.visibility.logged_in_users') }}</option>
            <option value="vip">{{ __('admin.page_builder.config_forms.visibility.vip_members') }}</option>
        </x-form.select>
    </div>
</section>
