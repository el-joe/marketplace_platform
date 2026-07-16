@extends('layouts.storefront-mobile')

@section('title', 'Now Nawy')

@section('content')
<div class="flex h-full overflow-hidden">

    {{-- ─── Sidebar: Nawy categories ──────────────────────────────────────── --}}
    <aside class="hidden md:flex flex-col w-56 shrink-0 border-e border-gray-100 bg-white overflow-y-auto">
        <div class="px-4 pt-5 pb-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">الأقسام</p>
        </div>

        <nav class="flex-1 px-2 pb-4 space-y-0.5">
            <a href="{{ route('nawy.index', $countryModel->slug ?? $countryModel->iso_code_2) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                      {{ !isset($category) ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <x-heroicon name="squares-2x2" class="w-5 h-5 shrink-0" />
                الكل
            </a>

            @foreach($categories as $cat)
            <a href="{{ route('nawy.category', [$countryModel->slug ?? $countryModel->iso_code_2, $cat]) }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                      {{ isset($category) && $category->id === $cat->id
                            ? 'bg-primary-50 text-primary-700 font-medium'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                @if($cat->icon)
                    <img src="{{ $cat->icon }}" alt="" class="w-5 h-5 object-contain shrink-0">
                @else
                    <x-heroicon name="tag" class="w-5 h-5 shrink-0" />
                @endif
                {{ app()->getLocale() === 'ar' ? $cat->name_ar : $cat->name_en }}
            </a>
            @endforeach
        </nav>
    </aside>

    {{-- ─── Main content ───────────────────────────────────────────────────── --}}
    <div class="flex-1 overflow-y-auto">

        {{-- Header --}}
        <div class="sticky top-0 z-10 bg-white border-b border-gray-100 px-4 py-3 flex items-center gap-3">
            <x-heroicon name="sparkles" class="w-5 h-5 text-primary-600" />
            <h1 class="text-base font-bold text-gray-900">Now Nawy</h1>
        </div>

        {{-- Filter pills: الكل | إكسبريس فقط | عالمي فقط --}}
        <div class="flex gap-2 px-4 py-3 overflow-x-auto border-b border-gray-100 bg-white">
            @php
                $baseRoute = isset($category)
                    ? route('nawy.category', [$countryModel->slug ?? $countryModel->iso_code_2, $category])
                    : route('nawy.index', $countryModel->slug ?? $countryModel->iso_code_2);
            @endphp

            @foreach([null => 'الكل', 'express' => '🚀 إكسبريس فقط', 'global' => '🌍 عالمي فقط'] as $type => $label)
            <a href="{{ $baseRoute }}{{ $type ? '?type=' . $type : '' }}"
               class="inline-flex items-center px-3.5 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors
                      {{ $filter === $type
                            ? 'bg-primary-600 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>

        {{-- Mobile categories horizontal scroll (hidden on md+) --}}
        <div class="md:hidden flex gap-2 px-4 py-2.5 overflow-x-auto border-b border-gray-100 bg-gray-50">
            <a href="{{ route('nawy.index', $countryModel->slug ?? $countryModel->iso_code_2) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors shrink-0
                      {{ !isset($category) ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                الكل
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('nawy.category', [$countryModel->slug ?? $countryModel->iso_code_2, $cat]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors shrink-0
                      {{ isset($category) && $category->id === $cat->id
                            ? 'bg-primary-600 text-white'
                            : 'bg-white text-gray-600 border border-gray-200' }}">
                {{ app()->getLocale() === 'ar' ? $cat->name_ar : $cat->name_en }}
            </a>
            @endforeach
        </div>

        {{-- Product grid --}}
        <div class="p-4">
            @if($listings->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <x-heroicon name="sparkles" class="w-12 h-12 text-gray-300 mb-3" />
                    <p class="text-gray-500 text-sm">لا توجد منتجات متاحة حالياً</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($listings as $listing)
                    @php
                        $variant   = $listing->productVariant;
                        $product   = $variant?->product;
                        $image     = $product?->images->first();
                        $locale    = app()->getLocale();
                        $cardMethod = $shippingMethods[$listing->fulfillment_type] ?? null;
                    @endphp
                    <a href="{{ route('nawy.show', [$countryModel->slug ?? $countryModel->iso_code_2, $listing]) }}"
                       class="block bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        {{-- Product image --}}
                        <div class="relative aspect-square bg-gray-50">
                            @if($image)
                                <img
                                    src="{{ Storage::url($image->path) }}"
                                    alt="{{ $product->name_en }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <x-heroicon name="photo" class="w-10 h-10" />
                                </div>
                            @endif

                            {{-- Shipping method badge --}}
                            @if($cardMethod)
                                <div class="absolute top-2 start-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold"
                                        style="background-color: {{ $cardMethod->badge_color_hex }}; color: {{ $cardMethod->badge_text_color_hex }}">
                                        {{ $locale === 'ar' ? $cardMethod->badge_label_ar : $cardMethod->badge_label_en }}
                                    </span>
                                </div>
                            @endif

                            {{-- COD badge --}}
                            @if($listing->payment_options === 'cod_only')
                                <div class="absolute bottom-2 end-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ $locale === 'ar' ? 'دفع عند الاستلام' : 'Cash on Delivery' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Product info --}}
                        <div class="p-3 space-y-1">
                            <p class="text-xs text-gray-500 line-clamp-1">
                                {{ $locale === 'ar'
                                    ? ($product?->name_ar ?? $product?->name_en)
                                    : ($product?->name_en ?? $product?->name_ar) }}
                            </p>
                            <p class="text-sm font-bold text-gray-900">
                                {{ \App\Helpers\CurrencyFormatter::formatPrice($listing->price, $listing->currency, $locale) }}
                            </p>
                            @if($cardMethod && $cardMethod->is_express_type)
                                <div class="mt-1">
                                    <span class="text-xs font-bold"
                                        style="color: {{ $cardMethod->badge_color_hex }}">
                                        {{ $locale === 'ar' ? $cardMethod->badge_label_ar : $cardMethod->badge_label_en }}
                                    </span>
                                    <span class="text-xs text-gray-600 ms-1">
                                        @if($cardMethod->max_delivery_days === 0)
                                            {{ $locale === 'ar' ? 'اليوم' : 'Today' }}
                                        @elseif($cardMethod->max_delivery_days === 1)
                                            {{ $locale === 'ar' ? 'غداً' : 'Tomorrow' }}
                                        @else
                                            {{ $locale === 'ar'
                                                ? "خلال {$cardMethod->min_delivery_days}-{$cardMethod->max_delivery_days} أيام"
                                                : "In {$cardMethod->min_delivery_days}-{$cardMethod->max_delivery_days} days" }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
