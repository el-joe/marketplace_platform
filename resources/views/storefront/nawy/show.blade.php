@extends('layouts.storefront-mobile')

@php
    $locale  = app()->getLocale();
    $variant = $listing->productVariant;
    $product = $variant?->product;
    $images  = $product?->images ?? collect();
    $name    = $locale === 'ar'
        ? ($product?->name_ar ?? $product?->name_en)
        : ($product?->name_en ?? $product?->name_ar);
    $description = $locale === 'ar'
        ? ($product?->description_ar ?? $product?->description_en)
        : ($product?->description_en ?? $product?->description_ar);
@endphp

@section('title', $name ?? config('app.name'))

@section('content')
<div class="flex flex-col h-full overflow-y-auto bg-white">

    {{-- Back nav --}}
    <div class="sticky top-0 z-10 bg-white border-b border-gray-100 px-4 py-3 flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="p-1 -ms-1 rounded-lg text-gray-500 hover:bg-gray-100">
            <x-heroicon name="arrow-left" class="w-5 h-5 {{ $locale === 'ar' ? 'rotate-180' : '' }}" />
        </a>
        <h1 class="flex-1 text-sm font-semibold text-gray-900 truncate">{{ $name }}</h1>
    </div>

    {{-- Product image --}}
    <div class="relative aspect-square bg-gray-50 shrink-0">
        @php $primaryImage = $images->firstWhere('is_primary', true) ?? $images->first(); @endphp
        @if($primaryImage)
            <img
                src="{{ Storage::url($primaryImage->path) }}"
                alt="{{ $name }}"
                class="w-full h-full object-contain"
            >
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-200">
                <x-heroicon name="photo" class="w-16 h-16" />
            </div>
        @endif
    </div>

    {{-- Product info --}}
    <div class="px-4 pt-4 pb-2">
        @if($product?->brand)
            <p class="text-xs font-semibold text-primary-600 uppercase tracking-wide mb-1">
                {{ $product->brand->{"name_{$locale}"} ?? $product->brand->name_en }}
            </p>
        @endif
        <h2 class="text-base font-bold text-gray-900 leading-snug">{{ $name }}</h2>

        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-xl font-bold text-gray-900">
                {{ number_format($listing->price_cents / 100, 2) }}
            </span>
            <span class="text-sm text-gray-500">{{ $listing->currency }}</span>
        </div>
    </div>

    {{-- ── DELIVERY INFORMATION panel ───────────────────────────────────── --}}
    <div class="mx-4 mt-4 border border-gray-100 rounded-xl overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                {{ $locale === 'ar' ? 'معلومات التوصيل' : 'DELIVERY INFORMATION' }}
            </h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($deliveryOptions as $i => $method)
                @if($i > 0)
                    <div class="px-4 py-1 text-center text-xs text-gray-400">
                        {{ $locale === 'ar' ? 'أو' : 'or' }}
                    </div>
                @endif
                <div class="flex items-center gap-3 px-4 py-3.5">
                    {{-- Method badge --}}
                    @if($method->badge_label_en)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap flex-shrink-0"
                            style="background: {{ $method->badge_color_hex }}; color: {{ $method->badge_text_color_hex }}">
                            {{ $locale === 'ar' ? $method->badge_label_ar : $method->badge_label_en }}
                        </span>
                    @else
                        <span class="w-16 flex-shrink-0"></span>
                    @endif

                    {{-- Delivery description --}}
                    <div class="flex-1 min-w-0">
                        <span class="text-sm text-gray-800 font-medium" id="{{ $method->code === 'same_day' ? 'same-day-eta' : '' }}">
                            @if($method->code === 'same_day')
                                {{ $locale === 'ar' ? 'احصل عليه اليوم' : 'Get it Today' }}
                            @elseif($method->max_delivery_days === 1)
                                {{ $locale === 'ar' ? ($method->delivery_label_ar ?? 'استلمه غداً') : ($method->delivery_label_en ?? 'Get it Tomorrow') }}
                            @else
                                {{ $locale === 'ar'
                                    ? "خلال {$method->min_delivery_days}-{$method->max_delivery_days} أيام"
                                    : "Get it in {$method->min_delivery_days}-{$method->max_delivery_days} business days" }}
                            @endif
                        </span>
                    </div>

                    {{-- Price --}}
                    @if($method->show_estimated_price)
                        <div class="flex-shrink-0 text-sm font-medium">
                            <span class="text-gray-400 text-xs">
                                {{ $locale === 'ar' ? 'يحدد عند الشراء' : 'Calculated at checkout' }}
                            </span>
                        </div>
                        {{-- ↗ icon on first row when multiple options --}}
                        @if($i === 0 && $deliveryOptions->count() > 1)
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                        @endif
                    @endif
                </div>
            @empty
                <div class="px-4 py-4 text-sm text-gray-400">
                    {{ $locale === 'ar' ? 'معلومات التوصيل غير متوفرة' : 'Delivery information not available' }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Product description --}}
    @if($description)
        <div class="px-4 mt-5 pb-6">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                {{ $locale === 'ar' ? 'وصف المنتج' : 'ABOUT THIS ITEM' }}
            </h3>
            <div class="text-sm text-gray-700 leading-relaxed">
                {!! nl2br(e($description)) !!}
            </div>
        </div>
    @endif

    {{-- Bottom safe area --}}
    <div class="pb-safe"></div>
</div>
@endsection

@push('head')
@if($deliveryOptions->contains(fn($o) => $o->shippingMethod->code === 'same_day'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('same-day-eta');
        if (!el) return;
        fetch('/api/delivery/same-day-eta?listing_id={{ $listing->id }}&city_id=')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.eta_minutes) {
                    var hrs  = Math.floor(data.eta_minutes / 60);
                    var mins = data.eta_minutes % 60;
                    el.textContent = {{ $locale === 'ar' ? "'احصل عليه في ' + hrs + ' س ' + mins + ' د'" : "'GET IN ' + hrs + ' HR ' + mins + ' MINS'" }};
                }
            })
            .catch(function () {});
    });
</script>
@endif
@endpush
