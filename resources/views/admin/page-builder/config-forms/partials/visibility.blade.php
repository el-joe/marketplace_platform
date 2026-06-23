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
    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Visibility settings</h4>

    <x-form.toggle name="__vis_is_visible" label="Visible" :value="$vis['is_visible']" />

    <div class="grid grid-cols-2 gap-3">
        <x-form.date-picker name="__vis_visible_from"  label="Visible from"  :value="$vis['visible_from']"  enableTime />
        <x-form.date-picker name="__vis_visible_until" label="Visible until" :value="$vis['visible_until']" enableTime />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <x-form.select name="__vis_device_target" label="Device target" :value="$vis['device_target']">
            <option value="all">All devices</option>
            <option value="desktop">Desktop only</option>
            <option value="mobile">Mobile only</option>
            <option value="app">App only</option>
        </x-form.select>

        <x-form.select name="__vis_audience" label="Audience" :value="$vis['audience']">
            <option value="all">All visitors</option>
            <option value="guest">Guests only</option>
            <option value="logged_in">Logged-in users</option>
            <option value="vip">VIP members</option>
        </x-form.select>
    </div>
</section>
