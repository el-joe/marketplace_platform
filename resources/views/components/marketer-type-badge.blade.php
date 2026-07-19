@props(['type'])

@php
    $value = $type?->value ?? $type;
    $color = match ($value) {
        'influencer' => 'indigo',
        'celebrity' => 'yellow',
        'brand_ambassador' => 'purple',
        'affiliate' => 'green',
        default => 'gray',
    };
    $label = ucfirst(str_replace('_', ' ', $value ?? ''));
@endphp

<span {{ $attributes->merge(['class' => "badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-{$color}-100 text-{$color}-700"]) }}>
    {{ $label }}
</span>
