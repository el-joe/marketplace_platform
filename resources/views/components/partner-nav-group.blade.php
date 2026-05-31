@props(['label'])

<div class="pt-3">
    <p class="px-3 mb-1 text-xs font-semibold text-gray-600 uppercase tracking-wider select-none">
        {{ $label }}
    </p>
    <div class="space-y-0.5">
        {{ $slot }}
    </div>
</div>