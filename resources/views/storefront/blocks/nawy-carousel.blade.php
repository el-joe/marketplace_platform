@php
    /** @var \Illuminate\Database\Eloquent\Collection $listings */
    /** @var array $config */
    $locale   = app()->getLocale();
    $title_en = $config['title_en'] ?? '';
    $title_ar = $config['title_ar'] ?? '';
    $title    = $locale === 'ar' ? ($title_ar ?: $title_en) : ($title_en ?: $title_ar);
@endphp

@if($listings->isNotEmpty())
<section class="py-4">
    @if($title)
    <div class="flex items-center gap-2 px-4 mb-3">
        <x-heroicon name="sparkles" class="w-5 h-5 text-primary-600 shrink-0" />
        <h2 class="text-base font-bold text-gray-900">{{ $title }}</h2>
    </div>
    @endif

    {{-- Horizontal scroll carousel --}}
    <div class="flex gap-3 overflow-x-auto px-4 pb-2 snap-x snap-mandatory scrollbar-hide">
        @foreach($listings as $listing)
        @php
            $variant     = $listing->productVariant;
            $product     = $variant?->product;
            $image       = $product?->images->first();
            $name        = $locale === 'ar'
                ? ($product?->name_ar ?? $product?->name_en)
                : ($product?->name_en ?? $product?->name_ar);
            $cardMethod  = ($shippingMethods ?? collect())->get($listing->fulfillment_type);
        @endphp

        <div class="flex-none w-36 snap-start bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            {{-- Image --}}
            <div class="relative aspect-square bg-gray-50">
                @if($image)
                    <img
                        src="{{ Storage::url($image->path) }}"
                        alt="{{ $name }}"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    >
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-200">
                        <x-heroicon name="photo" class="w-8 h-8" />
                    </div>
                @endif

                {{-- Shipping method badge --}}
                @if($cardMethod)
                    <div class="absolute top-1.5 start-1.5">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold"
                            style="background-color: {{ $cardMethod->badge_color_hex }}; color: {{ $cardMethod->badge_text_color_hex }}">
                            {{ $locale === 'ar' ? $cardMethod->badge_label_ar : $cardMethod->badge_label_en }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="p-2 space-y-0.5">
                <p class="text-xs text-gray-700 line-clamp-2 leading-tight">{{ $name }}</p>
                <p class="text-sm font-bold text-gray-900">
                    {{ \App\Helpers\CurrencyFormatter::formatPrice($listing->price_cents, $listing->currency, $locale) }}
                </p>
                @if($cardMethod && $cardMethod->is_express_type)
                    <div class="flex items-center gap-1 pt-0.5">
                        <span class="text-xs font-bold" style="color: {{ $cardMethod->badge_color_hex }}">
                            {{ $locale === 'ar' ? $cardMethod->badge_label_ar : $cardMethod->badge_label_en }}
                        </span>
                        <span class="text-xs text-gray-500">
                            @if($cardMethod->max_delivery_days === 0)
                                {{ $locale === 'ar' ? 'اليوم' : 'Today' }}
                            @elseif($cardMethod->max_delivery_days === 1)
                                {{ $locale === 'ar' ? 'غداً' : 'Tomorrow' }}
                            @else
                                {{ $locale === 'ar'
                                    ? "خلال {$cardMethod->min_delivery_days}-{$cardMethod->max_delivery_days} أيام"
                                    : "{$cardMethod->min_delivery_days}-{$cardMethod->max_delivery_days} days" }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif
