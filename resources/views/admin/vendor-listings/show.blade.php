@extends('layouts.admin')

@section('title', __('admin.vendor_listings.show_title'))

@section('content')
<div class="p-6 space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $listing->productVariant->product->name_en }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $listing->vendor->business_name }} · {{ $listing->country->name_en }}</p>
        </div>
        <a href="{{ route('admin.vendor-listings.edit', $listing) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            {{ __('common.edit') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- General --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('admin.vendor_listings.general_section') }}</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.status_col') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ ucfirst(str_replace('_', ' ', $listing->status?->value)) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.price_col') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ number_format($listing->price, 2) }} {{ $listing->currency }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.vendor_sku') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ $listing->vendor_sku ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.warehouse_col') }}</dt>
                    <dd class="mt-1 text-gray-800">{{ $listing->warehouse?->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Fulfillment / Shipping --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('admin.vendor_listings.fulfillment_section') }}</h3>
            <dl class="grid grid-cols-1 gap-4 text-sm">
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.primary_shipping_method') }}</dt>
                    <dd class="mt-1">
                        @if($listing->primaryShippingMethod)
                            <x-shipping-method-badge :method="$listing->primaryShippingMethod" />
                        @else
                            <span class="text-gray-800">{{ __('admin.vendor_listings.inherits_category_default') }}</span>
                            <x-shipping-method-badge :method="$categoryDefaultShippingMethod" fallback-text="— none configured —" />
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wide">{{ __('admin.vendor_listings.available_shipping_methods') }}</dt>
                    <dd class="mt-2">
                        @if($availableShippingMethods->isEmpty())
                            <span class="text-xs text-gray-400">{{ __('admin.vendor_listings.no_shipping_methods_available') }}</span>
                        @else
                            <ul class="flex flex-wrap gap-3">
                                @foreach($availableShippingMethods as $method)
                                    <li class="flex items-center gap-1.5 text-sm text-gray-700">
                                        <x-shipping-method-badge :method="$method" />
                                        <span>{{ $method->name }}</span>
                                        @if($method->is_default)
                                            <span class="text-[10px] uppercase tracking-wide text-gray-400">(default)</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
