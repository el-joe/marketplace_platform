@extends('layouts.admin')

@section('title', 'Now Nawy Preview')

@section('content')
<div class="p-6 max-w-sm mx-auto">

    <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <x-heroicon name="device-phone-mobile" class="w-4 h-4" />
        Now Nawy card preview
    </div>

    {{-- Simulated mobile card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm w-48">
        @php
            $variant = $listing->productVariant;
            $product = $variant?->product;
            $image   = $product?->images->first();
        @endphp

        <div class="relative aspect-square bg-gray-50">
            @if($image)
                <img src="{{ Storage::url($image->path) }}"
                     alt="{{ $product->name_en }}"
                     class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-300">
                    <x-heroicon name="photo" class="w-10 h-10" />
                </div>
            @endif

            <div class="absolute top-2 start-2">
                @if($listing->fulfillment_type === 'express')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        🚀 إكسبريس
                    </span>
                @elseif($listing->fulfillment_type === 'global')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                        🌍 عالمي
                    </span>
                @endif
            </div>

            @if($listing->payment_options === 'cod_only')
                <div class="absolute bottom-2 end-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        دفع عند الاستلام
                    </span>
                </div>
            @endif
        </div>

        <div class="p-3 space-y-1">
            <p class="text-xs text-gray-500 line-clamp-1">{{ $product?->name_ar ?? $product?->name_en }}</p>
            <p class="text-sm font-bold text-gray-900">
                {{ number_format($listing->price_cents / 100, 2) }}
                <span class="text-xs font-normal text-gray-500">{{ $listing->currency }}</span>
            </p>
        </div>
    </div>

    {{-- Meta info --}}
    <dl class="mt-6 space-y-2 text-sm">
        <div class="flex gap-3">
            <dt class="text-gray-500 w-32 shrink-0">Fulfillment</dt>
            <dd class="font-medium text-gray-900">{{ ucfirst($listing->fulfillment_type) }}</dd>
        </div>
        <div class="flex gap-3">
            <dt class="text-gray-500 w-32 shrink-0">Payment options</dt>
            <dd class="font-medium text-gray-900">{{ str_replace('_', ' ', $listing->payment_options) }}</dd>
        </div>
        <div class="flex gap-3">
            <dt class="text-gray-500 w-32 shrink-0">Nawy category</dt>
            <dd class="font-medium text-gray-900">{{ $listing->nawyCategory?->name_en ?? '—' }}</dd>
        </div>
        <div class="flex gap-3">
            <dt class="text-gray-500 w-32 shrink-0">Featured</dt>
            <dd class="font-medium">
                @if($listing->featured_in_nawy)
                    <span class="text-primary-700 flex items-center gap-1">
                        <x-heroicon name="sparkles" class="w-4 h-4" /> Yes
                    </span>
                @else
                    <span class="text-gray-400">No</span>
                @endif
            </dd>
        </div>
    </dl>

    <div class="mt-6">
        <a href="{{ route('admin.admin-product-listings.edit', $listing) }}"
           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            <x-heroicon name="pencil-square" class="w-4 h-4" />
            Edit listing
        </a>
    </div>
</div>
@endsection
