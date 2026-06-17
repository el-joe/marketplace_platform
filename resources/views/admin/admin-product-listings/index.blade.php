@extends('layouts.admin')

@section('title', 'Admin Product Listings')

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Admin Product Listings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage products sold directly by admin — powers the Now Nawy feed.</p>
        </div>
        <a href="{{ route('admin.admin-product-listings.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
            <x-heroicon name="plus" class="w-4 h-4" />
            New Listing
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50 text-left">
                    <th class="px-4 py-3 font-medium text-gray-600">Product</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Country</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Price</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Fulfillment</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Payment</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Now Nawy</th>
                    <th class="px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-600"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($listings as $listing)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 line-clamp-1">
                            {{ $listing->productVariant?->product?->name_en ?? '—' }}
                        </p>
                        <p class="text-xs text-gray-400 font-mono">{{ $listing->productVariant?->sku }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $listing->country?->name_en ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ number_format($listing->price_cents / 100, 2) }}
                        <span class="text-xs text-gray-400">{{ $listing->currency }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $ft = $listing->fulfillment_type;
                            $ftColors = ['express' => 'bg-amber-100 text-amber-800', 'global' => 'bg-blue-100 text-blue-800', 'mixed' => 'bg-gray-100 text-gray-700'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $ftColors[$ft] ?? '' }}">
                            {{ ucfirst($ft) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $po = $listing->payment_options;
                            $poLabel = ['cod_only' => 'COD Only', 'electronic_only' => 'Electronic Only', 'both' => 'Both'];
                        @endphp
                        <span class="text-xs text-gray-600">{{ $poLabel[$po] ?? $po }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($listing->featured_in_nawy)
                            <span class="inline-flex items-center gap-1 text-xs text-primary-700 font-medium">
                                <x-heroicon name="sparkles" class="w-3.5 h-3.5" />
                                Featured
                            </span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sc = ['active' => 'bg-green-100 text-green-800', 'paused' => 'bg-yellow-100 text-yellow-700', 'archived' => 'bg-gray-100 text-gray-500'];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$listing->status] ?? '' }}">
                            {{ ucfirst($listing->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('admin.admin-product-listings.nawy-preview', $listing) }}"
                               target="_blank"
                               class="text-xs text-gray-500 hover:text-primary-600 transition-colors"
                               title="Now Nawy Preview">
                                <x-heroicon name="eye" class="w-4 h-4" />
                            </a>
                            <a href="{{ route('admin.admin-product-listings.edit', $listing) }}"
                               class="text-xs text-gray-500 hover:text-primary-600 transition-colors">
                                <x-heroicon name="pencil-square" class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('admin.admin-product-listings.destroy', $listing) }}"
                                  onsubmit="return confirm('Remove this listing?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors">
                                    <x-heroicon name="trash" class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">No listings yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($listings->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $listings->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
