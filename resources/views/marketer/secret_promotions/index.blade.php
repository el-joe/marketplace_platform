@extends('layouts.marketer')

@section('title', __('marketer.secret_promotions.title'))
@section('page-title', __('marketer.secret_promotions.title'))

@section('content')

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.secret_promotions.title') }}</h2>
        <p class="text-sm text-gray-500">{{ __('marketer.secret_promotions.subtitle') }}</p>
    </div>

    @if($promotions->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">🤫</div>
            <h3 class="font-bold text-gray-700 mb-1">{{ __('marketer.secret_promotions.none_title') }}</h3>
            <p class="text-sm text-gray-400">{{ __('marketer.secret_promotions.none_desc') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($promotions as $promo)
                @php
                    $product = $promo->vendorListing?->productVariant?->product;
                    $statusColors = [
                        'active'  => 'bg-green-100 text-green-700',
                        'pending' => 'bg-blue-100 text-blue-700',
                    ];
                    $sc = $statusColors[$promo->status->value] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0 pr-2">
                            <p class="font-bold text-gray-800 truncate">{{ $product?->name_en ?? __('marketer.secret_promotions.unknown_product') }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $promo->vendor?->store_name }}</p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $sc }}">
                            {{ $promo->status->label() }}
                        </span>
                    </div>

                    <div class="bg-slate-800 rounded-xl p-3 mb-3 text-center">
                        <p class="text-xs text-slate-400">{{ __('marketer.secret_promotions.your_commission') }}</p>
                        <p class="text-lg font-bold text-yellow-400">{{ $promo->marketer_share_pct }}%</p>
                    </div>

                    <p class="text-xs text-gray-400 mb-4">
                        @if($promo->valid_until)
                            {{ __('marketer.secret_promotions.valid_until', ['date' => $promo->valid_until->format('d M Y')]) }}
                        @else
                            {{ __('marketer.secret_promotions.no_expiry') }}
                        @endif
                    </p>

                    <a href="{{ route('marketer.campaigns.create', ['vendor_id' => $promo->vendor_id, 'listing_id' => $promo->vendor_listing_id]) }}"
                        class="mt-auto text-center bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
                        {{ __('marketer.secret_promotions.promote_this') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

@endsection
